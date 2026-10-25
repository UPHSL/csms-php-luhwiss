# T11 - Search and View Service Requests

T11 adds read-only queries over the existing T09 Service Request persistence.
It reuses the `ServiceRequest` model and `ServiceRequestRepository`; T10 remains
responsible for lifecycle transitions.

## Query API

Laravel can resolve `App\Services\ServiceRequestQueryService` through its container:

```php
$queries = app(\App\Services\ServiceRequestQueryService::class);

$all = $queries->listServiceRequests();
$request = $queries->viewServiceRequest(25);
$matches = $queries->searchServiceRequests('  cLeAr  ');
$pending = $queries->searchServiceRequests(null, 'Pending');
$completed = $queries->searchServiceRequests('employment', 'Completed');
```

- Listing and searching return Eloquent collections of persisted `ServiceRequest`
  models. Empty databases and queries without matches return empty collections.
- Every collection is ordered by `date_requested DESC`, then `id DESC`.
- Viewing delegates to the existing repository `findById()` and returns `null`
  when the ID does not exist. It never creates a placeholder or consumes an ID.
- Search trims leading/trailing input whitespace and matches a partial,
  case-insensitive substring in `service_type` **or** `description`.
- `null`, empty, and whitespace-only text inputs act as listing; an accompanying
  status filter still applies. Calling `searchServiceRequests()` lists all records.
- Only `Pending`, `In Progress`, `Completed`, and `Cancelled` are supported status
  filters. `null` or `''` means no filter. Other nonempty values, including wrong
  capitalization or whitespace-only values, throw `InvalidArgumentException`
  with the message `Unsupported Service Request status filter.`
- Combined queries require `(service_type match OR description match) AND status
  match`. A request matching both searchable fields appears once.
- Unfiltered queries include all four statuses. Completed and Cancelled requests
  remain available to listing, searching, filtering, and viewing.

The repository applies `WHERE` conditions and ordering in SQL through Eloquent.
Search uses `LOWER(...) LIKE ?` with parameter binding. Status values are also
bound. `%`, `_`, and the escape character `!` in input are escaped so they match
literal text. Search never loads the whole table for application-memory filtering.
Case conversion follows the selected database's support; the integration suite
uses the project's existing SQLite test database configuration.

All reads preserve `id`, `resident_id`, `service_type`, `description`,
`date_requested`, and `status`. They do not update requests or residents.
There are no new routes, controllers, pages, pagination, schema changes, or T12
features.

## Automated coverage

`tests/Feature/ServiceRequestQueryServiceTest.php` uses the established database
migrations and persists fixtures through the T09 repository.

| Required scenario | Test method (prefix `test_`) |
| --- | --- |
| 1. List persisted requests | `lists_all_persisted_service_requests` |
| 2. Empty listing | `empty_listing_returns_an_empty_collection` |
| 3. Date/ID ordering | `listing_orders_by_request_date_descending_then_id_descending` |
| 4. View existing ID | `views_an_existing_service_request_by_id` |
| 5. Missing ID without inserts | `viewing_an_unknown_id_returns_null_without_creating_a_record` |
| 6. Partial case-insensitive service type | `partial_service_type_search_is_case_insensitive` |
| 7. Partial case-insensitive description | `partial_description_search_is_case_insensitive` |
| 8. Blank search equals listing | `blank_search_returns_the_normal_ordered_listing` |
| 9. No matching text | `search_without_matches_returns_an_empty_collection` |
| 10. Status filtering | `status_filter_returns_only_the_matching_status` |
| 11. All statuses when unfiltered | `unfiltered_queries_include_all_four_lifecycle_states` |
| 12. Combined text/status | `combined_search_requires_text_and_status_to_match` |
| 13. No duplicated matches | `request_matching_both_searchable_fields_is_not_duplicated` |
| 14. Complete information preserved | `queries_preserve_complete_information_for_each_status` |

Additional tests cover trimmed input, rejected statuses, status filtering with
blank text, empty filtered results, ordering across query modes, exclusion of
unrelated searchable fields, read-only SQL and database snapshots, bound SQL
parameters, and literal wildcard characters. Data providers exercise all four
statuses and multiple blank/invalid inputs.

## Verification - October 25, 2026

- Baseline: updated clean `main` at `14aa8f7`, containing merged T10 PR #13.
- Baseline suite: **110 tests, 375 assertions passed** before T11 code changes.
- Required branch: `feature/t11-service-request-search-view`, published before
  development.
- Test-first check: all 39 new cases initially failed because the query service
  did not exist.
- T11: **39 tests, 167 assertions passed**.
- Complete Starter through T11 regression: **149 tests, 542 assertions passed**,
  including the T10 student-designed transition test.
- Laravel Pint passed for all three changed PHP files.
- Before development, HTTP requests to `/` and `/health` returned 200; `/` displayed
  the application name.
- After implementation, both routes again returned HTTP 200. `/health` returned
  `{"status":"ok","application":"Community Services Management System","version":"0.1.0"}`.

Reproduce automated verification:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/ServiceRequestQueryServiceTest.php
php artisan test --compact
php vendor/bin/pint --test app/Repositories/ServiceRequestRepository.php app/Services/ServiceRequestQueryService.php tests/Feature/ServiceRequestQueryServiceTest.php
```

The Moodle evidence is the personal repository URL and the merged T11 pull
request URL. Do not upload a ZIP unless the course separately requests one.
