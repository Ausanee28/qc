# Database Schema Reference (qc3)

- Generated at: 2026-04-08 09:46:14
- Driver: mariadb
- Database: qc3
- Table count: 12

## Table List

| Table | Approx. rows |
|---|---:|
| `Audit_Logs` | 62 |
| `Departments` | 5 |
| `Equipments` | 7 |
| `External_Users` | 3 |
| `Internal_Users` | 2 |
| `migrations` | 30 |
| `performance_daily_inspector_aggregates` | 2 |
| `report_daily_aggregates` | 1 |
| `report_monthly_aggregates` | 1 |
| `Test_Methods` | 1 |
| `Transaction_Detail` | 8 |
| `Transaction_Header` | 3 |

## Table Details

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

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `idx_audit_actor` | No | BTREE | `performed_by` |
| `idx_audit_created_at` | No | BTREE | `created_at` |
| `idx_audit_module_action` | No | BTREE | `module`, `action` |
| `idx_audit_record` | No | BTREE | `record_type`, `record_id` |
| `PRIMARY` | Yes | BTREE | `audit_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `fk_audit_logs_user` | `performed_by` | `Internal_Users.user_id` | RESTRICT | SET NULL |

### `Departments`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `department_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `department_name` | `varchar(100)` | NO | MUL | `NULL` | - |
| `internal_phone` | `varchar(255)` | YES | - | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `idx_departments_active_name` | No | BTREE | `is_active`, `department_name` |
| `idx_departments_name` | No | BTREE | `department_name` |
| `PRIMARY` | Yes | BTREE | `department_id` |

**Foreign Keys**

None

### `Equipments`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `equipment_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `equipment_name` | `varchar(100)` | YES | MUL | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `idx_equipments_active_name` | No | BTREE | `is_active`, `equipment_name` |
| `idx_equipments_name` | No | BTREE | `equipment_name` |
| `PRIMARY` | Yes | BTREE | `equipment_id` |

**Foreign Keys**

None

### `External_Users`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `external_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `external_name` | `varchar(100)` | NO | MUL | `NULL` | - |
| `department_id` | `int(10) unsigned` | YES | MUL | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `ft_eu_name` | No | FULLTEXT | `external_name` |
| `idx_external_users_active_name` | No | BTREE | `is_active`, `external_name` |
| `idx_external_users_dept_name` | No | BTREE | `department_id`, `external_name` |
| `idx_external_users_name` | No | BTREE | `external_name` |
| `PRIMARY` | Yes | BTREE | `external_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `external_users_department_id_foreign` | `department_id` | `Departments.department_id` | RESTRICT | RESTRICT |

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

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `ft_iu_name` | No | FULLTEXT | `name` |
| `idx_internal_users_active_name` | No | BTREE | `is_active`, `name` |
| `idx_internal_users_name` | No | BTREE | `name` |
| `internal_users_is_active_idx` | No | BTREE | `is_active` |
| `PRIMARY` | Yes | BTREE | `user_id` |

**Foreign Keys**

None

### `migrations`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `migration` | `varchar(255)` | NO | - | `NULL` | - |
| `batch` | `int(11)` | NO | - | `NULL` | - |

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `PRIMARY` | Yes | BTREE | `id` |

**Foreign Keys**

None

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

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `idx_pdia_month_internal` | No | BTREE | `month_key`, `internal_id` |
| `PRIMARY` | Yes | BTREE | `date_key`, `internal_id` |

**Foreign Keys**

None

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

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `idx_rda_month_dmc` | No | BTREE | `month_key`, `dmc` |
| `PRIMARY` | Yes | BTREE | `date_key`, `dmc` |

**Foreign Keys**

None

### `report_monthly_aggregates`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `month_key` | `varchar(7)` | NO | PRI | `NULL` | - |
| `dmc` | `varchar(255)` | NO | PRI | `''` | - |
| `total_rows` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ok_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `ng_count` | `bigint(20) unsigned` | NO | - | `0` | - |
| `aggregated_at` | `timestamp` | YES | - | `NULL` | - |

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `PRIMARY` | Yes | BTREE | `month_key`, `dmc` |

**Foreign Keys**

None

### `Test_Methods`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `method_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `method_name` | `varchar(100)` | NO | MUL | `NULL` | - |
| `equipment_id` | `int(10) unsigned` | YES | MUL | `NULL` | - |
| `is_active` | `tinyint(1)` | NO | MUL | `1` | - |

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `ft_tm_name` | No | FULLTEXT | `method_name` |
| `idx_test_methods_active_name` | No | BTREE | `is_active`, `method_name` |
| `idx_test_methods_name` | No | BTREE | `method_name` |
| `PRIMARY` | Yes | BTREE | `method_id` |
| `test_methods_equipment_id_foreign` | No | BTREE | `equipment_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `test_methods_equipment_id_foreign` | `equipment_id` | `Equipments.equipment_id` | RESTRICT | SET NULL |

### `Transaction_Detail`

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

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `ft_td_search` | No | FULLTEXT | `remark`, `max_value`, `min_value` |
| `idx_judgement` | No | BTREE | `judgement` |
| `idx_perf_detail` | No | BTREE | `internal_id`, `start_time`, `end_time` |
| `idx_td_deleted_at` | No | BTREE | `deleted_at` |
| `idx_td_deleted_end_detail` | No | BTREE | `deleted_at`, `end_time`, `detail_id` |
| `idx_td_deleted_internal_time` | No | BTREE | `deleted_at`, `internal_id`, `start_time`, `end_time` |
| `idx_td_deleted_judge_start_detail` | No | BTREE | `deleted_at`, `judgement`, `start_time`, `detail_id` |
| `idx_td_deleted_start_detail` | No | BTREE | `deleted_at`, `start_time`, `detail_id` |
| `idx_td_deleted_start_internal` | No | BTREE | `deleted_at`, `start_time`, `internal_id` |
| `idx_td_deleted_tx_judgement` | No | BTREE | `deleted_at`, `transaction_id`, `judgement` |
| `PRIMARY` | Yes | BTREE | `detail_id` |
| `transaction_detail_method_id_foreign` | No | BTREE | `method_id` |
| `transaction_detail_transaction_id_foreign` | No | BTREE | `transaction_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `transaction_detail_internal_id_foreign` | `internal_id` | `Internal_Users.user_id` | RESTRICT | RESTRICT |
| `transaction_detail_method_id_foreign` | `method_id` | `Test_Methods.method_id` | RESTRICT | RESTRICT |
| `transaction_detail_transaction_id_foreign` | `transaction_id` | `Transaction_Header.transaction_id` | RESTRICT | RESTRICT |

