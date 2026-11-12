# T16 Commit, PR, Release, and Submission Guide

The code and release-candidate checks are prepared on `feature/t16-final-release`.
Both final runs passed: **238 tests, 1,373 assertions each**.
The steps below are for the developer to perform in order. No commit, completed-work
push, PR, merge, tag, or GitHub Release was created by this preparation session.

## 1. Review and commit

Read `docs/final-checkpoint.md` and confirm that its explanation reflects your
understanding before submitting it. Review every changed file; do not stage local
databases, runtime files, credentials, or unrelated changes.

```powershell
git branch --show-current
git status
git diff
git diff --check
```

Expected branch: `feature/t16-final-release`. Inspect new files too; ordinary
`git diff` does not display untracked file contents.

Stage the eleven intended files explicitly:

```powershell
git add app/Http/Controllers/HealthController.php app/Http/Controllers/HomeController.php config/csms.php resources/views/index.blade.php resources/views/residents/layout.blade.php tests/Feature/CsmsApplicationTest.php tests/Feature/ReleaseIdentityTest.php tests/Feature/FinalReleaseIntegrationTest.php docs/release-v1.0.0.md docs/final-checkpoint.md docs/t16-release-guide.md
git status
git diff --staged
git diff --staged --check
```

After reviewing the staged changes, use the required commit message:

```powershell
git commit -m "release: prepare csms v1.0.0"
git push -u origin feature/t16-final-release
```

## 2. Open the Pull Request

Repository: <https://github.com/UPHSL/csms-php-luhwiss>

Compare page:
<https://github.com/UPHSL/csms-php-luhwiss/compare/main...feature/t16-final-release>

- Base: `main`
- Compare: `feature/t16-final-release`

Copy this title:

```text
T16 - Final System Release and Development Checkpoint
```

Copy this Markdown description:

```markdown
## Summary

Prepare CSMS v1.0.0 with one authoritative release identity in `config/csms.php`.
The health endpoint, home page, and shared application footer read that configuration.
`/health` reports the application name, version `1.0.0`, and status `ok`; the UI shows `v1.0.0`.

## Final Examination Integration Test

`test_resident_update_preserves_request_ownership_through_mixed_workflows_and_dashboard_queries`

Uses real SQLite persistence to register two Residents, submit three requests,
update a Resident while a request is In Progress, complete/cancel separate requests,
reject a terminal transition, and verify queries, Dashboard totals, ownership,
read-only safety, and preservation of unrelated records.

## Verification

- T15 baseline: PASS — 235 tests, 1,298 assertions.
- Final Regression Run 1: PASS — 238 tests, 1,373 assertions.
- Final Regression Run 2, immediately afterward: PASS — 238 tests, 1,373 assertions.
- Release tests verify the exact safe health payload, visible UI version, and shared configuration consumption.
- Manual browser/HTTP verification: PASS — startup, health, Dashboard, Resident registration/validation/search/update/deactivation, request submission/eligibility/search/filter/details, valid workflow, invalid-transition rejection, navigation, and read-only safety.
- Laravel Pint and whitespace checks: PASS.
- No previous tests removed, disabled, or skipped; starter version expectations updated for the required release.

## Documentation

- `docs/release-v1.0.0.md`
- `docs/final-checkpoint.md`
- `docs/t16-release-guide.md`

## Known Limitations and Release Status

No known release-blocking limitations remain within the defined CSMS v1.0.0 scope.
Existing scope exclusions remain unchanged; no major feature was introduced.

CSMS v1.0.0 - READY FOR RELEASE.

After merge, verify updated `main` with the complete suite and running application
before creating annotated tag `v1.0.0` and publishing GitHub Release `CSMS v1.0.0`.
```

## 3. Review and merge

Review the PR's **Files changed** tab. Confirm the change is limited to T16,
earlier tests remain intact, release documentation matches the implementation,
and no credential, local database, or build/runtime artifact is included. Wait for
required repository checks to pass. Merge the PR into this repository's `main`.

Use **Create a merge commit** if available to preserve the required commit message.
If using squash merge instead, set the squash commit title to
`release: prepare csms v1.0.0` so the required message remains in Git history.
Save the merged PR URL for Moodle.

## 4. Verify merged main before tagging

After GitHub confirms the PR is merged:

```powershell
git switch main
git pull --ff-only origin main
git status
git log -5 --oneline
php artisan config:clear
php vendor/phpunit/phpunit/phpunit --colors=never
```

