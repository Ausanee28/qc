# QC Database Schema Documentation

This document outlines the structure of the database tables used in the QC tracking application. It was generated based on the current live database schema.

## Core Tables

### Transaction_Header
Stores the main information for a received job.
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| transaction_id | int(10) unsigned | NO | PRI | null | auto_increment |
| external_id | int(10) unsigned | NO | MUL | null | |
| internal_id | int(10) unsigned | YES | MUL | null | |
| sender_leader | varchar(255) | YES | | null | |
| detail | text | YES | MUL | null | |
| dmc | varchar(255) | YES | | null | |
| cell | varchar(255) | YES | | null | |
| line | varchar(255) | YES | | null | |
| shift | varchar(255) | YES | | null | |
| model | varchar(255) | YES | | null | |
| receive_date | datetime | NO | MUL | current_timestamp() | |
| return_date | datetime | YES | MUL | null | |
| deleted_at | timestamp | YES | MUL | null | |

### Transaction_Detail
Stores the individual test processes for a given transaction.
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| detail_id | int(10) unsigned | NO | PRI | null | auto_increment |
| transaction_id | int(10) unsigned | NO | MUL | null | |
| method_id | int(10) unsigned | NO | MUL | null | |
| internal_id | int(10) unsigned | NO | MUL | null | |
| start_time | datetime | YES | | null | |
| end_time | datetime | NO | | current_timestamp() | |
| duration_sec | int(11) | YES | | null | |
| max_value | varchar(255) | YES | | null | |
| min_value | varchar(255) | YES | | null | |
| judgement | varchar(255) | YES | MUL | null | |
| remark | varchar(255) | YES | MUL | null | |
| deleted_at | timestamp | YES | MUL | null | |

## Master Data Tables

### Departments
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| department_id | int(10) unsigned | NO | PRI | null | auto_increment |
| department_name | varchar(100) | NO | MUL | null | |
| internal_phone | varchar(255) | YES | | null | |
| is_active | tinyint(1) | NO | MUL | 1 | |

### Equipments
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| equipment_id | int(10) unsigned | NO | PRI | null | auto_increment |
| equipment_name | varchar(100) | YES | MUL | null | |
| is_active | tinyint(1) | NO | MUL | 1 | |

### External_Users
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| external_id | int(10) unsigned | NO | PRI | null | auto_increment |
| external_name | varchar(100) | NO | MUL | null | |
| department_id | int(10) unsigned | YES | MUL | null | |
| is_active | tinyint(1) | NO | MUL | 1 | |

### Internal_Users
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| user_id | int(10) unsigned | NO | PRI | null | auto_increment |
| user_name | varchar(255) | NO | | null | |
| user_password | varchar(255) | YES | | null | |
| employee_id | varchar(255) | YES | | null | |
| name | varchar(100) | YES | MUL | null | |
| role | varchar(50) | YES | | null | |
| is_active | tinyint(1) | NO | MUL | 1 | |

### Test_Methods
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| method_id | int(10) unsigned | NO | PRI | null | auto_increment |
| method_name | varchar(100) | NO | MUL | null | |
| equipment_id | int(10) unsigned | YES | MUL | null | |
| is_active | tinyint(1) | NO | MUL | 1 | |

## Logging and Aggregation Tables

### Audit_Logs
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| audit_id | bigint(20) unsigned | NO | PRI | null | auto_increment |
| module | varchar(50) | NO | MUL | null | |
| action | varchar(20) | NO | | null | |
| record_type | varchar(80) | NO | MUL | null | |
| record_id | int(10) unsigned | YES | | null | |
| performed_by | int(10) unsigned | YES | MUL | null | |
| performed_by_name | varchar(120) | YES | | null | |
| before_data | longtext | YES | | null | |
| after_data | longtext | YES | | null | |
| created_at | timestamp | NO | MUL | current_timestamp() | |

### performance_daily_inspector_aggregates
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| date_key | date | NO | PRI | null | |
| month_key | varchar(7) | NO | MUL | null | |
| internal_id | int(10) unsigned | NO | PRI | null | |
| total_tests | bigint(20) unsigned | NO | | 0 | |
| ok_count | bigint(20) unsigned | NO | | 0 | |
| ng_count | bigint(20) unsigned | NO | | 0 | |
| duration_total_sec | bigint(20) unsigned | NO | | 0 | |
| duration_samples | bigint(20) unsigned | NO | | 0 | |
| min_duration_sec | int(10) unsigned | YES | | null | |
| max_duration_sec | int(10) unsigned | YES | | null | |
| aggregated_at | timestamp | YES | | null | |

### report_daily_aggregates
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| date_key | date | NO | PRI | null | |
| month_key | varchar(7) | NO | MUL | null | |
| dmc | varchar(255) | NO | PRI | "" | |
| total_rows | bigint(20) unsigned | NO | | 0 | |
| ok_count | bigint(20) unsigned | NO | | 0 | |
| ng_count | bigint(20) unsigned | NO | | 0 | |
| aggregated_at | timestamp | YES | | null | |

### report_monthly_aggregates
| Field | Type | Null | Key | Default | Extra |
|---|---|---|---|---|---|
| month_key | varchar(7) | NO | PRI | null | |
| dmc | varchar(255) | NO | PRI | "" | |
| total_rows | bigint(20) unsigned | NO | | 0 | |
| ok_count | bigint(20) unsigned | NO | | 0 | |
| ng_count | bigint(20) unsigned | NO | | 0 | |
| aggregated_at | timestamp | YES | | null | |
