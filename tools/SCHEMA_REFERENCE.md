# PeekTrack Schema Reference (AI-relevant tables)

_Generated 2026-05-02 from peektrackv2\_\_6_.sql.\_
Use this whenever writing SQL or DB code. Cross-check column names BEFORE writing, not after.

## Critical naming gotchas

Watch for these specifically — they bit Sprint 2 and Sprint 3:

| Column on...                          | NOT this                          | IS this                                                                 |
| ------------------------------------- | --------------------------------- | ----------------------------------------------------------------------- |
| `jobentries`                          | `user_id`                         | `userId` (camelCase)                                                    |
| `jobentries`                          | `notes`                           | (no such column — use `equipment_only_reason_text` or join `job_notes`) |
| `job_data`                            | `qty`                             | `est_qty`                                                               |
| `settings`                            | `description`                     | `notes`                                                                 |
| `production`, `material`, `equipment` | (cards have line items by `link`) | use `link` UUID, not `card_id`                                          |

## `jobentries`

| Column                       | Type           | Nullable | Default |
| ---------------------------- | -------------- | -------- | ------- |
| `id`                         | `bigint(20)`   | NO       | ``      |
| `link`                       | `char(36)`     | NO       | ``      |
| `job_number`                 | `varchar(255)` | NO       | ``      |
| `workdate`                   | `date`         | NO       | ``      |
| `submitted`                  | `int(11)`      | NO       | `0`     |
| `submitted_on`               | `date`         | YES      | `NULL`  |
| `userId`                     | `int(11)`      | NO       | ``      |
| `name`                       | `varchar(255)` | NO       | ``      |
| `created_at`                 | `timestamp`    | YES      | `NULL`  |
| `updated_at`                 | `timestamp`    | YES      | `NULL`  |
| `approved`                   | `int(11)`      | YES      | `NULL`  |
| `review_state`               | `varchar(32)`  | YES      | `NULL`  |
| `equipment_only_reason`      | `enum`         | YES      | `NULL`  |
| `equipment_only_reason_text` | `varchar(500)` | YES      | `NULL`  |
| `approvedBy`                 | `varchar(255)` | YES      | `NULL`  |
| `approved_date`              | `date`         | YES      | `NULL`  |
| `billing_approval`           | `tinyint(1)`   | YES      | `NULL`  |
| `billing_approval_by`        | `int(11)`      | YES      | `NULL`  |
| `billing_approval_at`        | `timestamp`    | YES      | `NULL`  |
| `kicked_back_at`             | `timestamp`    | YES      | `NULL`  |
| `kickback_count`             | `tinyint(3)`   | NO       | `0`     |
| `super_notified_at`          | `timestamp`    | YES      | `NULL`  |
| `manager_escalated_at`       | `timestamp`    | YES      | `NULL`  |

## `production`

| Column                | Type           | Nullable | Default |
| --------------------- | -------------- | -------- | ------- |
| `id`                  | `bigint(20)`   | NO       | ``      |
| `link`                | `char(36)`     | NO       | ``      |
| `job_number`          | `varchar(255)` | NO       | ``      |
| `userId`              | `int(11)`      | NO       | ``      |
| `phase`               | `varchar(255)` | NO       | ``      |
| `description`         | `varchar(255)` | NO       | ``      |
| `qty`                 | `double`       | NO       | ``      |
| `unit_of_measure`     | `varchar(255)` | NO       | ``      |
| `mark_mill`           | `varchar(255)` | YES      | `NULL`  |
| `road_name`           | `varchar(255)` | YES      | `NULL`  |
| `phase_item_complete` | `varchar(255)` | YES      | `NULL`  |
| `surface_type`        | `varchar(255)` | YES      | `NULL`  |
| `created_at`          | `timestamp`    | YES      | `NULL`  |
| `updated_at`          | `timestamp`    | YES      | `NULL`  |
| `deleted_at`          | `timestamp`    | YES      | `NULL`  |

## `material`

