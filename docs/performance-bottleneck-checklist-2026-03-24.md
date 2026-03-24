# Performance Bottleneck Checklist (2026-03-24)

Clean UTF-8 copy of the latest checklist state.

See also: [performance-summary-2026-03-24.md](./performance-summary-2026-03-24.md)

## Baseline

- [x] Collect route baseline with `qc:profile`
- [x] Compare before/after for major quick wins
- [x] Publish final baseline summary after all implemented steps

## Backend And Query

- [x] Reduce repeated dashboard metric queries
- [x] Replace date filters with index-friendly range queries
- [x] Reduce `/execute-test/pending-jobs-version` load with cache-backed version token
- [x] Push report export filtering into SQL and stream the result set
- [x] Cache analytics hotspots such as `performance`
- [x] Re-check `certificate / report / performance` queries on a larger dataset

## Workflow Pages

- [x] Reduce query and relation overhead in `Receive Job`
- [x] Reduce query and relation overhead in `Execute Test`
- [x] Reduce render work in workflow table rows
- [x] Move workflow filter state out of unnecessary `useForm`
- [x] Reduce `Receive Job` and `Execute Test` option/data payload
- [x] Audit workflow modal/form state for avoidable reload weight

## Dashboard And Analytics

- [x] Split chart runtime to page-specific loaders
- [x] Split chart bundle from the main app path
- [x] Make dashboard first viewport lighter
- [x] Move dashboard realtime boot off the critical path
- [x] Audit dashboard hydration on desktop/tablet/mobile viewports
- [x] Consider reducing or further deferring dashboard sections if first load still feels heavy

## Shared App Shell

- [x] Reduce `AuthenticatedLayout` size
- [x] Consolidate nav config and remove repeated branching
- [x] Remove sidebar/mobile nav hover prefetch noise
- [x] Review shared components that still carry avoidable state or logic

## Master Data

- [x] Reduce client-side search overhead with normalized search paths
- [x] Move master-data lists to server-side search + pagination
- [x] Use partial reload after create/update/delete
- [x] Defer modal-only option lists in `Test Methods` and `External Users`
- [x] Reduce any remaining master-data modal/form payload if needed
- [x] Extract duplicated modal/form shell into a shared component

## Bundle And Frontend Delivery

- [x] Split `framework`, `charts`, `realtime`, and `http` into vendor chunks
- [x] Reduce the `app.js` main entry to essentials
- [x] Investigate whether `framework-vendor` can be reduced further
- [x] Audit shared CSS/utility payload for first-load weight

## Final Verification

- [x] Run `npm run build`
- [x] Run `php artisan qc:profile` for the core routes again
- [x] Summarize before/after metrics numerically
- [x] Separate remaining backlog into quick wins and long-term work

## Suggested Next Steps

1. Run an optional physical-device sanity check if you want hardware-level confirmation beyond viewport emulation.
2. Re-benchmark `certificates` on production-like data if the monthly range grows beyond the current local sample.
3. Keep watching `framework-vendor` only when a dependency change lands, because the remaining weight is core Vue + Inertia runtime.
