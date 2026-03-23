# DB Data Incident Notes (2026-03-20)

## What We Confirmed

- Active runtime DB now points to `10.22.0.101:3307 / qc3`.
- Core tables in `qc3` currently have `0` rows:
  - `Transaction_Header`
  - `Transaction_Detail`
  - `Internal_Users`
  - `External_Users`
- The `migrations` table exists and shows a full bootstrap in one batch (batch `1`), which is typical for a fresh schema setup.

## Evidence Timeline (From App Logs)

- **2026-03-03 08:10:35**: `Unknown database 'qc3'`  
  Source: [laravel.log](C:/qc/storage/logs/laravel.log:569)
- **2026-03-04 06:23:53**: `Table 'qc3.transaction_header' doesn't exist`  
  Source: [laravel.log](C:/qc/storage/logs/laravel.log:1535)
- **2026-03-19 06:51:43**: local home DB connection refused (`127.0.0.1:3306 / dbqc`)  
  Source: [laravel.log](C:/qc/storage/logs/laravel.log:34689)

Interpretation: `qc3` was at some point missing (database and/or tables), then later recreated/migrated. This does not look like a direct effect of performance-code changes.

## Why This Is Likely Not From "Bottleneck Fix Round 1"

- Round-1 code edits were query/index/runtime improvements; they did not include `truncate`, `delete all`, `drop table`, or `migrate:fresh`.
- Project tests use sqlite in-memory per [phpunit.xml](C:/qc/phpunit.xml), so `php artisan test` does not wipe `qc3`.

## Immediate Recovery Checklist (For DBA / DB Owner)

1. Identify authoritative backup before incident window:
   - Before **2026-03-03 08:10:35** (or nearest healthy snapshot).
2. Restore to staging schema first (for validation), not directly to production schema.
3. Compare counts on key tables:
   - `Transaction_Header`
   - `Transaction_Detail`
   - `Internal_Users`
   - `External_Users`
4. If binlog is available, perform point-in-time recovery to just before data loss.
5. After recovery, run application smoke checks:
   - login
   - dashboard counts
   - receive/execute flow
   - report export

## Guardrails Added

- `switch_db.php status` now reports whether `DB_PROFILE` matches actual runtime DB settings.
- Added `php switch_db.php sync-profile` to align only `DB_PROFILE` with current DB config (no host/database credential changes).

