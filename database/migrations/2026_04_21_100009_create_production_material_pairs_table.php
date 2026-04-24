<?php

/**
 * SPRINT 1 TICKET S1-13: Create `production_material_pairs` lookup table.
 *
 * WHY (from Ron's 2026-04-22 feedback):
 *   Certain production items SHOULD have certain material codes on the
 *   same card. Example: production "yellow thermo" must have material
 *   code 10-xxxxx. Today there is no machine-readable relationship, so
 *   reviewers catch these mismatches manually.
 *
 *   We model this as a lookup table, not hard-coded logic, because:
 *     - The mapping comes from Ron's team as a spreadsheet. Ops should
 *       be able to add/edit pairings without a deploy.
 *     - Product descriptions aren't stable identifiers. We want a
 *       normalized match key that can absorb minor spelling changes.
 *     - Some production items map to MULTIPLE valid material codes
 *       (one-to-many), so a flat column on production wouldn't work.
 *
 * STRUCTURE:
 *   production_pattern     String or regex pattern matched against
 *                          production.description (case-insensitive).
 *   match_mode             'exact' | 'contains' | 'regex'
 *                          Default 'contains' — most operator-friendly.
 *   expected_material_code Material code that should appear on the
 *                          same card when the production pattern matches.
 *                          Can be a prefix (e.g. '10-') with is_prefix=1.
 *   is_prefix              When 1, match material.description/code as
 *                          STARTS WITH expected_material_code.
 *   severity               'required' | 'recommended'
 *                          Required pairs contribute to the risk score
 *                          on mismatch. Recommended pairs are reason
 *                          codes only.
 *   active                 Soft-disable a pair without deleting it.
 *   notes                  Free text for the ops team.
 *
 * EMPTY ON CREATION. Seeded in Sprint 2 from the spreadsheet Ron's team
 * will deliver. See docs/PRODUCTION_MATERIAL_PAIRS_FORMAT.md.
 *
 * RISK: None. New table, empty.
 *
 * ROLLBACK: dropIfExists.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('production_material_pairs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('production_pattern', 255);
            $table->enum('match_mode', ['exact', 'contains', 'regex'])
                ->default('contains');
            $table->string('expected_material_code', 64);
            $table->boolean('is_prefix')->default(false);
            $table->enum('severity', ['required', 'recommended'])
                ->default('required');
            $table->boolean('active')->default(true);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['active', 'match_mode'], 'idx_pmp_active_mode');
            $table->index('production_pattern', 'idx_pmp_pattern');
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_material_pairs');
    }
};