Confirm the working tree is clean, the T16 changes are present, and the complete
suite passes with 238 tests and 1,373 assertions. Stop if a command fails; do not
tag a failed or unmerged candidate.

Start the application from this merged checkout:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Keep that terminal running. In another terminal:

```powershell
Invoke-RestMethod http://127.0.0.1:8000/health | ConvertTo-Json
```

Expected payload:

```json
{
  "status": "ok",
  "application": "Community Services Management System",
  "version": "1.0.0"
}
```

Open <http://127.0.0.1:8000/dashboard> and verify the metrics and footer `v1.0.0`.
Navigate to Residents and Service Requests and verify they work. Check the home
page version too. Record the merged-main commit SHA and test result for your
release evidence:

```powershell
git rev-parse HEAD
git status --short
```

The second command must print nothing. If configuration is cached in another
environment, clear or rebuild that cache and restart its application process
before verifying the release identity.

## 5. Create and push the annotated tag

Only after the merged-main checks above pass, run:

```powershell
git branch --show-current
git tag --list v1.0.0
```

The branch must be `main`; the tag query should be empty for this first release.
If the tag already exists, inspect it rather than overwriting or moving it.

```powershell
git tag -a v1.0.0 -m "CSMS v1.0.0"
git tag
git cat-file -t v1.0.0
git rev-parse HEAD
git rev-parse 'v1.0.0^{commit}'
git push origin v1.0.0
```

The object type must be `tag`, confirming an annotated tag. The two commit hashes
must match. Do not create this tag while still on the T16 feature branch.

## 6. Publish the GitHub Release

Open <https://github.com/UPHSL/csms-php-luhwiss/releases/new>.

1. Choose the **existing** pushed tag `v1.0.0`.
2. Set the release title to `CSMS v1.0.0`.
3. Paste `docs/release-v1.0.0.md` into the release description.
4. After completing Step 4, add the merged-main verification note below with the actual SHA and results.
5. Leave **This is a pre-release** unchecked. Set it as the latest release if offered.
6. Do not attach unnecessary build artifacts. Preview the notes and publish the release.

Copy this note only after filling in the verified commit SHA and confirming the results:

```markdown
### Official Release Verification

- Verified merged-main commit: <paste the actual commit SHA>
- Full regression from merged main: PASS — 238 tests, 1,373 assertions.
- Application startup and browser navigation: PASS.
- `/health`: version `1.0.0`, status `ok`, correct application identity.
- Home page and shared UI footer: `v1.0.0`.
- Annotated tag `v1.0.0` points to the verified main commit.
```

GitHub's current release workflow is documented in
[Managing releases in a repository](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository).
After publication, verify the release page and tag commit. The expected release
URL is <https://github.com/UPHSL/csms-php-luhwiss/releases/tag/v1.0.0>; it is not
evidence of publication until the release actually exists.

## 7. Moodle submission and technical defense

Submit the repository URL, actual merged T16 PR URL, and published release URL.
Use this text only after completing all steps above:

```text
Name: Constantino, Raxell Louis I.
GitHub Username: luhwiss
Technology Stack: PHP with Laravel, Blade, SQLite, PHPUnit
Final Release Version: v1.0.0
T16 Final Examination Test Name: test_resident_update_preserves_request_ownership_through_mixed_workflows_and_dashboard_queries
Modules/Responsibilities Covered by My Test: Resident registration/update; Service Request submission/ownership/workflow; query/retrieval; Dashboard aggregation; real SQLite persistence.
Final Regression Run 1: PASS - 238 tests, 1,373 assertions
Final Regression Run 2: PASS - 238 tests, 1,373 assertions
Final Release Tag: v1.0.0
Release Status: CSMS v1.0.0 - READY FOR RELEASE
Repository URL: https://github.com/UPHSL/csms-php-luhwiss
Merged T16 PR URL: <paste your actual merged PR URL>
GitHub Release URL: https://github.com/UPHSL/csms-php-luhwiss/releases/tag/v1.0.0
```

For the individual defense, trace `config/csms.php` through the health/home
controllers and shared Blade footer. Explain why the version-override test proves
shared consumption and why the exact JSON assertion guards the public health
payload. Walk through the integration test's two Residents, three requests,
Resident update, terminal rejection, unchanged-record comparisons, and final
Dashboard counts. Demonstrate the application, full suite, merged PR, annotated
tag, and published release. Be prepared to explain the documented technical
decision and the old-version consistency problem in your own words.