| Column            | Type           | Nullable | Default |
| ----------------- | -------------- | -------- | ------- |
| `id`              | `bigint(20)`   | NO       | ``      |
| `link`            | `char(36)`     | YES      | `NULL`  |
| `job_number`      | `varchar(20)`  | NO       | ``      |
| `userId`          | `int(11)`      | NO       | ``      |
| `phase`           | `varchar(255)` | NO       | ``      |
| `description`     | `varchar(255)` | NO       | ``      |
| `qty`             | `double(10,4)` | NO       | ``      |
| `unit_of_measure` | `varchar(255)` | NO       | ``      |
| `supplier`        | `varchar(255)` | YES      | `NULL`  |
| `batch`           | `varchar(255)` | YES      | `NULL`  |
| `created_at`      | `timestamp`    | YES      | `NULL`  |
| `updated_at`      | `timestamp`    | YES      | `NULL`  |
| `deleted_at`      | `timestamp`    | YES      | `NULL`  |

## `equipment`

| Column        | Type           | Nullable | Default |
| ------------- | -------------- | -------- | ------- |
| `id`          | `bigint(20)`   | NO       | ``      |
| `link`        | `char(36)`     | NO       | ``      |
| `job_number`  | `varchar(255)` | NO       | ``      |
| `userId`      | `int(11)`      | NO       | ``      |
| `phase`       | `varchar(255)` | NO       | ``      |
| `description` | `varchar(255)` | NO       | ``      |
| `truck`       | `varchar(255)` | NO       | ``      |
| `hours`       | `double`       | NO       | ``      |
| `created_at`  | `timestamp`    | YES      | `NULL`  |
| `updated_at`  | `timestamp`    | YES      | `NULL`  |
| `deleted_at`  | `timestamp`    | YES      | `NULL`  |

## `job_data`

| Column            | Type           | Nullable | Default |
| ----------------- | -------------- | -------- | ------- |
| `id`              | `bigint(20)`   | NO       | ``      |
| `job_id`          | `int(11)`      | YES      | `NULL`  |
| `job_number`      | `varchar(20)`  | NO       | ``      |
| `phase`           | `varchar(255)` | NO       | ``      |
| `description`     | `varchar(255)` | NO       | ``      |
| `est_qty`         | `double(16,2)` | NO       | ``      |
| `unit_of_measure` | `varchar(255)` | NO       | ``      |
| `created_at`      | `timestamp`    | YES      | `NULL`  |
| `updated_at`      | `timestamp`    | YES      | `NULL`  |

## `crew_types`

| Column       | Type           | Nullable | Default |
| ------------ | -------------- | -------- | ------- |
| `id`         | `bigint(20)`   | NO       | ``      |
| `name`       | `varchar(255)` | NO       | ``      |
| `value`      | `varchar(255)` | NO       | ``      |
| `created_at` | `timestamp`    | YES      | `NULL`  |
| `updated_at` | `timestamp`    | YES      | `NULL`  |

## `users`

| Column              | Type           | Nullable | Default               |
| ------------------- | -------------- | -------- | --------------------- |
| `id`                | `bigint(20)`   | NO       | ``                    |
| `name`              | `varchar(255)` | NO       | ``                    |
| `email`             | `varchar(255)` | YES      | `NULL`                |
| `role_id`           | `bigint(20)`   | NO       | ``                    |
| `email_verified_at` | `timestamp`    | YES      | `NULL`                |
| `password`          | `varchar(255)` | NO       | ``                    |
| `picture`           | `varchar(255)` | YES      | `NULL`                |
| `location`          | `varchar(255)` | YES      | `NULL`                |
| `phone`             | `varchar(255)` | YES      | `NULL`                |
| `remember_token`    | `varchar(100)` | YES      | `NULL`                |
| `created_at`        | `timestamp`    | YES      | `NULL`                |
| `updated_at`        | `timestamp`    | YES      | `current_timestamp()` |
| `class`             | `varchar(255)` | YES      | `NULL`                |
| `pay_rate`          | `tinyint(1)`   | YES      | `0`                   |
| `termDate`          | `varchar(25)`  | YES      | `NULL`                |
| `rehireDate`        | `varchar(25)`  | YES      | `NULL`                |
| `active`            | `int(1)`       | NO       | `1`                   |
| `manager_id`        | `bigint(20)`   | YES      | `NULL`                |
| `is_clocked_in`     | `tinyint(4)`   | NO       | `0`                   |
| `clocked_in_by`     | `bigint(20)`   | YES      | `NULL`                |

