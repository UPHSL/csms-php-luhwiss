# T15 Release Readiness

## 1. Developer Information

- Name: Constantino, Raxell Louis I.
- GitHub Username: luhwiss
- Technology Stack: PHP with Laravel, SQLite
- T15 Branch: `feature/t15-system-hardening`

## 2. Release Candidate Baseline

Verified on 2026-11-03 before changing files. T14 was merged in PR #17,
and local `main` was updated to `b89aa0b` with a clean working tree.

| Baseline check | Result |
| --- | --- |
| Application startup | PASS; HTTP 200 at `/` |
| `/health` | PASS; HTTP 200 |
| Dashboard | PASS; HTTP 200 |
| Resident Management | PASS; HTTP 200 |
| Service Request Management | PASS; HTTP 200 |
| Starter through T14 tests | PASS; 230 tests, 1,190 assertions |

Command: `php vendor/phpunit/phpunit/phpunit --colors=never`.
The required branch was created from this baseline and published before development.

## 3. Hardening Changes

All nine required areas were reviewed under feature freeze. The only application
change is an error view; no transactional rules, query algorithms, schemas,
statuses, or major features were changed.

| Area | Issue or Risk Found | Action Taken | Files Changed | Test/Evidence |
| --- | --- | --- | --- | --- |
| 1. Full regression behavior | New hardening must preserve Starter through T14 | Kept all existing tests and assertions; ran the full suite twice consecutively | `tests/Feature/SystemHardeningTest.php`; this report | Both runs: 235 passing tests, 1,298 assertions |
| 2. Invalid and missing input | With debug enabled, a wrong-method request to a mutation URL exposed Laravel's debug page instead of controlled application feedback | Added a shared-layout 405 page; retained HTTP 405 and existing routing rejection | `resources/views/errors/405.blade.php`; `tests/Feature/SystemHardeningTest.php` | Category 1 failed before the view existed and passed afterward; malformed IDs still return 404 |
| 3. Persistence integrity | Multi-field malformed updates must not partially save valid fields or reactivate an Inactive Resident | Added an integration regression comparing all Resident and Service Request fields before/after rejection | `tests/Feature/SystemHardeningTest.php` | Category 2; manual valid/invalid update probes |
| 4. HTTP and mutation safety | Wrong-method GETs and repeated reads must not mutate domain state | Retained POST/PUT routes, CSRF forms, and redirects; verified repeated cross-module GETs and direct mutation GET rejection | `tests/Feature/SystemHardeningTest.php`; 405 view | Categories 1 and 3; snapshots of every persisted domain field remained identical |
| 5. Query and database safety | Quotes and SQL-like search text must remain data | Reviewed ORM bindings and both repositories' parameterized raw predicates; added hostile/quoted search regression; retained database COUNT queries and recent LIMIT | `tests/Feature/SystemHardeningTest.php` | Category 3; existing query tests and Dashboard aggregate-query test passed |
| 6. Workflow and lifecycle protection | Deactivation must block future submissions without erasing history; replayed terminal operations must not change records | Added lifecycle integration and recent-boundary/replay regressions; retained T09 eligibility and T10 transitions | `tests/Feature/SystemHardeningTest.php` | Categories 4 and 5; manual Completed/Cancelled direct actions rejected |
| 7. Interface and navigation consistency | Expected HTTP errors must use understandable feedback | Reused the existing layout and navigation in the 405 view; checked forms, labels, escaped output, status text, and module links | `resources/views/errors/405.blade.php`; `tests/Feature/SystemHardeningTest.php` | Browser inspection and all twelve manual scenarios passed |
| 8. Test reliability | Shared persistence, generated-ID assumptions, or clock leakage can make tests order-dependent | New tests use RefreshDatabase, in-memory SQLite, generated model IDs, fixed request dates, and restored test time; no server dependency in PHPUnit | `tests/Feature/SystemHardeningTest.php` | Two immediate complete runs passed; no old tests deleted, disabled, skipped, or weakened |
| 9. Repository and release hygiene | Local review databases, scripts, credentials, and runtime output must stay outside the submitted change | Reviewed tracked files and ignores, debug patterns, starter placeholders, and credential patterns; no accidental tracked artifact identified | This report | Final change list contains only the 405 view, five-test class, and this report; temporary HTTP review assets stayed outside the repository |

