# CSMS v1.0.0 Release Notes

## 1. Release

- Application: Community Services Management System
- Release: v1.0.0
- Release Type: Final Course Release

## 2. Release Summary

CSMS v1.0.0 provides persistent Resident registration, searching, information
updates, and deactivation; Service Request submission and controlled processing;
and a Dashboard summarizing current community records. The Laravel browser
interface connects these capabilities while preserving validation, eligibility,
workflow, and persistence rules developed through T01-T15.

The release identity is defined in `config/csms.php`. The health endpoint reports
version `1.0.0`, and the home page and shared application footer display `v1.0.0`
from that same configuration.

## 3. Resident Management Capabilities

- Register Residents with validated names, address, contact number, and email.
- Persist generated identities and an initial Active status in SQLite.
- List and search by partial, case-insensitive Resident names.
- Update allowed information without changing identity or lifecycle status.
- Deactivate Residents while preserving their records and request relationships.
- Reject invalid input with understandable browser feedback.

## 4. Service Request Management Capabilities

- Submit validated requests only for existing Active Residents.
- Persist the Resident relationship, service type, description, requested date,
  and initial Pending status.
- List, search, filter by status, and view request details.
- Process Pending -> In Progress -> Completed.
- Cancel Pending or In Progress requests; Completed and Cancelled are terminal.
- Reject invalid transitions and ineligible submissions without changing records.

## 5. Dashboard Capabilities

The Dashboard displays total, Active, and Inactive Residents; total requests and
counts for all four request statuses; Open requests as Pending plus In Progress;
and the five most recent requests ordered by requested date and ID descending.
Counts come from persisted data. Links connect the Dashboard, management pages,
and request details. Read-only navigation does not perform domain mutations.

## 6. Quality and Hardening

T15 added controlled wrong-method feedback and regression coverage for malformed
input, persistence integrity, hostile searches, lifecycle eligibility, and recent
request ordering. T16 retains that protection and adds a persisted integration
scenario combining Resident updates, request ownership, mixed workflow outcomes,
searching, Dashboard aggregation, and rejected-operation safety.

Release tests verify the exact safe health payload, visible version across the
browser application, and propagation of a test-only configuration change to both
health and UI consumers. No previous test was deleted or disabled. Starter version
expectations were updated from `0.1.0` to the required `1.0.0`; the health assertion
was strengthened to reject additional payload fields.

## 7. Final Verification

Verified on 2026-11-13 (Asia/Manila), on `feature/t16-final-release`.

Command: `php vendor/phpunit/phpunit/phpunit --colors=never`.

| Verification | Result |
| --- | --- |
| Final Regression Run 1 | PASS - 238 tests, 1,373 assertions |
| Final Regression Run 2, immediately after Run 1 | PASS - 238 tests, 1,373 assertions |
| Application Startup | PASS - local PHP HTTP server and application pages responded |
| `/health` | PASS - HTTP 200; application name, version `1.0.0`, status `ok`; exactly three public fields |
| Dashboard | PASS - Resident metrics, request metrics, Open count, and recent request reflected browser operations |
| Resident Management | PASS - listing, search, valid/invalid registration, update, and deactivation |
| Service Request Management | PASS - submission, eligibility protection, listing, search, status filtering, and details |
| Valid workflow | PASS - Pending -> In Progress -> Completed in the browser |
| Invalid workflow | PASS - direct Completed -> Cancelled POST rejected with feedback; database unchanged |
| Read-only safety | PASS - three repeated rounds of Dashboard, Resident search, request search/filter, and details left the SQLite file hash unchanged |
| Navigation | PASS - shared links connected Dashboard, Residents, and Service Requests |
| Final Release Version | PASS - `/health` reports `1.0.0`; home and shared footer show `v1.0.0` |
| Formatting and whitespace | PASS - scoped Laravel Pint check and `git diff --check` |

Browser acceptance used a separate local SQLite review database, not the normal
development database. The direct invalid-transition HTTP check used a real
session and CSRF token. Automated tests use isolated in-memory SQLite persistence.

These are release-candidate results. The official release must be created only
after merging T16, updating local `main`, rerunning the full suite, and verifying
the running application from that merged commit. See [the release guide](t16-release-guide.md).

## 8. Known Limitations

No known release-blocking limitations remain within the defined CSMS v1.0.0 scope.
Authentication, authorization, reactivation, physical deletion, exports, and
advanced reporting remain outside the course scope, as documented in T15.
The health endpoint confirms basic application availability and release identity;
it is not a database diagnostic or a complete infrastructure health check.
