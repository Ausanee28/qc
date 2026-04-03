# Database Schema Reference (qc3)

- Generated at: 2026-04-03 09:32:02
- Driver: mariadb
- Database: qc3
- Table count: 9

## Table List

| Table | Approx. rows |
|---|---:|
| `Audit_Logs` | 18 |
| `Departments` | 5 |
| `Equipments` | 6 |
| `External_Users` | 3 |
| `Internal_Users` | 2 |
| `migrations` | 20 |
| `Test_Methods` | 1 |
| `Transaction_Detail` | 9 |
| `Transaction_Header` | 4 |

## Table Details

### `Audit_Logs`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `audit_id` | `bigint(20) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `module` | `varchar(50)` | NO | MUL | `NULL` | `-` |
| `action` | `varchar(20)` | NO | - | `NULL` | `-` |
| `record_type` | `varchar(80)` | NO | MUL | `NULL` | `-` |
| `record_id` | `int(10) unsigned` | YES | - | `NULL` | `-` |
| `performed_by` | `int(10) unsigned` | YES | MUL | `NULL` | `-` |
| `performed_by_name` | `varchar(120)` | YES | - | `NULL` | `-` |
| `before_data` | `longtext` | YES | - | `NULL` | `-` |
| `after_data` | `longtext` | YES | - | `NULL` | `-` |
| `created_at` | `timestamp` | NO | MUL | `current_timestamp()` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `idx_audit_actor` | No | `performed_by` |
| `idx_audit_created_at` | No | `created_at` |
| `idx_audit_module_action` | No | `module`, `action` |
| `idx_audit_record` | No | `record_type`, `record_id` |
| `PRIMARY` | Yes | `audit_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `fk_audit_logs_user` | `performed_by` | `Internal_Users.user_id` | RESTRICT | SET NULL |

### `Departments`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `department_id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `department_name` | `varchar(100)` | NO | - | `NULL` | `-` |
| `internal_phone` | `varchar(255)` | YES | - | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `PRIMARY` | Yes | `department_id` |

### `Equipments`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `equipment_id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `equipment_name` | `varchar(100)` | YES | - | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `PRIMARY` | Yes | `equipment_id` |

### `External_Users`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `external_id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `external_name` | `varchar(100)` | NO | - | `NULL` | `-` |
| `department_id` | `int(10) unsigned` | YES | MUL | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `external_users_department_id_foreign` | No | `department_id` |
| `PRIMARY` | Yes | `external_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `external_users_department_id_foreign` | `department_id` | `Departments.department_id` | RESTRICT | RESTRICT |

### `Internal_Users`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `user_id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `user_name` | `varchar(255)` | NO | - | `NULL` | `-` |
| `user_password` | `varchar(255)` | YES | - | `NULL` | `-` |
| `employee_id` | `varchar(255)` | YES | - | `NULL` | `-` |
| `name` | `varchar(100)` | YES | - | `NULL` | `-` |
| `role` | `varchar(50)` | YES | - | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `PRIMARY` | Yes | `user_id` |

### `migrations`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `migration` | `varchar(255)` | NO | - | `NULL` | `-` |
| `batch` | `int(11)` | NO | - | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `PRIMARY` | Yes | `id` |

### `Test_Methods`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `method_id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `method_name` | `varchar(100)` | NO | - | `NULL` | `-` |
| `equipment_id` | `int(10) unsigned` | YES | MUL | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `PRIMARY` | Yes | `method_id` |
| `test_methods_equipment_id_foreign` | No | `equipment_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `test_methods_equipment_id_foreign` | `equipment_id` | `Equipments.equipment_id` | RESTRICT | SET NULL |

### `Transaction_Detail`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `detail_id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `transaction_id` | `int(10) unsigned` | NO | MUL | `NULL` | `-` |
| `method_id` | `int(10) unsigned` | NO | MUL | `NULL` | `-` |
| `internal_id` | `int(10) unsigned` | NO | MUL | `NULL` | `-` |
| `start_time` | `datetime` | YES | - | `NULL` | `-` |
| `end_time` | `datetime` | NO | - | `current_timestamp()` | `-` |
| `duration_sec` | `int(11)` | YES | - | `NULL` | `-` |
| `max_value` | `varchar(255)` | YES | - | `NULL` | `-` |
| `min_value` | `varchar(255)` | YES | - | `NULL` | `-` |
| `judgement` | `varchar(255)` | YES | MUL | `NULL` | `-` |
| `remark` | `varchar(255)` | YES | - | `NULL` | `-` |
| `deleted_at` | `timestamp` | YES | MUL | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `idx_judgement` | No | `judgement` |
| `idx_perf_detail` | No | `internal_id`, `start_time`, `end_time` |
| `idx_td_deleted_at` | No | `deleted_at` |
| `idx_td_deleted_internal_time` | No | `deleted_at`, `internal_id`, `start_time`, `end_time` |
| `idx_td_deleted_tx_judgement` | No | `deleted_at`, `transaction_id`, `judgement` |
| `PRIMARY` | Yes | `detail_id` |
| `transaction_detail_method_id_foreign` | No | `method_id` |
| `transaction_detail_transaction_id_foreign` | No | `transaction_id` |

**Foreign Keys**

| Constraint | Column | References | On Update | On Delete |
|---|---|---|---|---|
| `transaction_detail_internal_id_foreign` | `internal_id` | `Internal_Users.user_id` | RESTRICT | RESTRICT |
| `transaction_detail_method_id_foreign` | `method_id` | `Test_Methods.method_id` | RESTRICT | RESTRICT |
| `transaction_detail_transaction_id_foreign` | `transaction_id` | `Transaction_Header.transaction_id` | RESTRICT | RESTRICT |

### `Transaction_Header`

| Column | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| `transaction_id` | `int(10) unsigned` | NO | PRI | `NULL` | `auto_increment` |
| `external_id` | `int(10) unsigned` | NO | MUL | `NULL` | `-` |
| `internal_id` | `int(10) unsigned` | NO | MUL | `NULL` | `-` |
| `detail` | `text` | YES | - | `NULL` | `-` |
| `dmc` | `varchar(255)` | YES | - | `NULL` | `-` |
| `line` | `varchar(255)` | YES | - | `NULL` | `-` |
| `receive_date` | `datetime` | NO | MUL | `current_timestamp()` | `-` |
| `return_date` | `datetime` | YES | MUL | `NULL` | `-` |
| `deleted_at` | `timestamp` | YES | MUL | `NULL` | `-` |

**Indexes**

| Index | Unique | Columns |
|---|---|---|
| `idx_dates` | No | `receive_date`, `return_date` |
| `idx_th_deleted_at` | No | `deleted_at` |
| `idx_th_deleted_receive` | No | `deleted_at`, `receive_date` |
| `idx_th_return_deleted_receive` | No | `return_date`, `deleted_at`, `receive_date` |
| `PRIMARY` | Yes | `transaction_id` |
| `transaction_header_external_id_foreign` | No | `external_id` |
| `transaction_header_internal_id_foreign` | No | `internal_id` |

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
