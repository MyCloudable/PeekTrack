<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Executes the state changes that drop a card on the super's Rejected tab.
 *
 * MIRRORS EXISTING MANAGER-KICKBACK BEHAVIOR
 *   Confirmed with PeekTrack dev 2026-05-08: when a manager rejects a card
 *   today, `approved = 2` is the state change that surfaces it on the
 *   super's Rejected tab. That's the entire UX. No emails, no in-app push.
 *
 *   AI rejections take the same path. The super sees an AI-rejected card
 *   identically to a manager-rejected one. Sprint 4 explicitly avoids
 *   introducing a parallel notification mechanism — same status, same flow.
 *
 * WHAT THIS WRITES
 *   jobentries.approved          → 2
 *   jobentries.kicked_back_at    → NOW()
 *   jobentries.kickback_count    → kickback_count + 1
 *   jobentries.super_notified_at → NOW() (used by EscalateOverdueKickbacks)
 *
 * WHAT THIS DOES NOT WRITE
 *   - kicked_back_reason_codes: column doesn't exist in deployed schema
 *     (Sprint 1 S1-12 migration drift). The reason data lives in
 *     `ai_scoring_audit.layer1_findings` (JSON) and is summarized in
 *     `jobreviews.decision_reason` — both written by AiDecisionMaker.
 *   - approvedBy / approved_date: these track approval-side metadata,
 *     not rejection-side. Leave them as-is.
 *
 * IDEMPOTENCY
 *   If a card is already kicked back (approved=2), this service still
 *   increments kickback_count and bumps kicked_back_at — that models a
 *   resubmit-then-rejected-again cycle. Caller is responsible for not
 *   calling kickback() on a card the AI just decided shouldn't be Red.
 *
 * USAGE
 *   $service->kickback($link, $auditId);
 *   $service->approve($link, $auditId);  // when auto_approve_green is on
 *
 * SHADOW MODE
 *   This service is the LIVE path. AiDecisionMaker checks `ai.shadow_mode`
 *   and only calls this service when shadow is off. Never call this from
 *   shadow-mode code paths.
 */
class KickbackService
{
    public const REJECTED = 2;
    public const APPROVED = 1;

    /**
     * Drop a card on the super's Rejected tab.
     *
     * @param string $link    jobentries.link UUID
     * @param int    $auditId ai_scoring_audit.id (for traceability in logs)
     */
    public function kickback(string $link, int $auditId): void
    {
        $affected = DB::table('jobentries')
            ->where('link', $link)
            ->update([
                'approved'           => self::REJECTED,
                'kicked_back_at'     => now(),
                'kickback_count'     => DB::raw('kickback_count + 1'),
                'super_notified_at'  => now(),
                'updated_at'         => now(),
            ]);

        if ($affected === 0) {
            throw new RuntimeException(
                "KickbackService::kickback — no row updated for link={$link}. " .
                "Card may have been deleted between scoring and action."
            );
        }

        Log::info('[KickbackService] card kicked back by AI', [
            'link'     => $link,
            'audit_id' => $auditId,
        ]);
    }

    /**
     * Auto-approve a Green card (only when ai.auto_approve_green = true).
     */
    public function approve(string $link, int $auditId): void
    {
        $affected = DB::table('jobentries')
            ->where('link', $link)
            ->update([
                'approved'      => self::APPROVED,
                'approvedBy'    => 'AI:' . AiDecisionMaker::ENGINE_VERSION,
                'approved_date' => now()->toDateString(),
                'updated_at'    => now(),
            ]);

        if ($affected === 0) {
            throw new RuntimeException(
                "KickbackService::approve — no row updated for link={$link}."
            );
        }

        Log::info('[KickbackService] card auto-approved by AI', [
            'link'     => $link,
            'audit_id' => $auditId,
        ]);
    }
}
