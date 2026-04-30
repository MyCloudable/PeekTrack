<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;

/**
 * Detects unestimated items on a card by phase code.
 *
 * BACKGROUND
 *   Per client clarification 2026-04-22, unestimated items appear in the
 *   `production` and `material` tables with phase codes 98-09000 and
 *   98-19999. The `non_est_items` table exists in the schema but isn't
 *   currently wired into the workflow (see docs/R8_UNESTIMATED_DETECTION.md
 *   for the forward-compatibility note).
 *
 * WHY EXTRACTED
 *   The phase-code list is configurable via the `ai.unestimated_phase_codes`
 *   setting. Keeping detection in its own class means swapping the source
 *   later (e.g., when/if billing refactors to use `non_est_items`) is a
 *   single-class change rather than a hunt across the FeatureBuilder.
 */
class UnestimatedItemDetector
{
    /**
     * @return array{has_unestimated: bool, line_count: int}
     */
    public function detect(string $link): array
    {
        $phaseCodes = $this->phaseCodes();

        $productionCount = DB::table('production')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->whereIn('phase', $phaseCodes)
            ->count();

        $materialCount = DB::table('material')
            ->where('link', $link)
            ->whereNull('deleted_at')
            ->whereIn('phase', $phaseCodes)
            ->count();

        $total = $productionCount + $materialCount;

        return [
            'has_unestimated' => $total > 0,
            'line_count'      => $total,
        ];
    }

    /**
     * Phase codes considered "unestimated." Read from settings each call
     * so ops can change them without a deploy. Falls back to the v1.3
     * defaults if the setting is missing.
     */
    private function phaseCodes(): array
    {
        $raw = DB::table('settings')
            ->where('key_name', 'ai.unestimated_phase_codes')
            ->value('value');

        if (!$raw) {
            return ['98-09000', '98-19999'];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded)) {
            return ['98-09000', '98-19999'];
        }

        return $decoded;
    }
}