### `Transaction_Header`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `transaction_id` | `int(10) unsigned` | NO | PRI | `NULL` | auto_increment |
| `external_id` | `int(10) unsigned` | NO | MUL | `NULL` | - |
| `internal_id` | `int(10) unsigned` | NO | MUL | `NULL` | - |
| `detail` | `text` | YES | MUL | `NULL` | - |
| `dmc` | `varchar(255)` | YES | - | `NULL` | - |
| `line` | `varchar(255)` | YES | - | `NULL` | - |
| `receive_date` | `datetime` | NO | MUL | `current_timestamp()` | - |
| `return_date` | `datetime` | YES | MUL | `NULL` | - |
| `deleted_at` | `timestamp` | YES | MUL | `NULL` | - |

**Indexes**

| Index | Unique | Type | Columns |
|---|---|---|---|
| `ft_th_search` | No | FULLTEXT | `detail`, `dmc`, `line` |
| `idx_dates` | No | BTREE | `receive_date`, `return_date` |
| `idx_th_deleted_at` | No | BTREE | `deleted_at` |
| `idx_th_deleted_receive` | No | BTREE | `deleted_at`, `receive_date` |
| `idx_th_return_deleted_receive` | No | BTREE | `return_date`, `deleted_at`, `receive_date` |
| `PRIMARY` | Yes | BTREE | `transaction_id` |
| `transaction_header_external_id_foreign` | No | BTREE | `external_id` |
| `transaction_header_internal_id_foreign` | No | BTREE | `internal_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `transaction_header_external_id_foreign` | `external_id` | `External_Users.external_id` | RESTRICT | RESTRICT |
| `transaction_header_internal_id_foreign` | `internal_id` | `Internal_Users.user_id` | RESTRICT | RESTRICT |

## Setup On Home Machine

1. Clone the project source code to the home machine.
2. Configure `.env` with the target MariaDB connection (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
3. Run migrations to create all tables and indexes:
   - `php artisan migrate --force`
4. (Optional) Seed baseline master data:
   - `php artisan db:seed --class=QcSeeder`
5. Verify generated schema with:
   - `php artisan migrate:status`

## Optional Data Copy (Current DB -> Home DB)

If you also need existing records (not only schema), export/import with `mysqldump`:

- Export:
  - `mysqldump -h <source_host> -P <source_port> -u <user> -p --databases qc3 > qc3_backup.sql`
- Import:
  - `mysql -h <target_host> -P <target_port> -u <user> -p < qc3_backup.sql`
