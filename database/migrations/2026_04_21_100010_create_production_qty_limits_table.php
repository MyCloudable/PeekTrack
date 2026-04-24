<?php

/**
 * SPRINT 1 TICKET S1-14: Create `production_qty_limits` lookup table.
 *
 * WHY (from Ron's 2026-04-22 feedback):
 *   "Calven is bad about typing 1250 tons instead of .65 tons."
 *   "Longline supers on a perfect day could only do 12-15 tons."
 *
 *   Ratio-based scoring doesn't catch order-of-magnitude typos when
 *   the other quantities also scale. A 1250-ton card with 3x the
 *   equipment hours can look perfectly in-ratio. We need a hard
 *   ceiling per production type.
 *
 *   Two protection layers are built from this table:
 *     1. HARD CEILING — if qty > hard_max, the card is flagged REGARDLESS
 *        of score. This is the "nothing valid is ever this big" line.
 *     2. SOFT CEILING — if qty > soft_max, ratio_out weight is applied
 *        and the card is more likely to be flagged Yellow. This is
 *        "possible but worth a glance".
 *
 * STRUCTURE:
 *   production_pattern    As in production_material_pairs — matched
 *                         against production.description.
 *   match_mode            'exact' | 'contains' | 'regex'
 *   unit_of_measure       Tons, sq_yd, linear_ft, each, etc. Paired
 *                         with the limit so 15 tons ≠ 15 sq_yd.
 *   soft_max              Above this, add score. NULL = no soft limit.
 *   hard_max              Above this, force flag. NULL = no hard limit.
 *   daily_max             Sum of qty per card per day. Catches split
 *                         lines with the same description.
 *   source                'seed_from_history' | 'client_provided'
 *                         | 'ml_learned' — audit trail for where the
 *                         number came from.
 *   notes                 Rationale. "Longline perfect day 12-15 tons"
 *
 * EMPTY ON CREATION. Seeded in Sprint 2 from client input.
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
        Schema::create('production_qty_limits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('production_pattern', 255);
            $table->enum('match_mode', ['exact', 'contains', 'regex'])
                ->default('contains');
            $table->string('unit_of_measure', 32);
            $table->double('soft_max')->nullable()
                ->comment('Exceeding adds to risk score but does not auto-flag.');
            $table->double('hard_max')->nullable()
                ->comment('Exceeding forces card to review regardless of score.');
            $table->double('daily_max')->nullable()
                ->comment('Max total qty per card per workdate for this production type.');
            $table->enum('source', [
                'seed_from_history',
                'client_provided',
                'ml_learned',
            ])->default('client_provided');
            $table->boolean('active')->default(true);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['active', 'match_mode'], 'idx_pql_active_mode');
            $table->index('production_pattern', 'idx_pql_pattern');
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_qty_limits');
    }
};
