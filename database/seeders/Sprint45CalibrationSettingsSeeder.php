<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 4.5 calibration settings.
 *
 * Idempotent — uses updateOrInsert. WON'T clobber values an admin has changed.
 *
 * INITIAL VALUES preserve Sprint 3 behavior — same thresholds, same severities.
 * After calibration review, admins update individual values via SQL or the
 * settings panel. No code deploy needed.
 *
 * NAMING CONVENTION
 *   ai.rule.<rule_id>.<threshold_name>     — numeric thresholds
 *   ai.rule.<rule_id>.severity             — one of: hard | high | medium | low
 *
 * EXAMPLE POST-CALIBRATION TUNING
 *   UPDATE settings SET value = '10' WHERE key_name = 'ai.rule.R1.min_material_qty';
 *   UPDATE settings SET value = 'high' WHERE key_name = 'ai.rule.R1.severity';
 *   UPDATE settings SET value = '50' WHERE key_name = 'ai.rule.R2.min_production_qty';
 *   UPDATE settings SET value = '0.5' WHERE key_name = 'ai.rule.R2.max_equipment_hours';
 */
class Sprint45CalibrationSettingsSeeder extends Seeder
{
    public function run()
    {
        $now = now();
        $rows = [
            // R1 — material without production
            [
                'key_name'   => 'ai.rule.R1.min_material_qty',
                'value'      => '0',
                'value_type' => 'float',
                'notes'      => 'R1 fires only if material_total_qty > this value. Default 0 = any material.',
            ],
            [
                'key_name'   => 'ai.rule.R1.severity',
                'value'      => 'hard',
                'value_type' => 'string',
                'notes'      => 'R1 finding severity. One of: hard | high | medium | low. hard forces Red.',
            ],

            // R2 — production without equipment
            [
                'key_name'   => 'ai.rule.R2.min_production_qty',
                'value'      => '0',
                'value_type' => 'float',
                'notes'      => 'R2 fires only if production_total_qty > this value. Default 0 = any production.',
            ],
            [
                'key_name'   => 'ai.rule.R2.max_equipment_hours',
                'value'      => '0',
                'value_type' => 'float',
                'notes'      => 'R2 fires if equipment_total_hours <= this value. Default 0 = exactly zero hours.',
            ],
            [
                'key_name'   => 'ai.rule.R2.severity',
                'value'      => 'hard',
                'value_type' => 'string',
                'notes'      => 'R2 finding severity. One of: hard | high | medium | low.',
            ],

            // R3 — empty card
            [
                'key_name'   => 'ai.rule.R3.severity',
                'value'      => 'hard',
                'value_type' => 'string',
                'notes'      => 'R3 finding severity. One of: hard | high | medium | low.',
            ],
            // R9 — equipment-only without reason
            [
                'key_name'   => 'ai.rule.R9.severity',
                'value'      => 'medium',
                'value_type' => 'string',
                'notes'      => 'R9 finding severity. One of: hard | high | medium | low. Default medium so the rule contributes to Yellow scoring.',
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
