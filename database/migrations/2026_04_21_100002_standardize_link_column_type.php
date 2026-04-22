<?php

/**
 * SPRINT 1 TICKET S1-02: Standardize `link` column type across tables.
 *
 * WHY:
 *   - jobentries.link is CHAR(36)
 *   - material.link is declared as uuid (MariaDB 10.7+ native UUID type)
 *   - production, equipment, jobreviews.link are all CHAR(36)
 *
 *   When joining across mismatched column types, MySQL/MariaDB does an
 *   implicit cast per row which: (a) prevents index usage in some cases,
 *   (b) is slow, (c) can produce wrong results under exotic collations.
 *
 *   We standardize ALL `link` columns to CHAR(36) with utf8mb4_bin
 *   collation. utf8mb4_bin is exact-match and case-sensitive, which is
 *   what we want for UUIDs (and it's the fastest collation for this type
 *   of lookup).
 *
 * RISK: Low. Column type change on a populated table requires a copy
 *       under the hood on MySQL 5.7 (InnoDB online DDL only for certain
 *       type changes). On MariaDB 10.4+ CHAR length changes are online.
 *       Run during off-hours as a precaution. Test on staging first.
 *
 * DEPENDENCIES: Run AFTER S1-01 (indexes), because we rebuild them here.
 *
 * ROLLBACK: Change type back. Note: if material.link had non-string UUID
 *           data, converting back to the `uuid` type may require data
 *           inspection.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Raw SQL because Doctrine DBAL doesn't cleanly express MariaDB's
        // native `uuid` type transitions and we want exact control over
        // the resulting column definition (including collation).
        //
        // IMPORTANT: these statements cast any existing UUID values to
        // their CHAR(36) canonical string form (e.g. "550e8400-e29b-..."),
        // which is exactly what jobentries.link already stores.

        DB::statement("
            ALTER TABLE material
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
            NULL
        ");

        // The other line-item tables are already CHAR(36) but may have
        // inconsistent collations. Normalize them.
        DB::statement("
            ALTER TABLE jobentries
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
            NOT NULL
        ");

        DB::statement("
            ALTER TABLE production
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
            NOT NULL
        ");

        DB::statement("
            ALTER TABLE equipment
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
            NOT NULL
        ");

        DB::statement("
            ALTER TABLE jobreviews
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
            NOT NULL
        ");
    }

    public function down()
    {
        // Revert collation only — we don't restore the `uuid` type on
        // material.link because that transition is lossy.
        DB::statement("
            ALTER TABLE material
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            NULL
        ");

        DB::statement("
            ALTER TABLE jobentries
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            NOT NULL
        ");

        DB::statement("
            ALTER TABLE production
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            NOT NULL
        ");

        DB::statement("
            ALTER TABLE equipment
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            NOT NULL
        ");

        DB::statement("
            ALTER TABLE jobreviews
            MODIFY link CHAR(36)
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            NOT NULL
        ");
    }
};
