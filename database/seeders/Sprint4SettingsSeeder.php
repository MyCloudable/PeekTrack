<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 4 settings additions.
 *
 * Idempotent — uses updateOrInsert keyed by key_name, so re-running is safe.
 * Will NOT clobber values an admin has changed via the admin panel.
 *
 * NEW KEYS
 *   ai.auto_approve_green          (default false)
 *     If true, Green AI decisions auto-approve the card (sets approved=1).
 *     If false (default), Green still goes through human approval.
 *     Admin opts in separately from ai.shadow_mode.
 *
 *   ai.kickback_escalation_days    (default 3)
 *     Threshold for EscalateOverdueKickbacks. Cards kicked back longer
 *     than this without being fixed are escalated to the super's manager.
 *
 * EXISTING KEYS (no change here, just documented for context)
 *   ai.shadow_mode                       — defaults to true; flip after pilot
 *   ai.manager_escalation_enabled        — defaults to false; flip after manager_id coverage backfill
 */
class Sprint4SettingsSeeder extends Seeder
{
    public function run()
    {
        $now = now();
        $rows = [
            [
                'key_name'   => 'ai.auto_approve_green',
                'value'      => 'false',
                'value_type' => 'bool',
                'notes'      => 'When true, Green AI decisions auto-approve the card. Conservative default false. Admin opts in after pilot.',
            ],
            [
                'key_name'   => 'ai.kickback_escalation_days',
                'value'      => '3',
                'value_type' => 'int',
                'notes'      => 'Days a kickback can age before EscalateOverdueKickbacks notifies the super manager.',
            ],
        ];

        foreach ($rows as $r) {
            DB::table('settings')->updateOrInsert(
                ['key_name' => $r['key_name']],
                array_merge($r, ['updated_at' => $now])
            );
        }
    }
}
