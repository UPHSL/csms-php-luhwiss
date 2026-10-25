<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ServiceRequestRepository;
use App\Services\ServiceRequestQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceRequestQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequestQueryService $service;

    private Resident $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ServiceRequestQueryService(new ServiceRequestRepository);
        $this->resident = Resident::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contact_number' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ]);
    }

    private function persistRequest(array $overrides = []): ServiceRequest
    {
        return (new ServiceRequestRepository)->save(new ServiceRequest(array_merge([
            'resident_id' => $this->resident->id,
            'service_type' => 'Barangay Clearance',
            'description' => 'Employment requirement',
            'date_requested' => '2026-09-15',
            'status' => 'Pending',
        ], $overrides)));
    }

    public function test_lists_all_persisted_service_requests(): void
    {
        $first = $this->persistRequest();
        $second = $this->persistRequest(['service_type' => 'Community Assistance']);

        $results = $this->service->listServiceRequests();

        $this->assertCount(2, $results);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $results->modelKeys());
    }

    public function test_empty_listing_returns_an_empty_collection(): void
    {
        $this->assertSame([], $this->service->listServiceRequests()->all());
    }

    public function test_listing_orders_by_request_date_descending_then_id_descending(): void
    {
        $older = $this->persistRequest();
        $newer = $this->persistRequest(['date_requested' => '2026-09-18']);
        $sameDateHigherId = $this->persistRequest(['date_requested' => '2026-09-18']);
        $oldest = $this->persistRequest(['date_requested' => '2026-09-10']);

        $this->assertSame(
            [$sameDateHigherId->id, $newer->id, $older->id, $oldest->id],
            $this->service->listServiceRequests()->modelKeys()
        );
    }

    public function test_views_an_existing_service_request_by_id(): void
    {
        $saved = $this->persistRequest();
        $this->persistRequest();

        $found = $this->service->viewServiceRequest($saved->id);

        $this->assertInstanceOf(ServiceRequest::class, $found);
        $this->assertSame($saved->fresh()->getAttributes(), $found->getAttributes());
    }

    public function test_viewing_an_unknown_id_returns_null_without_creating_a_record(): void
    {
        $saved = $this->persistRequest();
        $before = ServiceRequest::orderBy('id')->get()->toArray();

        $this->assertNull($this->service->viewServiceRequest(999999));
        $this->assertSame($before, ServiceRequest::orderBy('id')->get()->toArray());
        $this->assertSame($saved->id + 1, $this->persistRequest()->id);
    }

    public function test_partial_service_type_search_is_case_insensitive(): void
    {
        $saved = $this->persistRequest();
        $this->persistRequest(['service_type' => 'Community Assistance']);

        $this->assertSame([$saved->id], $this->service->searchServiceRequests('cLeAr')->modelKeys());
    }

    public function test_partial_description_search_is_case_insensitive(): void
    {
        $saved = $this->persistRequest();
        $this->persistRequest(['description' => 'Scholarship requirement']);

        $this->assertSame([$saved->id], $this->service->searchServiceRequests('pLoYm')->modelKeys());
    }

    public static function blankSearches(): array
    {
        return [[null], [''], [' '], ['      '], ["\t\n\r"]];
    }

    #[DataProvider('blankSearches')]
    public function test_blank_search_returns_the_normal_ordered_listing(?string $term): void
    {
        $this->persistRequest();
        $this->persistRequest(['date_requested' => '2026-09-18']);

        $this->assertSame(
            $this->service->listServiceRequests()->modelKeys(),
            $this->service->searchServiceRequests($term)->modelKeys()
        );
    }

    public function test_search_without_matches_returns_an_empty_collection(): void
    {
        $this->persistRequest();

        $this->assertSame([], $this->service->searchServiceRequests('unmatched')->all());
    }

    public static function statuses(): array
    {
        return [['Pending'], ['In Progress'], ['Completed'], ['Cancelled']];
    }

    #[DataProvider('statuses')]
    public function test_status_filter_returns_only_the_matching_status(string $status): void
    {
        $expected = null;
        foreach (self::statuses() as [$candidate]) {
            $saved = $this->persistRequest(['status' => $candidate]);
            if ($candidate === $status) {
                $expected = $saved->id;
            }
        }

        $results = $this->service->searchServiceRequests(null, $status);

        $this->assertSame([$expected], $results->modelKeys());
        $this->assertSame([$status], $results->pluck('status')->all());
    }

    public function test_unfiltered_queries_include_all_four_lifecycle_states(): void
    {
        foreach (self::statuses() as [$status]) {
            $this->persistRequest(['status' => $status]);
        }

        foreach ([
            $this->service->listServiceRequests(),
            $this->service->searchServiceRequests('clear'),
            $this->service->searchServiceRequests(null, ''),
        ] as $results) {
            $this->assertEqualsCanonicalizing(
                ['Pending', 'In Progress', 'Completed', 'Cancelled'],
                $results->pluck('status')->all()
            );
        }
    }

    public function test_combined_search_requires_text_and_status_to_match(): void
    {
        $typeMatch = $this->persistRequest();
        $descriptionMatch = $this->persistRequest([
            'service_type' => 'Certification', 'description' => 'Clearance requirement',
        ]);
        $this->persistRequest(['status' => 'Completed']);
        $this->persistRequest([
            'service_type' => 'Community Assistance', 'status' => 'Cancelled',
        ]);
        $this->persistRequest(['service_type' => 'Community Assistance']);

        $this->assertSame(
            [$descriptionMatch->id, $typeMatch->id],
            $this->service->searchServiceRequests('clear', 'Pending')->modelKeys()
        );
    }

    public function test_request_matching_both_searchable_fields_is_not_duplicated(): void
    {
        $saved = $this->persistRequest([
            'service_type' => 'Community Assistance',
            'description' => 'Assistance requested for medical support',
        ]);

        $this->assertSame([$saved->id], $this->service->searchServiceRequests('assistance')->modelKeys());
    }

    #[DataProvider('statuses')]
    public function test_queries_preserve_complete_information_for_each_status(string $status): void
    {
        $saved = $this->persistRequest(['status' => $status]);

        foreach ([
            $this->service->listServiceRequests()->first(),
            $this->service->viewServiceRequest($saved->id),
            $this->service->searchServiceRequests('clear', $status)->first(),
        ] as $result) {
            $this->assertInstanceOf(ServiceRequest::class, $result);
            foreach (['id', 'resident_id', 'service_type', 'description', 'date_requested', 'status'] as $field) {
                $this->assertSame($saved->$field, $result->$field, $field);
            }
        }
    }

    public function test_search_trims_input_without_changing_stored_text(): void
    {
        $saved = $this->persistRequest(['description' => '  Employment requirement  ']);

        $this->assertSame(
            [$saved->id],
            $this->service->searchServiceRequests(" \t cLeAr \n ")->modelKeys()
        );
        $this->assertSame('  Employment requirement  ', $saved->fresh()->description);
    }

    public static function unsupportedStatuses(): array
    {
        return [['Approved'], ['Rejected'], ['Closed'], ['Done'], ['Processing'], ['pending'], [' ']];
    }

    #[DataProvider('unsupportedStatuses')]
    public function test_unsupported_nonempty_status_is_rejected(string $status): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported Service Request status filter.');

        $this->service->searchServiceRequests(null, $status);
    }

    public function test_blank_search_keeps_the_status_filter(): void
    {
        $pending = $this->persistRequest();
        $this->persistRequest(['status' => 'Completed']);

        $this->assertSame(
            [$pending->id],
            $this->service->searchServiceRequests('   ', 'Pending')->modelKeys()
        );
    }

    public function test_status_and_combined_queries_without_matches_return_empty_collections(): void
    {
        $this->persistRequest();

        $this->assertSame([], $this->service->searchServiceRequests(null, 'Cancelled')->all());
        $this->assertSame([], $this->service->searchServiceRequests('missing', 'Pending')->all());
    }

    public function test_search_and_filter_results_use_the_required_ordering(): void
    {
        $older = $this->persistRequest();
        $newer = $this->persistRequest(['date_requested' => '2026-09-18']);
        $tie = $this->persistRequest(['date_requested' => '2026-09-18']);
        $oldest = $this->persistRequest(['date_requested' => '2026-09-10']);

        foreach ([['clear', null], [null, 'Pending'], ['clear', 'Pending']] as [$term, $status]) {
            $this->assertSame(
                [$tie->id, $newer->id, $older->id, $oldest->id],
                $this->service->searchServiceRequests($term, $status)->modelKeys()
            );
        }
    }

    public function test_general_text_search_does_not_search_unrelated_fields(): void
    {
        $saved = $this->persistRequest();

        foreach ([(string) $saved->id, (string) $this->resident->id, '2026-09-15', 'Pending', 'Juan', 'Santo Tomas', '09171234567'] as $term) {
            $this->assertSame([], $this->service->searchServiceRequests($term)->all(), $term);
        }
    }

    public function test_queries_are_read_only_for_requests_and_residents(): void
    {
        foreach (self::statuses() as [$status]) {
            $this->persistRequest(['status' => $status]);
        }
        $beforeRequests = ServiceRequest::orderBy('id')->get()->toArray();
        $beforeResidents = Resident::orderBy('id')->get()->toArray();
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->service->listServiceRequests();
        foreach (self::statuses() as [$status]) {
            $results = $this->service->searchServiceRequests('clear', $status);
            $this->service->viewServiceRequest($results->first()->id);
        }
        $this->service->viewServiceRequest(999999);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertStringStartsWith('select ', strtolower($query['query']));
        }
        $this->assertSame($beforeRequests, ServiceRequest::orderBy('id')->get()->toArray());
        $this->assertSame($beforeResidents, Resident::orderBy('id')->get()->toArray());
    }

    public function test_search_and_status_filter_are_bound_in_the_database_query(): void
    {
        $this->persistRequest();
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->service->searchServiceRequests("x' OR 1=1 --", 'Pending');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $sql = strtolower($queries[0]['query']);
        $this->assertStringContainsString('where', $sql);
        $this->assertStringContainsString('lower(service_type)', $sql);
        $this->assertStringContainsString('lower(description)', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringNotContainsString("x' OR 1=1 --", $queries[0]['query']);
        $this->assertSame(["%x' or 1=1 --%", "%x' or 1=1 --%", 'Pending'], $queries[0]['bindings']);
        $this->assertSame([], $this->service->searchServiceRequests("x' OR 1=1 --")->all());
    }

    public function test_sql_wildcard_characters_are_matched_as_literal_text(): void
    {
        $saved = $this->persistRequest(['description' => '100% support_for families!']);
        $this->persistRequest(['description' => '1000 support for families']);

        foreach (['100%', 'support_', 'families!'] as $term) {
            $this->assertSame([$saved->id], $this->service->searchServiceRequests($term)->modelKeys());
        }
    }
}
