# T16 Final Development Checkpoint

## 1. Developer Information

- Name: Constantino, Raxell Louis I.
- GitHub Username: luhwiss
- Technology Stack: PHP with Laravel, Blade, SQLite, PHPUnit
- T16 Branch: `feature/t16-final-release`
- Final Release Version: 1.0.0

T15 was merged in [PR #18](https://github.com/UPHSL/csms-php-luhwiss/pull/18).
Before T16 edits, local `main` was fast-forwarded to `1a4e97a` with a clean working
tree. The T15 readiness document stated `READY FOR T16 FINAL EXAM`. The complete
baseline suite passed with 235 tests and 1,298 assertions, and `/`, `/health`,
Dashboard, Residents, and Service Requests returned successful HTTP responses.
The T16 branch was then created from that verified `main` baseline.

## 2. My Final Release Implementation

I established the application name and version in `config/csms.php`, with `1.0.0`
as the authoritative release version. This location fits Laravel because release
metadata is application configuration rather than a Resident or Service Request
business rule. The health controller reads the name and version using Laravel's
`config()` helper and retains its `ok` status. The home controller reads the same
configuration and passes the values to the existing home view. The shared Blade
layout uses that configuration to display `v1.0.0` in the footer of the management
and Dashboard screens. This removes the separate runtime version literals that
previously existed in the home and health controllers. A test temporarily changes
the configured version to `1.0.1` and verifies that health and browser views follow
it, demonstrating that a future release needs only one runtime version change.

## 3. Files I Changed

| File | Purpose |
| --- | --- |
| `config/csms.php` | Defines authoritative application name and release version |
| `app/Http/Controllers/HealthController.php` | Reads release identity from configuration while preserving the safe JSON structure |
| `app/Http/Controllers/HomeController.php` | Replaces separate home-page identity/version literals with configuration reads |
| `resources/views/index.blade.php` | Displays the configured home-page version with a `v` prefix |
| `resources/views/residents/layout.blade.php` | Displays the configured application name and shared footer version |
| `tests/Feature/CsmsApplicationTest.php` | Updates release-version expectations and requires an exact safe health payload |
| `tests/Feature/ReleaseIdentityTest.php` | Verifies visible release identity and shared configuration consumption |
| `tests/Feature/FinalReleaseIntegrationTest.php` | Adds the final persisted cross-module scenario |
| `docs/release-v1.0.0.md` | Records release capabilities, verification results, and limitations |
| `docs/final-checkpoint.md` | Explains the implementation, test, decision, and verification evidence |
| `docs/t16-release-guide.md` | Provides copy-ready commit/PR text and the ordered merge, verification, tag, release, and submission steps |

## 4. My Final Examination Test

**Test Name:**
`test_resident_update_preserves_request_ownership_through_mixed_workflows_and_dashboard_queries`

**Location:** `tests/Feature/FinalReleaseIntegrationTest.php`.

**Modules/Responsibilities Covered:** Resident registration and update; Service
Request submission and ownership; controlled workflow; Resident/request querying;
Dashboard aggregation; and actual SQLite persistence through the existing
controllers, services, repositories, and models.

**Starting State:** `RefreshDatabase` creates an isolated database. The test checks
that both domain tables start empty, then registers two Residents through HTTP
routes and submits three requests: two for Elena and one for Tomas.

**Operations Performed:** Start one of Elena's requests, update her surname and
address while it is In Progress, and verify that the update did not alter any
request. Complete that request, cancel her second request, and attempt an invalid
cancellation of the completed request. Search the updated and old Resident names,
combine request text search with a Completed filter, read request details, and
load the Dashboard. Compare persisted records before and after rejected actions
and reads, including Tomas and his untouched Pending request.

**Expected Final State:** Two Active Residents and three requests remain. Elena's
updated information is saved under the same identity; her requests retain their
ownership and are Completed and Cancelled. Tomas and his Pending request are
unchanged. Dashboard totals are Total 3, Pending 1, In Progress 0, Completed 1,
Cancelled 1, and Open 1, with all three requests in the expected recent order.
The old surname no longer matches, the new surname matches, and the combined
request query returns exactly the completed request. Rejected and read-only
operations leave persisted domain state unchanged.

**Why This Test Is Meaningful:** It verifies the interaction of an in-flight
Resident update with multiple independent request outcomes and an unrelated
Resident's records. Unlike an isolated unit test, it exercises the real routes,
services, repositories, database writes, and subsequent reads without mocking
persistence. It adds a scenario beyond T15's deactivation and recent-five boundary
tests by checking ownership preservation during an update and reconciling mixed
workflow outcomes with search results and Dashboard totals.

## 5. My Final Technical Decision

**Decision:** Keep release metadata in a small checked-in Laravel configuration
file, reused by controllers and Blade views.

**Alternative Considered:** Keep separate version strings in controllers and
templates, or add a dedicated release service for the two metadata values.

**Reason for My Decision:** Separate strings could drift and would require several
edits for the next release. A dedicated service would add unnecessary structure
for fixed metadata. Laravel configuration already provides the needed shared
access and keeps release identity separate from domain logic. The checked-in
value also ties the release version to the source commit rather than allowing a
local environment variable to silently report a different release. If Laravel
configuration has been cached, it must be cleared or rebuilt after deployment.

## 6. Problem or Verification Issue Encountered

**Problem:** The starter application independently reported `0.1.0` in its home
and health controllers, while the shared management layout had no release number.
Changing only `/health` would leave inconsistent release information in the UI.

**Cause or Investigation:** Reading the controllers and views identified both
runtime literals. Before implementation, the release-focused test run failed four
checks: home version, health version, visible UI version, and configuration-change
propagation. The final integration scenario already passed against existing
business behavior, as expected for a regression test during feature freeze.

**Resolution:** Both controllers now read `config/csms.php`, and the home/shared
views display the configured version. All seven focused tests then passed with
84 assertions. The exact health assertion also verifies that extra internal fields
cannot be added unnoticed. The two subsequent full regression runs both passed.

An additional verification obstacle was sandbox access: a full-suite invocation
could not open `vendor/autoload.php`, although the file existed. Running the same
suite with approved access resolved the environment issue without changing source
or dependencies. That failed invocation is not counted as a passing regression run.

## 7. Final Regression Results

Verification date: 2026-11-13 (Asia/Manila).

**Test Command:** `php vendor/phpunit/phpunit/phpunit --colors=never`.

| Run | Result | Total Passing Tests Reported | Assertions |
| --- | --- | --- | --- |
| Final Regression Run 1 | PASS | 238 | 1,373 |
| Final Regression Run 2, immediately after Run 1 | PASS | 238 | 1,373 |

The suite includes Starter through T16 and the T10 student-designed Midterm test.
T16 adds one integration test and two release-identity tests. No earlier test was
deleted, skipped, or disabled. Only the intentionally superseded starter version
expectations changed, with the health assertion strengthened rather than weakened.
The scoped Laravel Pint check and `git diff --check` passed.

Final browser/HTTP acceptance passed for startup, health, visible version,
Dashboard, Resident operations, request submission/query/details, eligibility,
valid workflow, invalid workflow rejection, navigation, and read-only safety.
Detailed results and the isolated-database method are recorded in
[the release notes](release-v1.0.0.md#7-final-verification).

## 8. Tools and References Used

- The T16 examination ticket and existing CSMS T01-T15 code, tests, and release-readiness documentation.
- PHP, Laravel/Blade, SQLite, PHPUnit, Laravel Pint, Git, and local HTTP/browser verification tools.
- [GitHub documentation: managing releases](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository).
- Asked AI For guidance, Codex specifically. AI assistance does not replace my responsibility to review,
  understand, explain, and defend the submitted work.

## 9. Final Release Declaration

The T16 release candidate has passed the recorded automated and manual checks;
no known release-blocking failure remains within the defined scope. Commit, push,
PR review/merge, verification from merged `main`, annotated tagging, and GitHub
Release publication are the remaining developer-controlled release steps. This
declaration records release readiness, not a claim that those steps already happened.

CSMS v1.0.0 - READY FOR RELEASE