## `jobreviews`

| Column                | Type           | Nullable | Default |
| --------------------- | -------------- | -------- | ------- |
| `id`                  | `bigint(20)`   | NO       | ``      |
| `link`                | `char(36)`     | NO       | ``      |
| `job_number`          | `varchar(255)` | NO       | ``      |
| `reviewed_by`         | `varchar(255)` | NO       | ``      |
| `reviewed_by_user_id` | `bigint(20)`   | YES      | `NULL`  |
| `reviewed_by_system`  | `varchar(64)`  | YES      | `NULL`  |
| `decision`            | `varchar(32)`  | YES      | `NULL`  |
| `decision_reason`     | `text`         | YES      | `NULL`  |
| `date_reviewed`       | `datetime`     | NO       | ``      |
| `created_at`          | `timestamp`    | YES      | `NULL`  |
| `updated_at`          | `timestamp`    | YES      | `NULL`  |

## `audit_logs`

| Column       | Type           | Nullable | Default |
| ------------ | -------------- | -------- | ------- |
| `id`         | `bigint(20)`   | NO       | ``      |
| `event_type` | `varchar(255)` | NO       | ``      |
| `link`       | `varchar(255)` | YES      | `NULL`  |
| `old_value`  | `varchar(255)` | YES      | `NULL`  |
| `new_value`  | `varchar(255)` | YES      | `NULL`  |
| `user_id`    | `bigint(20)`   | NO       | ``      |
| `ip_address` | `varchar(255)` | YES      | `NULL`  |
| `created_at` | `timestamp`    | YES      | `NULL`  |
| `updated_at` | `timestamp`    | YES      | `NULL`  |

## `non_est_items`

| Column                | Type           | Nullable | Default |
| --------------------- | -------------- | -------- | ------- |
| `id`                  | `bigint(20)`   | NO       | ``      |
| `job_id`              | `int(11)`      | NO       | ``      |
| `userId`              | `int(11)`      | NO       | ``      |
| `phase`               | `varchar(255)` | NO       | ``      |
| `description`         | `varchar(255)` | NO       | ``      |
| `qty`                 | `double(8,2)`  | NO       | ``      |
| `unit_of_measure`     | `varchar(255)` | NO       | ``      |
| `date`                | `date`         | NO       | ``      |
| `mark_mill`           | `varchar(255)` | NO       | ``      |
| `road_name`           | `varchar(255)` | NO       | ``      |
| `phase_item_complete` | `varchar(255)` | NO       | ``      |
| `surface_type`        | `varchar(255)` | NO       | ``      |
| `created_at`          | `timestamp`    | YES      | `NULL`  |
| `updated_at`          | `timestamp`    | YES      | `NULL`  |

## `job_notes`

| Column       | Type           | Nullable | Default |
| ------------ | -------------- | -------- | ------- |
| `id`         | `bigint(20)`   | NO       | ``      |
| `link`       | `char(36)`     | NO       | ``      |
| `note_type`  | `varchar(255)` | NO       | ``      |
| `username`   | `varchar(255)` | NO       | ``      |
| `note`       | `longtext`     | YES      | `NULL`  |
| `created_at` | `timestamp`    | YES      | `NULL`  |
| `updated_at` | `timestamp`    | YES      | `NULL`  |

## `roles`

