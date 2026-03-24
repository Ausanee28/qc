# Performance Summary (2026-03-24)

## Scope

- Objective:
  - close the current performance pass with a route-level before/after comparison
  - record remaining backlog after the implemented quick wins
- Before reference:
  - `storage/app/performance/profile-prodlike-2026-03-20-154701.txt`
- After reference:
  - `php artisan qc:profile --runs=3` on `2026-03-24`
  - `storage/app/performance/profile-prodlike-2026-03-24-134647.txt` for the 5-run snapshot

## Before / After

Comparison below uses the 3-run route profile so it lines up with the saved before report from `2026-03-20`.

| Route | Before Avg (ms) | After Avg (ms) | Delta | Before DB (ms) | After DB (ms) | Before Queries | After Queries |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| `/dashboard` | 23.58 | 27.93 | +4.35 | 0.00 | 0.00 | 0.0 | 0.0 |
| `/receive-job` | 57.72 | 42.02 | -15.70 | 35.54 | 23.13 | 4.7 | 2.7 |
| `/execute-test` | 52.66 | 29.15 | -23.51 | 37.77 | 18.63 | 7.0 | 3.0 |
| `/report` | 28.53 | 13.35 | -15.18 | 21.27 | 0.00 | 1.0 | 0.0 |
| `/performance` | 38.74 | 6.72 | -32.02 | 32.61 | 0.00 | 2.0 | 0.0 |

## What Improved Most

- `Execute Test`
  - query count dropped from `7.0` to `3.0`
  - route average dropped by `23.51ms`
- `Performance`
  - warm analytics cache removes DB work on the measured route
  - route average dropped by `32.02ms`
- `Receive Job`
  - query count dropped from `4.7` to `2.7`
  - route average dropped by `15.70ms`
- `Report`
  - pagination, SQL-side filtering, and cache-friendly path reduced both payload pressure and measured DB work

## Notes

- The `prod-like` benchmark script still hits two environment issues in this workspace:
  - `route:cache` throws a Laravel type error with compiled routes
  - `view:cache` can fail with a Windows file rename access error
- The script-side `npm run build` also failed in that shell context with `spawn EPERM`, but direct `npm run build` in the normal workspace shell passed.
- `/dashboard` did not improve in the route-only measurement because most of the recent work there was front-end bundle and hydration reduction, not server query time.
- A viewport-based hydration audit was completed and saved in [dashboard-hydration-audit-2026-03-24.md](./dashboard-hydration-audit-2026-03-24.md).
- That audit found no horizontal overflow on desktop, tablet, or mobile, and confirmed that the deferred `Trend archive` section stays out of the initial render and loads when scrolled into view.

## Final Completion Pass

- `Certificates`
  - moved the list to server-side pagination and then to `simplePaginate()` so the grouped query does not pay a full count cost on every filter change
  - route profile for `/certificates` after the change: `124.91ms avg`, `25.35ms DB`, `1.7 queries`
- `Shared components`
  - `Modal` and `Dropdown` now attach their Escape listeners only while open, instead of leaving document-level listeners active on every page
- `Master data`
  - trimmed remaining list payloads by selecting only table columns and modal option labels that the pages actually render
- `Shared CSS`
  - removed unused legacy dashboard/layout selectors from `app.css`
  - built CSS dropped from roughly `84.30 kB` to `81.12 kB`
- `framework-vendor`
  - audited for another reduction pass and did not find a safe quick win beyond the existing chunk split
  - the remaining weight is primarily the Vue + Inertia runtime that the app shell requires on every page

## Remaining Backlog

### Optional Follow-Up

- Run a manual physical-device smoke test if you want hardware-level confirmation beyond viewport emulation
- Re-check `/certificates` with a larger production-like month range if data volume grows significantly
- Revisit `framework-vendor` only when dependency choices change, not as a blind size-only exercise
