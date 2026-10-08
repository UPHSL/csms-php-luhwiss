# Midterm Checkpoint

## Developer Information

Name: Raxell Constantino
GitHub Username: luhwiss
Primary Technology Stack: PHP with Laravel
T10 Branch: feature/t10-service-request-status

## My T10 Implementation

My T10 implementation adds a service-layer component named `ServiceRequestStatusService` to manage the Service Request status workflow. The service receives an existing Service Request ID and a requested target status, then retrieves the current persisted Service Request through `ServiceRequestRepository`. It checks whether the requested status is one of the supported T10 statuses before checking whether the current persisted status can transition to that target. Valid transitions are defined in the service as a small workflow map, so the repository is not responsible for business-rule decisions. If the request is missing, unsupported, or invalid, the service returns a `ServiceRequestStatusResult` without asking the repository to save anything. For a valid transition, the service calls the repository's `updateStatus` method, which updates only the `status` field on the existing persisted Service Request. The updated persisted Service Request is returned through the successful result so the caller can inspect the final saved state.

## My Transition Rules

The implementation allows `Pending` to move to `In Progress` and `Pending` to move to `Cancelled`. It also allows `In Progress` to move to `Completed` and `In Progress` to move to `Cancelled`. `Pending` to `Completed` is rejected because a request must first enter `In Progress` before it can be completed. `Completed` is terminal because it represents a finished Service Request workflow, so it cannot move back to another status. `Cancelled` is also terminal because T10 does not implement reopening cancelled requests. Same-status requests such as `Pending` to `Pending` are rejected as invalid transitions because they do not represent an actual workflow change.

## Files I Changed

File: `app/Services/ServiceRequestStatusService.php`
Purpose: Implements the T10 application-layer workflow by finding the existing Service Request, validating the requested status, enforcing allowed transitions, and only saving valid status changes.

File: `app/Services/ServiceRequestStatusResult.php`
Purpose: Provides structured results for status-management outcomes, including success, not found, unsupported status, and invalid transition.

File: `app/Repositories/ServiceRequestRepository.php`
Purpose: Adds a repository method that updates only the persisted Service Request status while preserving the existing Service Request record and all other fields.

File: `tests/Feature/ServiceRequestStatusServiceTest.php`
Purpose: Covers the required T10 transition, terminal-state, unsupported-status, not-found, persistence-preservation, and student-designed scenarios.

File: `docs/midterm-checkpoint.md`
Purpose: Documents the implementation, transition rules, changed files, real problem encountered, student-designed test, and tools used for the Midterm Examination.

## Problem I Encountered

While verifying the project, `php artisan test` and PHPUnit with the XML bootstrap reported that they could not open `vendor/autoload.php`. The file existed and PHP could load it directly with `require`, but PHPUnit's bootstrap readability check still failed in this Windows/Codex environment. I investigated by checking that `vendor/autoload.php` existed, reading its first lines, confirming PHP could `require` it, and checking the file permissions. To keep verification meaningful, I ran PHPUnit through `vendor/bin/phpunit` with `--no-configuration` and manually supplied the same testing environment values from `phpunit.xml`, which allowed the Composer proxy to load autoload and run the test suites correctly.

## My Student-Designed Test

Test Name: `test_student_designed_transition_only_updates_target_request`

What the Test Verifies: This test creates two persisted Service Requests, changes the target request from `Pending` to `In Progress`, and verifies that the other request remains `Pending` with its own service type and description unchanged.

Why I Added This Test: I added this test to confirm that the status update targets only the requested Service Request ID and does not accidentally modify another persisted Service Request.

## Tools and References Used

I used PHP, Laravel, Composer, PHPUnit, Git, PowerShell, and the existing project tests and code as references. I also used Codex as an AI coding assistant to inspect the repository, implement the T10 workflow, write tests, and prepare this checkpoint document. I remain responsible for understanding and explaining the submitted implementation.
