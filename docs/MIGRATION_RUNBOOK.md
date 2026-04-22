# PeekTrack AI — Sprint 1 Migration Runbook

Everything you need to deploy Sprint 1 safely on staging and prod.

**Read this file start-to-finish before running anything.**

---

## Contents

1. [What Sprint 1 delivers](#what-sprint-1-delivers)
2. [Order of operations](#order-of-operations)
3. [Per-migration reference](#per-migration-reference)
4. [Rollback procedures](#rollback-procedures)
5. [Verification checklist](#verification-checklist)
6. [Troubleshooting](#troubleshooting)

---

## What Sprint 1 delivers

Schema preparation for the PeekTrack AI project. After Sprint 1 is complete, the database is ready for Sprint 2 (feature-layer implementation). No AI code runs yet. No user-visible changes.

Eight deliverables in this sprint:

| ID | Type | What it does |
|----|------|--------------|
| S1-01 | Migration | Add `link` indexes to `production`, `material`, `equipment`, `jobreviews` |
| S1-02 | Migration | Standardize `link` column to `CHAR(36)` `utf8mb4_bin` across tables |
| S1-03 | Migration | Add `review_state` to `jobentries` + backfill from legacy `approved` |
| S1-04 | Migration | Add `reviewed_by_user_id`, `reviewed_by_system`, `decision`, `decision_reason` to `jobreviews` |
| S1-05 | Migration | Add `deleted_at` soft-delete to `production`, `material`, `equipment` |
| S1-06 | Migration | Create `settings` table |
| S1-07 | Artisan cmd | `ai:audit-materials` — codebase + DB audit for deprecated `materials` table |
| S1-08 | Migration | Migrate orphan rows `materials` → `material`, rename `materials` to `materials_deprecated_do_not_use` |
| S1-09 | SQL script | Benchmark harness for the feature query |
| S1-10 | Doc | This file |
| S1-11 | SQL script | Canonical feature-aggregation query |
| — | Seeder | `AiSettingsSeeder` — 27 rows of AI feature flags and thresholds (all safe-off) |

---

## Order of operations

Do not skip steps. Do not reorder.

### Phase A — Staging dry run (required)

1. Take a fresh staging snapshot from prod.
2. `cd` into the PeekTrack root.
3. Run `git pull` on the AI-sprint-1 branch.
4. `composer install` (Symfony Finder is needed by the audit command).
5. Run migrations 1 through 6 in order. **Do not run migration 7 (S1-08) yet.**
6. Run the settings seeder: `php artisan db:seed --class=AiSettingsSeeder`.
7. Run the audit: `php artisan ai:audit-materials`.
8. Review the audit CSV in `storage/app/`. **If any references are found in app code, stop and fix them.** Do not proceed.
9. Run migration 7 (S1-08).
10. Run `sql/benchmark_feature_query.sql` against the staging DB.
11. Confirm the benchmark reports **PASS** (p95 < 200 ms).
12. Smoke-test the app: submit a job card, approve one, reject one. All must still work.

### Phase B — Production deploy

1. Announce a 30-minute maintenance window (the S1-02 column-type change can momentarily lock tables on older MySQL).
2. Take a prod database backup. Confirm the backup file exists and is non-zero bytes.
3. Set the app to maintenance mode: `php artisan down --retry=60`.
4. Deploy the code.
5. Run migrations 1 through 6 in order.
6. Seed settings: `php artisan db:seed --class=AiSettingsSeeder --force`.
7. Run the audit: `php artisan ai:audit-materials`. **If this finds anything, restore from backup and investigate.** The staging audit should have caught everything — a prod finding means staging and prod have drifted.
8. Run migration 7 (S1-08).
9. Bring the app back up: `php artisan up`.
10. Run the benchmark against prod. Save the output.

---

## Per-migration reference

### S1-01 · `2026_04_21_100001_add_link_indexes_to_line_item_tables.php`

**Adds:** B-tree index on `link` for `production`, `material`, `equipment`, `jobreviews`.

**Time on prod-sized data:** ~5 seconds per table on InnoDB. Online (no write lock) on MySQL 5.6+ / MariaDB 10.x.

**Data impact:** None. Read-only schema metadata change.

**Rollback:** `php artisan migrate:rollback --step=1` when this is the most-recent migration. Safe to rollback at any time — dropping indexes does not remove data.

---

### S1-02 · `2026_04_21_100002_standardize_link_column_type.php`

**Modifies:** `link` column on `jobentries`, `production`, `material`, `equipment`, `jobreviews` to `CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin`.

**Time on prod-sized data:** 30s–5min depending on table size and MySQL version. `material.link` conversion from `uuid` type is the slowest because it copies the table.

**⚠ IMPORTANT:** On MySQL 5.7 this ALTER is NOT online — it briefly locks the table for writes. Run during the maintenance window. On MariaDB 10.4+ (what PeekTrack runs) these are online operations.

**Data impact:** None. UUID values in `material.link` are cast to their canonical 36-char string form, which is the same representation already used in `jobentries.link`.

**Rollback:** Reverts collation to `utf8mb4_unicode_ci`. Does NOT restore the `uuid` native type on `material.link` — that cast is considered acceptable because the string form is lossless.

---

### S1-03 · `2026_04_21_100003_add_review_state_to_jobentries.php`

**Adds:** `review_state VARCHAR(32)` column + index. Backfills from existing `approved` int.

**Backfill rules:**

| Source | → | Result |
|--------|---|--------|
| `submitted = 0` | → | `NULL` (not yet submitted) |
| `submitted = 1` AND `approved IS NULL` | → | `pending_ai` |
| `submitted = 1` AND `approved = 1` | → | `human_approved` |
| `submitted = 1` AND `approved = 2` | → | `human_rejected` |
| `submitted = 1` AND `approved = 4` | → | `NULL` + Laravel log entry |

The `approved = 4` case is the overflow-related state from `JobsController` and is deliberately not mapped here — the overflow team needs to weigh in on what that value represents before we assign it a review state.

**Time on prod-sized data:** ~3 seconds.

**Data impact:** Pure additive. Legacy `approved` column unchanged. Existing controllers continue to work.

**Rollback:** `dropColumn('review_state')` + drop index. Zero data loss (the column is backfilled data, original `approved` is source of truth).

---

### S1-04 · `2026_04_21_100004_add_reviewer_identity_to_jobreviews.php`

**Adds:** `reviewed_by_user_id`, `reviewed_by_system`, `decision`, `decision_reason` to `jobreviews`.

**Backfills:** `reviewed_by_user_id` where there is EXACTLY ONE user with a matching name. Ambiguous matches (two users with the same name) are intentionally left NULL. The log entry at the end of the migration reports resolved vs. unresolved counts.

**Data impact:** Additive only. Legacy `reviewed_by` varchar untouched.

**Rollback:** `dropColumn` on the four new columns + indexes.

---

### S1-05 · `2026_04_21_100005_add_soft_deletes_to_line_item_tables.php`

**Adds:** `deleted_at TIMESTAMP NULL` to `production`, `material`, `equipment`.

**Data impact:** None at migration time. The column is NULL for all existing rows. Behavior changes only when the corresponding Eloquent models are updated to use the `SoftDeletes` trait in Sprint 2.

**Rollback:** Laravel's `dropSoftDeletes()`.

---

### S1-06 · `2026_04_21_100006_create_settings_table.php`

**Creates:** `settings` table (empty).

**Data impact:** None. Table is empty until the seeder runs.

**Rollback:** `dropIfExists('settings')`.

---

### S1-07 · `php artisan ai:audit-materials`

**Not a migration.** A read-only Artisan command. Run it before S1-08.

**Output:**
- Table showing row counts in `material` vs `materials`.
- Last-write timestamps for each.
- Orphan count (rows in `materials` with no `(link, description)` match in `material`).
- Grep results across PHP, Blade, Vue, JS, TS files for any references to the deprecated `materials` table.
- CSV at `storage/app/materials-audit-<timestamp>.csv`.

**Exit code 0:** clean — safe to run S1-08.
**Exit code 1:** (with `--fail-on-refs`) app code still references `materials`. Fix those code paths before running S1-08.

**What to do if the audit finds references:**
1. Review the CSV.
2. For each reference, decide: is it a read, a write, or a declaration (model class, migration)?
3. Writes must be switched to `material`.
4. Reads should be switched to `material` unless there's a specific reason (there shouldn't be).
5. Model declarations: update the `$table` property or delete the model if unused.
6. Re-run the audit until it comes back clean.

---

### S1-08 · `2026_04_21_100007_migrate_and_rename_materials_table.php`

**Migrates:** rows from `materials` → `material` where the `(link, description, qty)` tuple doesn't already exist on the canonical side.

**Renames:** `materials` → `materials_deprecated_do_not_use`. Any future code that tries to write to `materials` will throw a `Table doesn't exist` error, which is what we want — it fails loudly instead of silently writing to a dead table.

**Time on prod-sized data:** Depends on orphan count. Expected: < 30 seconds.

**Data impact:** Rows are copied, not moved. The source table is renamed but its rows still exist in `materials_deprecated_do_not_use`. Nothing is deleted.

**PRECONDITION:** S1-07 audit is clean. The migration does NOT re-check the codebase — it assumes you did.

**Rollback:** Rename `materials_deprecated_do_not_use` back to `materials`. Copied rows stay in `material` (they're not removed — removing them is a restore-from-backup operation).

---

### S1-09 · `sql/benchmark_feature_query.sql`

**Not a migration.** A SQL script the DBA runs manually to benchmark the feature query on prod-sized data.

See the file's header comment for usage.

**Expected result on prod-sized staging data after S1-01 + S1-02:**
- p50 < 30 ms
- p95 < 200 ms (hard requirement for Sprint 2 unblock)
- p99 < 500 ms

If any of these fail, see Troubleshooting below.

---

### S1-11 · `sql/build_feature_row.sql`

**Not a migration.** A SQL script that builds a single feature-row preview for a given jobcard. Used by the DBA and engineers to validate the aggregation logic before Sprint 2 wraps it in Laravel.

---

### Seeder · `AiSettingsSeeder`

27 rows seeded. All AI-related feature flags default to OFF. All tunable thresholds default to safe values that the team will revisit during the pilot in Sprint 8.

Run: `php artisan db:seed --class=AiSettingsSeeder`

Idempotent — can be run multiple times safely (uses `updateOrInsert`).

---

## Rollback procedures

### Full-sprint rollback (nuclear option)

In order, most-recent first:

```bash
php artisan migrate:rollback --step=7
```

That reverses all 7 migrations in LIFO order. The seeder data stays in `settings` but that's fine — the table will be dropped by the final rollback step.

### Partial rollback (preferred for most incidents)

If only one migration has a problem, roll back just it:

```bash
# See what's applied
php artisan migrate:status

# Roll back exactly one most-recent migration
php artisan migrate:rollback --step=1

# Roll back N most-recent migrations
php artisan migrate:rollback --step=N
```

### Restore-from-backup (last resort)

If a migration damaged data in a non-rollback-able way (shouldn't happen with Sprint 1, but documenting for process):

1. Stop the app: `php artisan down`
2. Restore the pre-deploy DB backup.
3. Deploy the previous application version.
4. Bring the app back up.

---

## Verification checklist

Run through this after a successful deploy. Every item should pass.

### Schema verification

```sql
-- Expect 4 rows, one per table.
SELECT table_name, index_name, column_name
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND column_name = 'link'
  AND index_name LIKE 'idx_%_link';

-- Expect CHAR(36) utf8mb4_bin on every row.
SELECT table_name, column_name, data_type, character_maximum_length, collation_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name = 'link';

-- Expect review_state to exist.
SHOW COLUMNS FROM jobentries LIKE 'review_state';

-- Expect 4 new columns on jobreviews.
SHOW COLUMNS FROM jobreviews LIKE 'reviewed_by_%';
SHOW COLUMNS FROM jobreviews LIKE 'decision%';

-- Expect deleted_at on each.
SHOW COLUMNS FROM production LIKE 'deleted_at';
SHOW COLUMNS FROM material   LIKE 'deleted_at';
SHOW COLUMNS FROM equipment  LIKE 'deleted_at';

-- Expect materials to be renamed.
SHOW TABLES LIKE 'materials%';
-- Should show: materials_deprecated_do_not_use (not: materials)

-- Expect settings table with 27 rows.
SELECT COUNT(*) FROM settings;
```

### Data-integrity verification

```sql
-- Every submitted jobentry should have a review_state (except overflow=4).
SELECT
    COUNT(*) AS total_submitted,
    SUM(review_state IS NOT NULL) AS has_state,
    SUM(review_state IS NULL AND approved = 4) AS legacy_overflow,
    SUM(review_state IS NULL AND approved <> 4) AS unexpected_null
FROM jobentries
WHERE submitted = 1;
-- unexpected_null should be 0.

-- jobreviews backfill coverage.
SELECT
    COUNT(*) AS total,
    SUM(reviewed_by_user_id IS NOT NULL) AS matched,
    SUM(reviewed_by_user_id IS NULL)     AS unmatched
FROM jobreviews;
-- matched / total >= 0.70 is a healthy baseline.

-- All settings seeded.
SELECT key_name, value, value_type FROM settings ORDER BY key_name;
-- Expect 27 rows. Every ai.enabled* flag should be 'false'.
```

### App smoke test

1. Log in as a superintendent.
2. Open an existing job card. All line items render.
3. Create a new job card. Submit it. It appears in the reviewer queue.
4. Log in as a reviewer.
5. Approve one card. Refresh — `jobentries.approved = 1` AND `review_state = 'human_approved'`.
6. Reject one card. Refresh — `jobentries.approved = 2` AND `review_state = 'human_rejected'`.

### Performance verification

Run `sql/benchmark_feature_query.sql`. It should print **PASS**.

---

## Troubleshooting

### `ai:audit-materials` finds references in a file you can't easily change

Typical culprits and how to handle:

- **Legacy reporting script** — if it's only-read, switch the table name to `material` and confirm it still returns the same data (it should, since the canonical data has been in `material` all along).
- **An old Laravel Model class** (e.g., `App\Models\Materials`) — delete it if unused, or fix its `$table` property.
- **A Blade view with a raw SQL query** — fix it, or route it through a Model.
- **A database view** — check `SHOW FULL TABLES WHERE Table_type = 'VIEW'`. If a view selects from `materials`, recreate it selecting from `material` before running S1-08.

### Benchmark p95 exceeds 200ms

Diagnostic steps:

1. Confirm indexes exist:
   ```sql
   SHOW INDEX FROM production WHERE Key_name = 'idx_production_link';
   SHOW INDEX FROM material   WHERE Key_name = 'idx_material_link';
   SHOW INDEX FROM equipment  WHERE Key_name = 'idx_equipment_link';
   ```
   If any are missing, S1-01 didn't apply — investigate the migration log.

2. Refresh table statistics:
   ```sql
   ANALYZE TABLE production, material, equipment, jobentries;
   ```
   Then re-run the benchmark. Stale stats can cause the optimizer to pick a bad plan.

3. Check the EXPLAIN output at the bottom of the benchmark script:
   - `type=ref` or `type=eq_ref` on the joins → index is being used.
   - `type=ALL` → table scan. Index isn't being used. Likely cause: collation mismatch on `link`. Check the output of the collation verification query above.

4. If p95 is still high, the issue is probably row count — check `SHOW TABLE STATUS` for the line-item tables. If any has 5M+ rows, we may need to tune further in Sprint 2 (e.g., add a composite index on `(link, deleted_at)`).

### A migration hangs

If S1-02 hangs for more than 10 minutes on a single table:

1. In another connection: `SHOW PROCESSLIST;`
2. Look for a `Waiting for table metadata lock` state.
3. Find what holds the lock and kill it (usually a long-running `SELECT`).
4. `KILL <process_id>;`
5. The migration should resume.

### S1-07 reports orphan rows but S1-08 says 0 copied

This is expected if the orphans are duplicates of rows that already exist in `material` via the `(link, description, qty)` match. It means someone at some point ran a sync and the orphans in `materials` are just stragglers. Nothing to do.

### Seeder fails with "Class AiSettingsSeeder not found"

The seeder hasn't been autoloaded yet:

```bash
composer dump-autoload
php artisan db:seed --class=AiSettingsSeeder
```