## 4. Five New T15 Hardening Tests

All tests are in `tests/Feature/SystemHardeningTest.php`.

| Test Name | Category | What It Verifies | Why It Adds Value Beyond Existing Tests |
| --- | --- | --- | --- |
| `test_malformed_identifiers_and_wrong_http_methods_have_safe_feedback_even_in_debug_mode` | 1. Invalid or malformed input | Malformed paths return controlled 404; GET mutation URLs return an understandable 405 without stack traces, even with app.debug=true; all domain fields remain unchanged | Earlier tests asserted 405 status but did not verify debug-mode error-page disclosure or application feedback |
| `test_rejected_malformed_update_preserves_inactive_resident_and_unrelated_records` | 2. Rejected mutation protects persistence | Nested contact-number input plus a valid changed name and forged identity/status cannot partially save, reactivate, or alter another record; redisplayed script text is escaped | Combines malformed form shape, multiple attempted changes, lifecycle protection, escaped retained input, and whole-database comparison |
| `test_quoted_and_hostile_searches_remain_read_only_across_both_modules_and_dashboard` | 3. Read-only request safety | Apostrophes match as data; SQL-like and script search input yields safe no-match results; repeated reads across both modules, details, and Dashboard preserve all domain data | Earlier read-only tests did not combine hostile queries, apostrophe matching, mixed-module repetition, and whole-domain snapshots |
| `test_deactivation_blocks_future_submissions_but_preserves_request_history_and_dashboard` | 4. Cross-feature regression | Browser registration/submission followed by deactivation blocks a later submission while preserving historical requests, Resident search, details, and current Dashboard counts | Exercises T04/T07/T09/T11/T12/T13/T14 as one lifecycle instead of independently testing an already-Inactive fixture |
| `test_workflow_updates_do_not_promote_old_requests_into_recent_five` | 5. Repeatability or boundary behavior | Among six same-date requests, later processing/completion of the excluded oldest request cannot displace the fifth recent result; terminal replay is rejected unchanged and the completed record remains queryable | Tests the interaction of updated timestamps, same-date ID ordering, the exact five/six boundary, terminal replay, and aggregate refresh |

The five-test run passed with 108 assertions. Test 1 reproduced the debug-page
issue before the fix; the other four were passing defensive regressions.

## 5. Full Regression Test Results

Command used for each complete run:

```text
php vendor/phpunit/phpunit/phpunit --colors=never
```

| Run | Result | Total passing tests | Assertions |
| --- | --- | --- | --- |
| Run 1 | PASS | 235 | 1,298 |
| Run 2, immediately after Run 1 | PASS | 235 | 1,298 |

Both runs included Starter through T15, including the T10 student-designed test.
The baseline had 230 tests; T15 adds exactly five. PHPUnit uses an isolated
in-memory SQLite database. Formatting and `git diff --check` also passed.

The required additional complete test run **after merging into main** remains a
developer follow-up; no T15 commit, completed-work push, PR, or merge was performed
by the coding assistant.

## 6. Manual Release Verification

Verified on 2026-11-03 using a temporary SQLite database and PHP HTTP server.
Real HTTP form submissions used session cookies and CSRF tokens, followed actual
redirects, and were checked against fresh persisted-record snapshots. Browser
inspection additionally verified Dashboard, shared navigation, Resident and
Service Request screens, details, and the controlled error page. All twelve
required scenarios were exercised. The permanent development database was not
used for review mutations.

| Verification | Result | Notes |
| --- | --- | --- |
| Application starts | PASS | PHP server served `/` successfully before and after review |
| `/health` works | PASS | HTTP 200 |
| Dashboard loads | PASS | HTTP 200; counts reconciled with the isolated database |
| Resident listing/search works | PASS | Registered and deactivated Resident remained listed and searchable |
| Valid Resident registration works | PASS | Generated positive ID, Active status, persisted fields, and success feedback verified |
| Invalid Resident registration is rejected | PASS | Whitespace first name produced feedback and unchanged Resident persistence |
| Resident update works | PASS | Address changed with identity/status preserved; invalid contact plus proposed name left the entire original record unchanged |
| Resident deactivation works and record remains visible | PASS | Active became Inactive without deletion; repeated deactivation returned already-Inactive feedback |
| Valid Service Request submission for Active Resident works | PASS | Three valid submissions generated IDs, retained the Resident relationship, and began Pending |
| Missing/Inactive Resident submission is rejected | PASS | Both produced eligibility feedback and no additional Service Requests |
| Service Request search/filter/details work | PASS | List, partial case-insensitive description search, status filter, AND combination, and details verified |
| Valid Service Request workflow works | PASS | Pending -> In Progress -> Completed; separate Pending -> Cancelled; status and Dashboard changes verified |
| Invalid/terminal transition is rejected | PASS | Direct Pending -> Completed and every action against Completed/Cancelled rejected; all fields unchanged |
| Dashboard reflects Resident/Service Request changes | PASS | Deactivation changed Active/Inactive with Total unchanged; workflow changed status counts and terminal completion reduced Open |
| Read-only GET operations do not modify persistence | PASS | Three repeated rounds of Dashboard, Resident search, Service Request search/filter, and details left every domain field unchanged |

