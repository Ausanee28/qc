# Performance Benchmark Runbook

Use this runbook to collect route timing in a production-like local setup.

## What it does

The script:

1. Clears framework caches
2. Builds front-end assets
3. Enables optimized Laravel caches (`optimize`)
4. Runs `qc:profile` with multiple runs per route
5. Saves a timestamped report under `storage/app/performance`

## Run

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\benchmark-prodlike.ps1 -Runs 5
```

## Output

Example output file:

```text
storage/app/performance/profile-prodlike-2026-03-20-143500.txt
```

## Notes

- Use the same machine conditions when comparing before/after results.
- Keep browser tabs and background apps minimal during measurements.
- For network/web-vitals audits (Lighthouse), prefer HTTPS + HTTP/2 reverse proxy in front of Laravel.