| Column        | Type           | Nullable | Default |
| ------------- | -------------- | -------- | ------- |
| `id`          | `bigint(20)`   | NO       | ``      |
| `name`        | `varchar(255)` | NO       | ``      |
| `description` | `text`         | YES      | `NULL`  |
| `created_at`  | `timestamp`    | YES      | `NULL`  |
| `updated_at`  | `timestamp`    | YES      | `NULL`  |

## `jobcard_ai_features`

| Column                           | Type           | Nullable | Default               |
| -------------------------------- | -------------- | -------- | --------------------- |
| `id`                             | `bigint(20)`   | NO       | ``                    |
| `link`                           | `char(36)`     | NO       | ``                    |
| `job_number`                     | `varchar(255)` | NO       | ``                    |
| `workdate`                       | `date`         | NO       | ``                    |
| `submitted_by_user_id`           | `int(10)`      | NO       | ``                    |
| `crew_type_id`                   | `bigint(20)`   | YES      | `NULL`                |
| `production_line_count`          | `int(11)`      | NO       | `0`                   |
| `production_total_qty`           | `double`       | NO       | `0`                   |
| `production_distinct_phases`     | `int(11)`      | NO       | `0`                   |
| `production_distinct_descs`      | `int(11)`      | NO       | `0`                   |
| `material_line_count`            | `int(11)`      | NO       | `0`                   |
| `material_total_qty`             | `double`       | NO       | `0`                   |
| `material_missing_supplier_cnt`  | `int(11)`      | NO       | `0`                   |
| `material_missing_batch_cnt`     | `int(11)`      | NO       | `0`                   |
| `material_distinct_suppliers`    | `int(11)`      | NO       | `0`                   |
| `equipment_line_count`           | `int(11)`      | NO       | `0`                   |
| `equipment_total_hours`          | `double`       | NO       | `0`                   |
| `equipment_distinct_trucks`      | `int(11)`      | NO       | `0`                   |
| `material_per_production`        | `double`       | YES      | `NULL`                |
| `equipment_hours_per_production` | `double`       | YES      | `NULL`                |
| `est_total_qty`                  | `double`       | YES      | `NULL`                |
| `production_vs_estimate_pct`     | `double`       | YES      | `NULL`                |
| `has_unestimated_items`          | `tinyint(1)`   | NO       | `0`                   |
| `unestimated_line_count`         | `int(11)`      | NO       | `0`                   |
| `has_complexity_override`        | `tinyint(1)`   | NO       | `0`                   |
| `qty_exceeds_soft_ceiling`       | `tinyint(1)`   | NO       | `0`                   |
| `qty_exceeds_hard_ceiling`       | `tinyint(1)`   | NO       | `0`                   |
| `pair_mismatch_required_count`   | `int(11)`      | NO       | `0`                   |
| `pair_mismatch_recommended_cnt`  | `int(11)`      | NO       | `0`                   |
| `prior_cards_same_job`           | `int(11)`      | NO       | `0`                   |
| `prior_cards_same_user_30d`      | `int(11)`      | NO       | `0`                   |
| `user_prior_rejection_rate`      | `double`       | YES      | `NULL`                |
| `user_30d_avg_material_qty`      | `double`       | YES      | `NULL`                |
| `user_30d_avg_equipment_hours`   | `double`       | YES      | `NULL`                |
| `has_hard_rule_violation`        | `tinyint(1)`   | NO       | `0`                   |
| `equipment_only_reason`          | `varchar(32)`  | YES      | `NULL`                |
| `equipment_only_reason_text`     | `varchar(500)` | YES      | `NULL`                |
| `notes_length`                   | `int(11)`      | NO       | `0`                   |
| `computed_at`                    | `timestamp`    | NO       | `current_timestamp()` |
| `feature_version`                | `varchar(16)`  | NO       | `'v1'`                |

## `production_material_pairs`

