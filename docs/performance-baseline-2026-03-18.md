# Performance Baseline (2026-03-18)

## Scope
- Environment: local app -> MariaDB `qc3` (`10.22.0.101:3307`)
- Objective:
  - Record baseline table/index/query metrics
  - Identify index gaps before data volume grows

## Snapshot

### Table Size / Rows
- `Transaction_Detail`: rows=2, ~0.09 MB
- `Transaction_Header`: rows=5, ~0.08 MB
- `External_Users`: rows=2, ~0.03 MB
- `Test_Methods`: rows=5, ~0.03 MB
- `Internal_Users`: rows=3, ~0.03 MB

### Existing Relevant Indexes (before scaling migration)
- `Transaction_Header`
  - `idx_dates(receive_date, return_date)`
  - `idx_th_deleted_at(deleted_at)`
  - FKs on `external_id`, `internal_id`
- `Transaction_Detail`
  - `idx_judgement(judgement)`
  - `idx_perf_detail(internal_id, start_time, end_time)`
  - `idx_td_deleted_at(deleted_at)`
  - FKs on `transaction_id`, `method_id`

### EXPLAIN Highlights
- `Header range` query uses `idx_th_deleted_at` (single-column) with index condition.
- `Detail by header + judgement` uses `idx_td_deleted_at` then filters.
- `Inspector efficiency` uses `idx_td_deleted_at` and reports `Using temporary; Using filesort`.

## Action Implemented
- Added migration:
  - `2026_03_18_000004_add_scaling_indexes_to_workflow_tables.php`
- New indexes:
  - `Transaction_Header(deleted_at, receive_date)` -> `idx_th_deleted_receive`
  - `Transaction_Detail(deleted_at, transaction_id, judgement)` -> `idx_td_deleted_tx_judgement`
  - `Transaction_Detail(deleted_at, internal_id, start_time, end_time)` -> `idx_td_deleted_internal_time`

## Next Measurement (after migrate)
- Re-run EXPLAIN for:
  - Dashboard header range
  - Dashboard counts by judgement
  - Dashboard inspector efficiency
- Compare:
  - chosen index
  - rows scanned
  - `Using temporary/filesort` flags

