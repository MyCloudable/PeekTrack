<?php

/**
 * SPRINT 1 TICKET S1-08: Migrate any orphaned rows from the deprecated
 * `materials` (plural) table into the canonical `material` table, then
 * rename `materials` to `materials_deprecated_do_not_use`.
 *
 * WHY:
 *   Per client decision, `material` (singular) is the canonical table.
 *   `materials` (plural) must be drained and renamed so any forgotten
 *   code path writing to it will ERROR LOUDLY instead of silently
 *   putting data in a dead table.
 *
 * PRECONDITION:
 *   Must run AFTER `php artisan ai:audit-materials` reports clean.
 *   If any code still writes to `materials`, that code must be fixed
 *   first. This migration does not check — it assumes Sprint 1 ticket
 *   S1-07 was completed.
 *
 * WHAT IT DOES:
 *   1. Copy any rows from `materials` into `material` where the
 *      (link, description, qty) tuple doesn't already exist.
 *   2. Log the copied count for the migration report.
 *   3. Rename `materials` → `materials_deprecated_do_not_use`.
 *
 * RISK: Low. If orphan copy is skipped or errors, rename won't happen
 *       — transactional boundary protects the database.
 *
 * ROLLBACK: Rename `materials_deprecated_do_not_use` back to `materials`.
 *           Copied rows stay in `material` (harmless duplicates if any).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('materials')) {
            \Log::info('[S1-08] materials table already absent. Nothing to do.');
            return;
        }

        // Step 1: count candidates
        $orphansToCopy = DB::select("
            SELECT COUNT(*) AS n
            FROM materials m
            LEFT JOIN material mm
                ON mm.link = m.link
               AND mm.description = m.description
               AND mm.qty = m.qty
            WHERE mm.id IS NULL
        ")[0]->n ?? 0;

        \Log::info("[S1-08] Candidate orphans to copy: {$orphansToCopy}");

        // Step 2: copy orphans (if any)
        if ($orphansToCopy > 0) {
            DB::statement("
                INSERT INTO material
                    (link, job_number, userId, phase, description, qty,
                     unit_of_measure, supplier, batch, created_at, updated_at)
                SELECT
                    m.link, m.job_number, m.userId, m.phase, m.description, m.qty,
                    m.unit_of_measure,
                    NULLIF(m.supplier, ''),
                    NULLIF(m.batch, ''),
                    m.created_at, m.updated_at
                FROM materials m
                LEFT JOIN material mm
                    ON mm.link = m.link
                   AND mm.description = m.description
                   AND mm.qty = m.qty
                WHERE mm.id IS NULL
            ");

            $copied = DB::select("SELECT ROW_COUNT() AS n")[0]->n ?? 0;
            \Log::info("[S1-08] Copied {$copied} orphan rows from materials to material.");
        }

        // Step 3: rename to make further writes fail loudly
        Schema::rename('materials', 'materials_deprecated_do_not_use');

        \Log::info('[S1-08] Renamed materials → materials_deprecated_do_not_use.');
    }

    public function down()
    {
        if (Schema::hasTable('materials_deprecated_do_not_use')) {
            Schema::rename('materials_deprecated_do_not_use', 'materials');
        }
        // We do NOT remove the copied rows from `material` — that would
        // be a destructive rollback. If a full restore is needed, use
        // a database backup.
    }
};
