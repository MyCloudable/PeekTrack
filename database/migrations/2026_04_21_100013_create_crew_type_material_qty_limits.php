<?php

/**
 * SPRINT 1 TICKET S1-17: Create `crew_type_material_qty_limits` table.
 *
 * WHY (from Ron's 2026-04-22 Materials_List.csv delivery):
 *   Ron delivered ceiling data in a shape we didn't originally plan for.
 *   The original `production_qty_limits` table (S1-14) is keyed by
 *   production description alone. Ron's CSV is keyed by
 *   (crew_type × material_type × unit_of_measure). Example:
 *
 *       Longline / Thermo / Tons      → soft 12, hard 15
 *       Handline / Thermo / Tons      → soft  2, hard  2.5
 *       RPM      / Markers / Each     → soft 2000, hard 3000
 *
 *   Same material type (Thermo) has wildly different ceilings depending
 *   on which crew is using it. A production-only lookup would collapse
 *   these together and miss the Handline-vs-Longline distinction.
 *
 *   Per 2026-04-22 decision (Option A), we adapt the schema to match
 *   how Ron thinks about the data rather than forcing him to re-cut it.
 *
 * RELATIONSHIP TO production_qty_limits (S1-14):
 *   production_qty_limits still exists and is still useful — if a
 *   ceiling applies regardless of crew (e.g., "yellow thermo sq_yd
 *   never above 5000/day regardless of crew"), put it there. If the
 *   ceiling depends on which crew is doing the work, put it here.
 *   Scoring engine checks BOTH tables — more-specific match wins.
 *
 * STRUCTURE:
 *   crew_type_id      FK to crew_types.id (existing table)
 *   material_type     Free-form string (e.g., 'Thermo', 'Paint', 'Markers')
 *                     matched against material.description case-insensitive
 *   unit_of_measure   Required — a ceiling in tons is different from gallons
 *   goal              Target daily value per Ron's "goal for crew to profit
 *                     company" column. Informational; not used in scoring v1.
 *   soft_max          Exceeding adds to risk score (S_QTY_SOFT_CEILING)
 *   hard_max          Exceeding force-flags (S_QTY_HARD_CEILING)
 *   source            'client_provided' | 'seed_from_history' | 'ml_learned'
 *   active            Soft-disable
 *   notes             Free text — include Ron's rationale from CSV
 *
 * RONS SEED DATA (loaded via AiLookupDataSeeder):
 *   Longline     / Thermo   / Tons    / goal=10    / soft=12  / hard=15
 *   Handline     / Thermo   / Tons    / goal=1.5   / soft=2   / hard=2.5
 *   RPM          / Markers  / Each    / goal=1500  / soft=2000/ hard=3000
 *   High Build P / Paint    / Gallon  / goal=500   / soft=700 / hard=800
 *
 *   Skipped from Ron's CSV (intentional, covered elsewhere):
 *   - Tape      → ? marks → auto-flag via R7 COMPLEXITY_OVERRIDE
 *   - Removal   → ? marks → auto-flag via R7 COMPLEXITY_OVERRIDE
 *   - Paint (temp/paver) → "varies" → handled by crew_type_ratio_bands
 *                          with a low-ratio-OK band (paint-behind-paver case)
 *
 * RISK: None. New table, empty at migration time, seeded in Sprint 2.
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
        Schema::create('crew_type_material_qty_limits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('crew_type_id');
            $table->string('material_type', 64)
                ->comment("Matched against material.description case-insensitive substring");
            $table->string('unit_of_measure', 32);
            $table->double('goal')->nullable()
                ->comment('Ron: "goal for crew to profit company". Informational, not used in v1 scoring.');
            $table->double('soft_max')->nullable()
                ->comment('Exceeding adds S_QTY_SOFT_CEILING to score.');
            $table->double('hard_max')->nullable()
                ->comment('Exceeding force-flags with S_QTY_HARD_CEILING.');
            $table->enum('source', [
                'client_provided',
                'seed_from_history',
                'ml_learned',
            ])->default('client_provided');
            $table->boolean('active')->default(true);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->unique(
                ['crew_type_id', 'material_type', 'unit_of_measure', 'active'],
                'uniq_ctmql_tuple_active'
            );
            $table->index(['crew_type_id', 'active'], 'idx_ctmql_crew_active');
            $table->index(['material_type', 'active'], 'idx_ctmql_mat_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('crew_type_material_qty_limits');
    }
};
