# CRUD Audit Checklist

Last reviewed: 2026-03-18

## Summary

| Module | Create | Read | Update | Delete | Status |
| --- | --- | --- | --- | --- | --- |
| Departments | Yes | Yes | Yes | Yes | Ready, admin-only after guard |
| Equipment | Yes | Yes | Yes | Yes | Ready, admin-only after guard |
| Test Methods | Yes | Yes | Yes | Yes | Ready, admin-only after guard |
| Internal Users | Yes | Yes | Yes | Yes | Ready, admin-only after guard |
| External Users | Yes | Yes | Yes | Yes | Ready, admin-only after guard |
| Receive Job | Yes | Yes | Yes | Yes | Implemented on 2026-03-18 |
| Execute Test Results | Yes | Yes | Yes | Yes | Implemented on 2026-03-18 |
| Dashboard | No | Yes | No | No | Read-only analytics |
| Report | No | Yes | No | No | Read/export module |
| Certificates | No | Yes | No | No | Read/download module |
| Performance | No | Yes | No | No | Read-only analytics |

## Standards Review

| Standard | Status | Notes |
| --- | --- | --- |
| Auth required for app pages | Yes | Protected by `auth` middleware |
| Email verification support | In progress | Schema/tests aligned on 2026-03-18 |
| Role-based authorization | Yes | Master data routes restricted to admin |
| Validation on write actions | Yes | Request validation exists across CRUD endpoints |
| Safe delete rules | Partial | Business rules block deletes with dependent records |
| Automated CRUD coverage | Partial | Core workflow tests added, more module coverage still recommended |

## Remaining Recommendations

1. Add pagination to master data and workflow tables once record counts grow.
2. Add policy-based permissions if engineer/inspector roles need finer access rules.
3. Add CRUD feature tests for every master-data controller, not only workflow and auth smoke coverage.
