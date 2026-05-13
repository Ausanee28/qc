# Database Schema Snapshot (qc3 Latest)

- Generated at: 2026-05-13 Asia/Bangkok
- Driver: mariadb
- Database: qc3
- Source: live database via Laravel DB connection and `information_schema`
- Table count: 13
- Data note: row counts below are exact `COUNT(*)` values at generation time; this file does not include row data dumps.

## Table List

| Table | Rows |
|---|---:|
| `Audit_Logs` | 2101 |
| `Departments` | 8 |
| `Equipments` | 9 |
| `External_Users` | 17 |
| `Internal_Users` | 15 |
| `migrations` | 39 |
| `performance_daily_inspector_aggregates` | 68 |
| `Production_Lines` | 18 |
| `report_daily_aggregates` | 317 |
| `report_monthly_aggregates` | 300 |
| `Test_Methods` | 21 |
| `Transaction_Detail` | 1144 |
| `Transaction_Header` | 814 |

## Core Workflow Tables

### `Transaction_Header`

Stores received job headers. `line` remains a text value for historical compatibility; it is populated from `Production_Lines.line_name` but is not a foreign key.

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `transaction_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `external_id` | `int(10) unsigned` | NO | MUL | `NULL` | - |
| `internal_id` | `int(10) unsigned` | YES | MUL | `NULL` | - |
| `sender_leader` | `varchar(255)` | YES | - | `NULL` | - |
| `detail` | `text` | YES | MUL | `NULL` | - |
| `dmc` | `varchar(255)` | YES | - | `NULL` | - |
| `cell` | `varchar(255)` | YES | - | `NULL` | - |
| `line` | `varchar(255)` | YES | - | `NULL` | - |
| `shift` | `varchar(255)` | YES | - | `NULL` | - |
| `model` | `varchar(255)` | YES | - | `NULL` | - |
| `receive_date` | `datetime` | NO | MUL | `current_timestamp()` | - |
| `return_date` | `datetime` | YES | MUL | `NULL` | - |
| `deleted_at` | `timestamp` | YES | MUL | `NULL` | - |

**Indexes:** `PRIMARY(transaction_id)`, `transaction_header_external_id_foreign(external_id)`, `transaction_header_internal_id_foreign(internal_id)`, `idx_dates(receive_date, return_date)`, `idx_th_deleted_at(deleted_at)`, `idx_th_deleted_receive(deleted_at, receive_date)`, `idx_th_return_deleted_receive(return_date, deleted_at, receive_date)`, `ft_th_search(detail, dmc, line)`.

**Foreign Keys:** `external_id -> External_Users.external_id`, `internal_id -> Internal_Users.user_id`.

### `Transaction_Detail`

Stores individual test result rows for each received job.

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `detail_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `transaction_id` | `int(10) unsigned` | NO | MUL | `NULL` | - |
| `method_id` | `int(10) unsigned` | NO | MUL | `NULL` | - |
| `internal_id` | `int(10) unsigned` | NO | MUL | `NULL` | - |
| `start_time` | `datetime` | YES | - | `NULL` | - |
| `end_time` | `datetime` | NO | - | `current_timestamp()` | - |
| `duration_sec` | `int(11)` | YES | - | `NULL` | - |
| `max_value` | `varchar(255)` | YES | - | `NULL` | - |
| `min_value` | `varchar(255)` | YES | - | `NULL` | - |
| `judgement` | `varchar(255)` | YES | MUL | `NULL` | - |
| `remark` | `varchar(255)` | YES | MUL | `NULL` | - |
| `deleted_at` | `timestamp` | YES | MUL | `NULL` | - |

**Indexes:** `PRIMARY(detail_id)`, foreign-key indexes on `transaction_id`, `method_id`, and `internal_id`, performance indexes on `deleted_at`, `start_time`, `end_time`, `judgement`, and `ft_td_search(remark, max_value, min_value)`.

**Foreign Keys:** `transaction_id -> Transaction_Header.transaction_id`, `method_id -> Test_Methods.method_id`, `internal_id -> Internal_Users.user_id`.

## Master Data Tables

### `Departments`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `department_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `department_name` | `varchar(100)` | NO | MUL | `NULL` | - |
| `internal_phone` | `varchar(255)` | YES | - | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes:** `PRIMARY(department_id)`, `idx_departments_name(department_name)`, `idx_departments_active_name(is_active, department_name)`.

### `Equipments`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `equipment_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `equipment_name` | `varchar(100)` | YES | MUL | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes:** `PRIMARY(equipment_id)`, `idx_equipments_name(equipment_name)`, `idx_equipments_active_name(is_active, equipment_name)`.

### `External_Users`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `external_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `external_name` | `varchar(100)` | NO | MUL | `NULL` | - |
| `department_id` | `int(10) unsigned` | YES | MUL | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes:** `PRIMARY(external_id)`, `idx_external_users_name(external_name)`, `idx_external_users_dept_name(department_id, external_name)`, `idx_external_users_active_name(is_active, external_name)`, `ft_eu_name(external_name)`.

**Foreign Keys:** `department_id -> Departments.department_id`.

