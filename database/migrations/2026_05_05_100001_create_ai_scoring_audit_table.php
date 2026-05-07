<?php

/**
 * SPRINT 3 TICKET S3-01: Create ai_scoring_audit table.
 *
 * Per spec §10.5. One row per AI scoring decision. Persists EVERY decision,
 * even shadow-mode ones, for ML training and forensic review.
 *
 * RELATIONSHIP TO jobreviews
 *   `jobreviews` already has `reviewed_by_system` and `decision` columns
 *   (added in Sprint 1 S1-04). Every AI decision writes BOTH a `jobreviews`
 *   row (summary, unified timeline with humans) AND an `ai_scoring_audit`
 *   row (detail, AI-specific structure for ML feedback loops).
 *
 * RETENTION
 *   Spec §11: ai.audit_retention_days = 2555 (7 years). Long retention
 *   because these decisions are contract-relevant.
 *
 * SHADOW MODE
 *   Sprint 3+4 run AI in shadow mode — rows in this table, but no card
 *   actions taken. The `acted_on` column distinguishes "AI ran but nothing
 *   was done" from "AI ran and the card was actually green-lit / kicked back".
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_scoring_audit', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Card identity
            $table->char('link', 36)
                ->comment('jobentries.link UUID');
            $table->string('job_number', 255);
            $table->date('workdate');

            // Decision context
            $table->unsignedBigInteger('feature_id')
                ->nullable()
                ->comment('FK to jobcard_ai_features.id at time of scoring');
            $table->string('feature_version', 16)->default('v1');
            $table->string('engine_version', 32)->default('ai_scoring_engine_v1');

            // Decision output
            $table->enum('band', ['green', 'yellow', 'red'])
                ->comment('Final band after Layer 1 + Layer 2');
            $table->double('score')
                ->comment('0-100 weighted risk score from Layer 2');

            // Layer 1 — hard rules
            $table->boolean('layer1_triggered')->default(false);
            $table->json('layer1_findings')->nullable()
                ->comment('Array of {code, severity, message, rule_id} from RulesEngine');

            // Layer 2 — soft scoring
            $table->json('layer2_breakdown')->nullable()
                ->comment('Scoring contributors keyed by code, plus weight/value');

            // Layer 3/4 (Sprints 5/6) — store NULL for now but column exists
            $table->double('layer3_zscore')->nullable()
                ->comment('Sprint 5+: ML variance z-score');
            $table->string('layer3_model_tag', 64)->nullable();
            $table->boolean('layer4_llm_used')->default(false)
                ->comment('Sprint 6+: did the LLM run?');
            $table->text('layer4_explanation')->nullable()
                ->comment('Sprint 6+: LLM-generated explanation');

            // Action
            $table->boolean('acted_on')->default(false)
                ->comment('false in shadow mode; true when AI verdict drives card state');
            $table->string('action_taken', 32)->nullable()
                ->comment('approved | yellow_review | kicked_back | NULL if shadow');

            // Performance / debug
            $table->integer('duration_ms')->nullable();
            $table->text('debug_notes')->nullable();

            // Timestamps — explicit, not Eloquent timestamps, since this
            // table is append-only and we don't update rows.
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('link', 'idx_asa_link');
            $table->index(['workdate', 'band'], 'idx_asa_workdate_band');
            $table->index('engine_version', 'idx_asa_engine');
            $table->index('created_at', 'idx_asa_created');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_scoring_audit');
    }
};
