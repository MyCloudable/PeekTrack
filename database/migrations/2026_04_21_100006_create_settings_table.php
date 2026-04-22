<?php

/**
 * SPRINT 1 TICKET S1-06: Create `settings` table for feature flags and
 * tunable AI thresholds. No rows seeded here — see SettingsSeeder.
 *
 * WHY:
 *   Every layer of the AI pipeline needs to be runtime-toggleable
 *   without a deploy:
 *     - ai.enabled       master switch
 *     - ai.shadow_mode   score but don't change review_state
 *     - ai.llm_enabled   Layer 4 on/off
 *     - ai.band_green_max, ai.band_yellow_max
 *     - weight_* knobs per reason code
 *
 *   Client ops must be able to tune these from an admin screen during
 *   pilot and early prod. Hard-coded config means a deploy per tweak.
 *
 *   We deliberately don't use Laravel's config/ files — config is
 *   cached and deploy-bound. `settings` is database-backed, cacheable
 *   via the SettingsService with Redis for fast lookups.
 *
 * STRUCTURE:
 *   - `key_name` is the primary key. Dotted namespace (ai.enabled).
 *   - `value` is always a string. `value_type` tells the app how to
 *     cast it. Keeps the column simple; casts live in the service.
 *   - `updated_by` references users.id but intentionally NOT a foreign
 *     key — a deleted user shouldn't cascade-delete config history.
 *
 * RISK: None. New table.
 *
 * ROLLBACK: Drop the table.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key_name', 128)->primary();
            $table->text('value');
            $table->enum('value_type', ['string', 'int', 'float', 'bool', 'json']);
            $table->string('notes', 512)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('updated_at', 'idx_settings_updated_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};