Additional checks: missing, malformed, and oversized record IDs returned safe
404 pages; direct GET requests to mutation URLs returned controlled 405 pages.
Completed and Cancelled records remained viewable and searchable/filterable.
Shared navigation connected Dashboard, Residents, and Service Requests without
typing URLs. Successful POST/PUT operations redirected to read-only pages;
repeated GETs did not repeat transactions.

## 7. Repository Hygiene Review

| Check | Result / cleanup |
| --- | --- |
| Debug output | Reviewed `app`, `routes`, and `resources`; no accidental dd/dump/var_dump/print_r/console.log identified |
| Temporary files | Review database and HTTP probe script were created in the OS temporary directory, outside tracked project files |
| Generated build/runtime artifacts | Existing ignores cover vendor, node_modules, build output, caches, logs, and editor files; none added to the release change |
| Accidental local database files | No SQLite/database copies tracked; existing database ignore patterns retained |
| Commented-out abandoned code | No newly abandoned implementation; intentionally supplied starter placeholder classes retained |
| Secrets/credentials | Reviewed tracked file inventory, configuration examples, and filename-only credential/private-key pattern results; no private credential identified; local `.env` remains ignored |
| Broken navigation/links | Browser navigation and HTTP checks passed; new 405 page links back to Dashboard |
| Direct SQL parameter binding | Both repositories bind dynamic LIKE patterns; ORM handles IDs, updates, status filters, and aggregate counts; static ordering contains no user-controlled SQL |

No repository files needed deletion. No old test or assertion was changed.
Only three new T15 files are intended for staging. Temporary review assets and
runtime-generated files are not part of the release candidate.

## 8. Known Limitations

No known release-blocking limitations identified within the defined CSMS scope.

Authentication, authorization, reactivation, physical deletion, exports, and
advanced reporting remain outside scope and were not added. This assessment is
based on the stated checks; it is not a claim that the software has no possible
defects.

## 9. Release Candidate Statement

READY FOR T16 FINAL EXAM

The scoped release candidate passed both full regression runs, all five T15
hardening tests, and the required release-verification scenarios. No known
release-blocking failure remains.

The empty branch was published before development, as requested. The developer
will stage/review the three intended files, commit using
`chore: harden csms for release`, push the completed changes, create and review
`T15 - System Hardening and Regression Testing` against the personal repository's
`main`, merge, update local `main`, rerun the entire suite from merged `main`, and
submit the repository URL, merged PR URL, and Moodle release-status text.

Moodle release-status text to accompany the repository URL and the merged PR URL:

```text
Name: Constantino, Raxell Louis I.
GitHub Username: luhwiss
Technology Stack: PHP with Laravel
T15 Hardening Test 1: test_malformed_identifiers_and_wrong_http_methods_have_safe_feedback_even_in_debug_mode
T15 Hardening Test 2: test_rejected_malformed_update_preserves_inactive_resident_and_unrelated_records
T15 Hardening Test 3: test_quoted_and_hostile_searches_remain_read_only_across_both_modules_and_dashboard
T15 Hardening Test 4: test_deactivation_blocks_future_submissions_but_preserves_request_history_and_dashboard
T15 Hardening Test 5: test_workflow_updates_do_not_promote_old_requests_into_recent_five
Regression Run 1: PASS - 235 tests, 1,298 assertions
Regression Run 2: PASS - 235 tests, 1,298 assertions
Release Status: READY FOR T16 FINAL EXAM
```
