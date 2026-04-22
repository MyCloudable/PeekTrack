<?php

/**
 * SPRINT 1 TICKET S1-05: Add soft-delete columns to line-item tables.
 *
 * WHY:
 *   Today, when a superintendent deletes a material or equipment row
 *   after submission, it's gone — no audit trail. For the AI system we
 *   need to preserve:
 *     1) What was on the card when the AI scored it (snapshot is in
 *        jobcard_ai_features, but source-level proof lives in these
 *        tables)
 *     2) Training data for the ML layer — removing "rejected, then
 *        deleted" rows would hide the very signals we want to learn
 *
 *   Laravel's SoftDeletes trait only needs a `deleted_at` TIMESTAMP.
 *   Models that add `use SoftDeletes` will then transparently hide
 *   deleted rows from normal queries while preserving them in the DB.
 *
 * RISK: None. Additive column. Default behavior unchanged until the
 *       Laravel models are updated to use the SoftDeletes trait in
 *       Sprint 2.
 *
 * SEE ALSO: app/Models/Production.php, Material.php, Equipment.php —
 *           these need `use SoftDeletes;` added in Sprint 2, alongside
 *           `protected $dates = ['deleted_at'];` for Laravel 9.
 *
 * ROLLBACK: Drop the deleted_at column on each table.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('production', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('material', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('production', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('material', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
