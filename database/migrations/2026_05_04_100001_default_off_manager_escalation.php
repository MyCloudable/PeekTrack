<?php

/**
 * SPRINT 2: Update default for ai.manager_escalation_enabled.
 *
 * WHY (from Sprint 1 audit, 2026-04-29):
 *   The S1-15 audit found users.manager_id coverage at 38.5% (50 of 130
 *   superintendents have a manager assigned). With coverage this low,
 *   manager-escalation auto-emails would silently fail for ~62% of supers
 *   because there's no manager to escalate to.
 *
 *   The fix is operational (HR/ops backfills the missing manager_id
 *   values), not engineering. But until that backfill runs, this feature
 *   should be OFF by default to avoid silent failures and incorrect
 *   user expectations.
 *
 *   Once ops confirms coverage ≥80%, flip this to true via the admin
 *   panel (no deploy needed). The manager dashboard from §9 still
 *   provides oversight without the auto-emails.
 *
 * IDEMPOTENT: only updates if the value is currently the v1.3 default.
 *             Preserves any value an admin has already set manually.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $current = DB::table('settings')
            ->where('key_name', 'ai.manager_escalation_enabled')
            ->value('value');

        if ($current === 'true') {
            DB::table('settings')
                ->where('key_name', 'ai.manager_escalation_enabled')
                ->update([
                    'value' => 'false',
                    // 'description' => 'Send 3-day escalation emails to managers. Default OFF — flip to true ' .
                    //                  'once users.manager_id coverage reaches ≥80%. As of 2026-04-29 audit, ' .
                    //                  'coverage was 38.5%. Auto-escalation with low coverage produces silent ' .
                    //                  'no-op for cards whose super lacks a manager_id.',
                    'updated_at' => now(),
                ]);
            \Log::info("[Sprint 2] Flipped ai.manager_escalation_enabled to false pending manager_id coverage backfill.");
        } else {
            \Log::info("[Sprint 2] ai.manager_escalation_enabled already non-default ({$current}); leaving unchanged.");
        }
    }

    public function down()
    {
        DB::table('settings')
            ->where('key_name', 'ai.manager_escalation_enabled')
            ->update([
                'value' => 'true',
                'updated_at' => now(),
            ]);
    }
};