| Column                   | Type           | Nullable | Default      |
| ------------------------ | -------------- | -------- | ------------ |
| `id`                     | `bigint(20)`   | NO       | ``           |
| `production_pattern`     | `varchar(255)` | NO       | ``           |
| `match_mode`             | `enum`         | NO       | `'contains'` |
| `expected_material_code` | `varchar(64)`  | NO       | ``           |
| `is_prefix`              | `tinyint(1)`   | NO       | `0`          |
| `severity`               | `enum`         | NO       | `'required'` |
| `active`                 | `tinyint(1)`   | NO       | `1`          |
| `notes`                  | `varchar(500)` | YES      | `NULL`       |
| `created_at`             | `timestamp`    | YES      | `NULL`       |
| `updated_at`             | `timestamp`    | YES      | `NULL`       |

## `production_qty_limits`

| Column               | Type           | Nullable | Default             |
| -------------------- | -------------- | -------- | ------------------- |
| `id`                 | `bigint(20)`   | NO       | ``                  |
| `production_pattern` | `varchar(255)` | NO       | ``                  |
| `match_mode`         | `enum`         | NO       | `'contains'`        |
| `unit_of_measure`    | `varchar(32)`  | NO       | ``                  |
| `soft_max`           | `double`       | YES      | `NULL`              |
| `hard_max`           | `double`       | YES      | `NULL`              |
| `daily_max`          | `double`       | YES      | `NULL`              |
| `source`             | `enum`         | NO       | `'client_provided'` |
| `active`             | `tinyint(1)`   | NO       | `1`                 |
| `notes`              | `varchar(500)` | YES      | `NULL`              |
| `created_at`         | `timestamp`    | YES      | `NULL`              |
| `updated_at`         | `timestamp`    | YES      | `NULL`              |

## `crew_type_ratio_bands`

| Column         | Type           | Nullable | Default             |
| -------------- | -------------- | -------- | ------------------- |
| `id`           | `bigint(20)`   | NO       | ``                  |
| `crew_type_id` | `bigint(20)`   | NO       | ``                  |
| `metric`       | `enum`         | NO       | ``                  |
| `lower_bound`  | `double`       | YES      | `NULL`              |
| `upper_bound`  | `double`       | YES      | `NULL`              |
| `source`       | `enum`         | NO       | `'client_provided'` |
| `active`       | `tinyint(1)`   | NO       | `1`                 |
| `notes`        | `varchar(500)` | YES      | `NULL`              |
| `created_at`   | `timestamp`    | YES      | `NULL`              |
| `updated_at`   | `timestamp`    | YES      | `NULL`              |

## `crew_type_material_qty_limits`

| Column            | Type           | Nullable | Default             |
| ----------------- | -------------- | -------- | ------------------- |
| `id`              | `bigint(20)`   | NO       | ``                  |
| `crew_type_id`    | `bigint(20)`   | NO       | ``                  |
| `material_type`   | `varchar(64)`  | NO       | ``                  |
| `unit_of_measure` | `varchar(32)`  | NO       | ``                  |
| `goal`            | `double`       | YES      | `NULL`              |
| `soft_max`        | `double`       | YES      | `NULL`              |
| `hard_max`        | `double`       | YES      | `NULL`              |
| `source`          | `enum`         | NO       | `'client_provided'` |
| `active`          | `tinyint(1)`   | NO       | `1`                 |
| `notes`           | `varchar(500)` | YES      | `NULL`              |
| `created_at`      | `timestamp`    | YES      | `NULL`              |
| `updated_at`      | `timestamp`    | YES      | `NULL`              |

## ⚠️ `ai_scoring_audit` — TABLE NOT FOUND IN SCHEMA DUMP

## `settings`

| Column       | Type           | Nullable | Default               |
| ------------ | -------------- | -------- | --------------------- |
| `key_name`   | `varchar(128)` | NO       | ``                    |
| `value`      | `text`         | NO       | ``                    |
| `value_type` | `enum`         | NO       | ``                    |
| `notes`      | `varchar(512)` | YES      | `NULL`                |
| `updated_by` | `bigint(20)`   | YES      | `NULL`                |
| `updated_at` | `timestamp`    | NO       | `current_timestamp()` |
