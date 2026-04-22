<?php

/**
 * SPRINT 1 TICKET S1-01: Add link indexes to line-item tables.
 *
 * WHY:
 *   production, material, equipment, and jobreviews all have `link` as the
 *   foreign key back to jobentries, but none of them have an index on it.
 *   Every scoring/aggregation query table-scans today. On prod-sized data
 *   (200K+ line items) this is the difference between a 50ms scoring run
 *   and a 30-second one.
 *
 * RISK: None. Non-unique B-tree index adds on InnoDB are online operations
 *       and don't lock writes on MySQL 5.6+ / MariaDB 10.x.
 *
 * ROLLBACK: $table->dropIndex(['link']);
 *
 * BENCHMARK BEFORE/AFTER:
 *   EXPLAIN SELECT SUM(qty) FROM production WHERE link = '...uuid...';
 *   Before: type=ALL, rows=~total_table_size
 *   After:  type=ref, rows=<100
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('production', function (Blueprint $table) {
            $table->index('link', 'idx_production_link');
        });

        Schema::table('material', function (Blueprint $table) {
            $table->index('link', 'idx_material_link');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->index('link', 'idx_equipment_link');
        });

        Schema::table('jobreviews', function (Blueprint $table) {
            $table->index('link', 'idx_jobreviews_link');
        });
    }

    public function down()
    {
        Schema::table('production', function (Blueprint $table) {
            $table->dropIndex('idx_production_link');
        });

        Schema::table('material', function (Blueprint $table) {
            $table->dropIndex('idx_material_link');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex('idx_equipment_link');
        });

        Schema::table('jobreviews', function (Blueprint $table) {
            $table->dropIndex('idx_jobreviews_link');
        });
    }
};
