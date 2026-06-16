<?php

namespace App\Services\Ai;

use App\Models\JobCardAiFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Top-level AI orchestrator. Runs the full pipeline:
 *   1. RulesEngine (Layer 1)
 *   2. ScoringEngine (Layer 2)
 *   3. BandClassifier (Green/Yellow/Red)
 *   4. TemplateExplainer (human-readable summary)
 *   5. Persists to ai_scoring_audit + jobreviews
 *
 * SHADOW MODE
 *   Sprint 3 runs in shadow mode by default — decisions are persisted
 *   but cards aren't actually green-lit / kicked back. The acted_on
 *   column on ai_scoring_audit distinguishes shadow from live.
 *
 *   The shadow flag comes from settings: ai.shadow_mode (default true).
 *   Sprint 4 flips it to false during pilot exit.
 *
 * USAGE
 *   $decision = app(AiDecisionMaker::class)->decide($feature);
 *   // returns ['band' => 'yellow', 'score' => 45.0, 'findings' => [...], 'audit_id' => 123]
 *
 * Engines are constructor-injected for testability. Production usage
 * should resolve via the container so settings-backed defaults apply.
 */
class AiDecisionMaker
{
    public const ENGINE_VERSION = 'ai_scoring_engine_v1';

    private RulesEngine $rules;
    private ScoringEngine $scoring;
    private BandClassifier $classifier;
    private TemplateExplainer $explainer;
    private KickbackService $kickback;

    public function __construct(
        RulesEngine $rules,
        ScoringEngine $scoring,
        BandClassifier $classifier,
        TemplateExplainer $explainer,
        KickbackService $kickback
    ) {
        $this->rules      = $rules;
        $this->scoring    = $scoring;
        $this->classifier = $classifier;
        $this->explainer  = $explainer;
        $this->kickback   = $kickback;
    }

    /**
     * Run the full pipeline and persist results.
     *
     * @return array{
     *   band: string,
     *   score: float,
     *   findings: array,
     *   summary: string,
     *   audit_id: int,
     *   shadow: bool
     * }
     */
    public function decide(JobCardAiFeature $feature, bool $writeJobReview = true): array
    {
        $start = microtime(true);

        // Layer 1
        $findings = $this->rules->evaluate($feature);
        $hardTriggered = $this->rules->hasHardTrigger($findings);

        // Layer 2
        $scoreResult = $this->scoring->score($findings);
        $score = $scoreResult['score'];
        $breakdown = $scoreResult['breakdown'];

        // Band
        $band = $this->classifier->classify($score, $findings);

        // Explain
        $summary = $this->explainer->summarize($findings);

        // Duration
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        // Persist
        $shadow = $this->isShadowMode();
        $auditId = $this->writeAudit(
            feature: $feature,
            findings: $findings,
            band: $band,
            score: $score,
            hardTriggered: $hardTriggered,
            breakdown: $breakdown,
            durationMs: $durationMs,
            shadow: $shadow
        );

        if ($writeJobReview) {
            $this->writeJobReview(
                feature: $feature,
                band: $band,
                summary: $summary,
                shadow: $shadow
            );
        }

        // Live-mode action dispatch (Sprint 4).
        // Shadow mode skips this entirely — only logs the decision.
        // Yellow always skips — humans review Yellow regardless of mode.
        // Green only acts if ai.auto_approve_green is explicitly enabled.
        if (!$shadow) {
            try {
                if ($band === BandClassifier::BAND_RED) {
                    $this->kickback->kickback($feature->link, $auditId);
                } elseif ($band === BandClassifier::BAND_GREEN && $this->isAutoApproveGreen()) {
                    $this->kickback->approve($feature->link, $auditId);
                }
            } catch (\Throwable $e) {
                // An action failure (e.g. card deleted between scoring and update)
                // should NOT cause the whole decision to fail. The audit row is
                // already written; we just couldn't apply the state change.
                Log::warning('[AiDecisionMaker] live action failed', [
                    'link'     => $feature->link,
                    'band'     => $band,
                    'audit_id' => $auditId,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        Log::info('[AiDecisionMaker] decided', [
            'link'     => $feature->link,
            'band'     => $band,
            'score'    => $score,
            'findings' => count($findings),
            'shadow'   => $shadow,
            'audit_id' => $auditId,
        ]);

        return [
            'band'     => $band,
            'score'    => $score,
            'findings' => array_map(fn ($f) => $f->toArray(), $findings),
            'summary'  => $summary,
            'audit_id' => $auditId,
            'shadow'   => $shadow,
        ];
    }

    private function writeAudit(
        JobCardAiFeature $feature,
        array $findings,
        string $band,
        float $score,
        bool $hardTriggered,
        array $breakdown,
        int $durationMs,
        bool $shadow
    ): int {
        return DB::table('ai_scoring_audit')->insertGetId([
            'link'              => $feature->link,
            'job_number'        => $feature->job_number,
            'workdate'          => $feature->workdate,
            'feature_id'        => $feature->id,
            'feature_version'   => $feature->feature_version,
            'engine_version'    => self::ENGINE_VERSION,
            'band'              => $band,
            'score'             => $score,
            'layer1_triggered'  => $hardTriggered ? 1 : 0,
            'layer1_findings'   => json_encode(array_map(fn ($f) => $f->toArray(), $findings)),
            'layer2_breakdown'  => json_encode($breakdown),
            'acted_on'          => $shadow ? 0 : 1,
            'action_taken'      => $shadow ? null : $this->actionFor($band),
            'duration_ms'       => $durationMs,
            'created_at'        => now(),
        ]);
    }

    private function writeJobReview(
        JobCardAiFeature $feature,
        string $band,
        string $summary,
        bool $shadow
    ): void {
        DB::table('jobreviews')->insert([
            'link'                => $feature->link,
            'job_number'          => $feature->job_number,
            'reviewed_by'         => 'AI',
            'reviewed_by_user_id' => null,
            'reviewed_by_system'  => self::ENGINE_VERSION,
            'decision'            => $this->decisionFor($band, $shadow),
            'decision_reason'     => $summary,
            'date_reviewed'       => now(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    private function decisionFor(string $band, bool $shadow): string
    {
        if ($shadow) {
            return 'flagged'; // shadow mode never auto-approves or auto-rejects
        }
        return match ($band) {
            'green'  => 'approved',
            'yellow' => 'flagged',
            'red'    => 'rejected',
            default  => 'flagged',
        };
    }

    private function actionFor(string $band): ?string
    {
        return match ($band) {
            'green'  => 'approved',
            'yellow' => 'yellow_review',
            'red'    => 'kicked_back',
            default  => null,
        };
    }

    private function isShadowMode(): bool
    {
        // Defaults to TRUE if setting is missing — safest default during deploy.
        return $this->boolSetting('ai.shadow_mode', true);
    }

    private function isAutoApproveGreen(): bool
    {
        // Defaults to FALSE if missing — conservative default. Admin must
        // explicitly opt in to having the AI approve Green cards.
        return $this->boolSetting('ai.auto_approve_green', false);
    }

    /**
     * Read a boolean setting. Accepts 'true', '1', 'yes' as true; everything
     * else (including missing) returns $default.
     */
    private function boolSetting(string $key, bool $default): bool
    {
        $val = DB::table('settings')
            ->where('key_name', $key)
            ->value('value');
        if ($val === null) return $default;
        $val = strtolower(trim((string) $val));
        return in_array($val, ['true', '1', 'yes'], true);
    }
}