### `Internal_Users`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `user_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `user_name` | `varchar(255)` | NO | - | `NULL` | - |
| `user_password` | `varchar(255)` | YES | - | `NULL` | - |
| `employee_id` | `varchar(255)` | YES | - | `NULL` | - |
| `name` | `varchar(100)` | YES | MUL | `NULL` | - |
| `role` | `varchar(50)` | YES | - | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes:** `PRIMARY(user_id)`, `idx_internal_users_name(name)`, `idx_internal_users_active_name(is_active, name)`, `internal_users_is_active_idx(is_active)`, `ft_iu_name(name)`.

### `Production_Lines`

Admin-managed line options for the Receive Job line dropdown.

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `line_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `line_name` | `varchar(255)` | NO | UNI | `NULL` | - |
| `sort_order` | `int(10) unsigned` | NO | - | `0` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes:** `PRIMARY(line_id)`, `production_lines_line_name_unique(line_name)`, `idx_production_lines_active_order_name(is_active, sort_order, line_name)`.

**Foreign Keys:** None.

### `Test_Methods`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `method_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `method_name` | `varchar(100)` | NO | MUL | `NULL` | - |
| `equipment_id` | `int(10) unsigned` | YES | MUL | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes:** `PRIMARY(method_id)`, `test_methods_equipment_id_foreign(equipment_id)`, `idx_test_methods_name(method_name)`, `idx_test_methods_active_name(is_active, method_name)`, `ft_tm_name(method_name)`.

**Foreign Keys:** `equipment_id -> Equipments.equipment_id` with `ON DELETE SET NULL`.

## Reporting And Audit Tables

### `Audit_Logs`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `audit_id` | `bigint(20) unsigned` | NO | PRI | `NULL` | auto_increment |
| `module` | `varchar(50)` | NO | MUL | `NULL` | - |
| `action` | `varchar(20)` | NO | - | `NULL` | - |
| `record_type` | `varchar(80)` | NO | MUL | `NULL` | - |
| `record_id` | `int(10) unsigned` | YES | - | `NULL` | - |
| `performed_by` | `int(10) unsigned` | YES | MUL | `NULL` | - |
| `performed_by_name` | `varchar(120)` | YES | - | `NULL` | - |
| `before_data` | `longtext` | YES | - | `NULL` | - |
| `after_data` | `longtext` | YES | - | `NULL` | - |
| `created_at` | `timestamp` | NO | MUL | `current_timestamp()` | - |

**Indexes:** `PRIMARY(audit_id)`, `idx_audit_actor(performed_by)`, `idx_audit_created_at(created_at)`, `idx_audit_module_action(module, action)`, `idx_audit_record(record_type, record_id)`.

**Foreign Keys:** `performed_by -> Internal_Users.user_id` with `ON DELETE SET NULL`.

### `performance_daily_inspector_aggregates`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `date_key` | `date` | NO | PRI | `NULL` | - |
| `month_key` | `varchar(7)` | NO | MUL | `NULL` | - |
| `internal_id` | `int(10) unsigned` | NO | PRI | `NULL` | - |
| `total_tests` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ok_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ng_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `duration_total_sec` | `bigint(20) unsigned` | NO | - | `0` | - |
| `duration_samples` | `bigint(20) unsigned` | NO | - | `0` | - |
| `min_duration_sec` | `int(10) unsigned` | YES | - | `NULL` | - |
| `max_duration_sec` | `int(10) unsigned` | YES | - | `NULL` | - |
| `aggregated_at` | `timestamp` | YES | - | `NULL` | - |

**Indexes:** `PRIMARY(date_key, internal_id)`, `idx_pdia_month_internal(month_key, internal_id)`.

### `report_daily_aggregates`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `date_key` | `date` | NO | PRI | `NULL` | - |
| `month_key` | `varchar(7)` | NO | MUL | `NULL` | - |
| `dmc` | `varchar(255)` | NO | PRI | `''` | - |
| `total_rows` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ok_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ng_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `aggregated_at` | `timestamp` | YES | - | `NULL` | - |

**Indexes:** `PRIMARY(date_key, dmc)`, `idx_rda_month_dmc(month_key, dmc)`.

### `report_monthly_aggregates`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `month_key` | `varchar(7)` | NO | PRI | `NULL` | - |
| `dmc` | `varchar(255)` | NO | PRI | `''` | - |
| `total_rows` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ok_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ng_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `aggregated_at` | `timestamp` | YES | - | `NULL` | - |

**Indexes:** `PRIMARY(month_key, dmc)`.

## Laravel Runtime Table

### `migrations`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `migration` | `varchar(255)` | NO | - | `NULL` | - |
| `batch` | `int(11)` | NO | - | `NULL` | - |

**Indexes:** `PRIMARY(id)`.

## Notes

- `Production_Lines` is the current source for active Receive Job line options.
- `Transaction_Header.line` deliberately remains nullable text, so old job history remains readable even if a line is later deactivated or renamed.
- Master data tables use `is_active` for deactivate/reactivate behavior instead of destructive deletes.
- This document is the single latest database schema reference. Older dated schema files are retained as historical snapshots.
