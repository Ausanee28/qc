# Performance Benchmark Runbook

Use this runbook to collect route timing and scale-focused query timing in a production-like setup.

## 1) Route Profile Benchmark

### What it does

The PowerShell script:

1. Clears framework caches
2. Builds front-end assets
3. Enables optimized Laravel caches (`config`, `event`, plus optional `route`/`view`)
4. Runs `qc:profile` with multiple runs per route
5. Saves a timestamped report

### Run

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\benchmark-prodlike.ps1 -Runs 5
```

### Output

Example output file:

```text
storage/app/performance/profile-prodlike-2026-03-20-143500.txt
```

## 2) Scale Benchmark (50k / 200k ready)

### What it does

`qc:scale-benchmark`:

1. Inserts synthetic workload rows in a transaction
2. Runs heavy query paths used by Receive Job / Execute Test / Report / Performance
3. Evaluates each query against configurable threshold gates
4. Prints PASS/FAIL per metric
5. Writes a JSON report
6. Rolls back synthetic rows after measurement

### Recommended run (50k headers / 200k details)

```powershell
php artisan qc:scale-benchmark --headers=50000 --details-per-header=4 --chunk=1000 --window-days=30
```

### Optional larger run

```powershell
php artisan qc:scale-benchmark --headers=100000 --details-per-header=4 --chunk=1500 --window-days=30
```

### Optional custom gate example

```powershell
php artisan qc:scale-benchmark --headers=50000 --details-per-header=4 --gate-report-page-ms=2500 --gate-pending-open-ms=900
```

### Output

The command prints an absolute JSON path using the active `local` filesystem disk root.
In this project, that is typically under:

```text
storage/app/private/performance/scale-benchmark-YYYY-MM-DD-HHMMSS.json
```

## Notes

- Keep machine conditions consistent across runs.
- Keep browser tabs and background apps minimal during measurements.
- Run with Redis available if production will use Redis failover cache/queue.
- The scale benchmark wraps inserts in a DB transaction and rolls them back after measurement.
