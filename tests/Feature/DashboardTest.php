<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Services\DashboardService;
use App\Services\ResidentDeactivationService;
use App\Services\ServiceRequestStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function resident(string $status = 'Active'): Resident
    {
        return Resident::create([
            'first_name' => 'Maria', 'last_name' => 'Santos',
            'address' => 'Barangay Santo Tomas', 'contact_number' => '09171234567',
            'email' => 'maria@example.com', 'status' => $status,
        ]);
    }

    private function serviceRequest(array $overrides = []): ServiceRequest
    {
        return ServiceRequest::create(array_merge([
            'resident_id' => Resident::first()?->id ?? $this->resident()->id,
            'service_type' => 'Barangay Clearance', 'description' => 'Employment requirements.',
            'date_requested' => '2026-10-30', 'status' => 'Pending',
        ], $overrides));
    }

    private function summary(): array
    {
        return app(DashboardService::class)->getSummary();
    }

    public function test_dashboard_loads_with_required_sections_and_module_navigation(): void
    {
        $this->get('/dashboard')->assertOk()->assertSee('CSMS Dashboard')
            ->assertSee('Resident Summary')->assertSee('Service Request Summary')
            ->assertSee('Open Service Requests')->assertSee('Recent Service Requests')
            ->assertSee('href="http://localhost/residents"', false)
            ->assertSee('href="http://localhost/service-requests"', false);
    }

    public function test_empty_dashboard_displays_zero_for_every_metric_and_safe_empty_state(): void
    {
        $summary = $this->summary();
        $this->assertSame(['total' => 0, 'active' => 0, 'inactive' => 0], $summary['residents']);
        $this->assertSame([
            'total' => 0, 'pending' => 0, 'in_progress' => 0,
            'completed' => 0, 'cancelled' => 0, 'open' => 0,
        ], $summary['serviceRequests']);
        $this->assertCount(0, $summary['recentServiceRequests']);
        $response = $this->get('/dashboard')->assertOk()->assertSee('No Service Requests available.');
        $this->assertSame(9, substr_count($response->getContent(), 'class="metric-value">0<'));
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_total_residents_includes_active_and_inactive_persisted_records(): void
    {
        $this->resident();
        $this->resident();
        $this->resident('Inactive');
        $this->assertSame(3, $this->summary()['residents']['total']);
        $this->get('/dashboard')->assertOk()->assertSeeInOrder(['Total Residents', 'class="metric-value">3<'], false);
    }

    public function test_resident_status_counts_are_correct_and_reconcile(): void
    {
        $this->resident();
        $this->resident();
        $this->resident('Inactive');
        $counts = $this->summary()['residents'];
        $this->assertSame(['total' => 3, 'active' => 2, 'inactive' => 1], $counts);
        $this->assertSame($counts['total'], $counts['active'] + $counts['inactive']);
        $this->get('/dashboard')->assertViewHas('residents', $counts);
    }

    public function test_total_service_requests_includes_every_status(): void
    {
        foreach (['Pending', 'In Progress', 'Completed', 'Cancelled', 'Pending'] as $status) {
            $this->serviceRequest(['status' => $status]);
        }
        $this->assertSame(5, $this->summary()['serviceRequests']['total']);
        $this->get('/dashboard')->assertOk()->assertSeeInOrder(['Total Service Requests', 'class="metric-value">5<'], false);
    }

    public function test_service_request_status_counts_match_persistence(): void
    {
        foreach (['Pending', 'Pending', 'In Progress', 'Completed', 'Cancelled', 'Cancelled'] as $status) {
            $this->serviceRequest(['status' => $status]);
        }
        $expected = ['total' => 6, 'pending' => 2, 'in_progress' => 1, 'completed' => 1, 'cancelled' => 2, 'open' => 3];
        $this->assertSame($expected, $this->summary()['serviceRequests']);
        $this->get('/dashboard')->assertOk()->assertViewHas('serviceRequests', $expected);
    }

    public function test_service_request_total_reconciles_with_the_four_status_counts(): void
    {
        foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $status) {
            $this->serviceRequest(['status' => $status]);
        }
        $counts = $this->summary()['serviceRequests'];
        $this->assertSame(4, $counts['total']);
        $this->assertSame($counts['total'], $counts['pending'] + $counts['in_progress'] + $counts['completed'] + $counts['cancelled']);
    }

    public function test_open_count_is_pending_plus_in_progress_and_excludes_terminal_requests(): void
    {
        foreach (['Pending', 'Pending', 'In Progress', 'Completed', 'Cancelled'] as $status) {
            $this->serviceRequest(['status' => $status]);
        }
        $counts = $this->summary()['serviceRequests'];
        $this->assertSame(3, $counts['open']);
        $this->assertSame($counts['pending'] + $counts['in_progress'], $counts['open']);
        $this->get('/dashboard')->assertOk()->assertSeeInOrder(['Open Service Requests', 'class="metric-value">3<'], false);
        $this->assertDatabaseMissing('service_requests', ['status' => 'Open']);
    }

    public function test_existing_resident_deactivation_is_reflected_after_dashboard_refresh(): void
    {
        $resident = $this->resident();
        $this->get('/dashboard')->assertViewHas('residents', ['total' => 1, 'active' => 1, 'inactive' => 0]);
        $this->assertTrue(app(ResidentDeactivationService::class)->deactivateResident($resident->id)->success);
        $this->get('/dashboard')->assertViewHas('residents', ['total' => 1, 'active' => 0, 'inactive' => 1]);
    }

    public function test_existing_pending_to_in_progress_transition_is_reflected_after_refresh(): void
    {
        $record = $this->serviceRequest();
        $this->get('/dashboard')->assertViewHas('serviceRequests', [
            'total' => 1, 'pending' => 1, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0, 'open' => 1,
        ]);
        $this->assertTrue(app(ServiceRequestStatusService::class)->manageStatus($record->id, 'In Progress')->success);
        $this->get('/dashboard')->assertViewHas('serviceRequests', [
            'total' => 1, 'pending' => 0, 'in_progress' => 1, 'completed' => 0, 'cancelled' => 0, 'open' => 1,
        ]);
    }

    public static function terminalStatuses(): array
    {
        return [['Completed'], ['Cancelled']];
    }

    #[DataProvider('terminalStatuses')]
    public function test_terminal_transition_decreases_open_and_preserves_total(string $status): void
    {
        $record = $this->serviceRequest();
        $statuses = app(ServiceRequestStatusService::class);
        $this->assertTrue($statuses->manageStatus($record->id, 'In Progress')->success);
        $this->get('/dashboard')->assertViewHas('serviceRequests', fn (array $counts) => $counts['open'] === 1);
        $this->assertTrue($statuses->manageStatus($record->id, $status)->success);
        $this->get('/dashboard')->assertViewHas('serviceRequests', fn (array $counts) => $counts['open'] === 0 && $counts['total'] === 1 && $counts['in_progress'] === 0 && $counts[strtolower($status)] === 1
        );
    }

    public function test_recent_requests_returns_at_most_five_and_displays_required_fields_and_t13_links(): void
    {
        for ($i = 1; $i <= 7; $i++) {
            $this->serviceRequest(['service_type' => 'Request '.$i]);
        }
        $recent = $this->summary()['recentServiceRequests'];
        $this->assertCount(5, $recent);
        $response = $this->get('/dashboard')->assertOk()->assertViewHas('recentServiceRequests', fn ($records) => $records->count() === 5);
        foreach ($recent as $record) {
            foreach (['id', 'resident_id', 'service_type', 'date_requested', 'status'] as $field) {
                $response->assertSee((string) $record->$field);
            }
            $response->assertSee('href="'.route('service-requests.show', $record->id).'"', false);
        }
        $response->assertDontSee('Request 1')->assertDontSee('Request 2');
    }

    public function test_recent_requests_use_date_then_id_descending_order(): void
    {
        $old = $this->serviceRequest(['service_type' => 'Old', 'date_requested' => '2026-01-01']);
        $tieLow = $this->serviceRequest(['service_type' => 'Tie low', 'date_requested' => '2026-10-20']);
        $new = $this->serviceRequest(['service_type' => 'Newest', 'date_requested' => '2026-11-01']);
        $tieHigh = $this->serviceRequest(['service_type' => 'Tie high', 'date_requested' => '2026-10-20']);
        $this->assertSame([$new->id, $tieHigh->id, $tieLow->id, $old->id], $this->summary()['recentServiceRequests']->modelKeys());
        $this->get('/dashboard')->assertOk()->assertSeeInOrder(['Newest', 'Tie high', 'Tie low', 'Old']);
    }

    public function test_terminal_requests_remain_eligible_for_recent_results_and_details(): void
    {
        foreach (['Completed', 'Cancelled'] as $status) {
            $record = $this->serviceRequest(['status' => $status, 'service_type' => 'Recent '.$status]);
            $this->get('/dashboard')->assertOk()->assertSee('Recent '.$status)
                ->assertSee('href="'.route('service-requests.show', $record->id).'"', false);
            $this->get('/service-requests/'.$record->id)->assertOk()->assertSee($status)->assertSee('Terminal state');
        }
        $this->assertCount(2, $this->summary()['recentServiceRequests']);
    }

    public function test_repeated_dashboard_get_does_not_modify_any_persisted_domain_data(): void
    {
        $this->resident('Inactive');
        foreach (['Pending', 'In Progress', 'Completed', 'Cancelled'] as $status) {
            $this->serviceRequest(['status' => $status]);
        }
        $beforeResidents = Resident::orderBy('id')->get()->toArray();
        $beforeRequests = ServiceRequest::orderBy('id')->get()->toArray();
        for ($i = 0; $i < 3; $i++) {
            $this->get('/dashboard')->assertOk();
        }
        $this->assertSame($beforeResidents, Resident::orderBy('id')->get()->toArray());
        $this->assertSame($beforeRequests, ServiceRequest::orderBy('id')->get()->toArray());
    }

    public function test_counts_are_database_aggregates_and_recent_query_is_limited_in_persistence(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->serviceRequest();
        }
        DB::enableQueryLog();
        $this->get('/dashboard')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $domainQueries = array_filter($queries, fn (array $query) => preg_match('/from ["`]?'.'(residents|service_requests)'.'["`]?\b/i', $query['query'])
        );
        $this->assertNotEmpty($domainQueries);
        $aggregates = 0;
        $recentQueries = 0;
        foreach ($domainQueries as $query) {
            if (str_contains(strtolower($query['query']), 'count(')) {
                $aggregates++;
            } else {
                $this->assertStringContainsString('service_requests', $query['query']);
                $this->assertMatchesRegularExpression('/limit\s+5\b/i', $query['query']);
                $recentQueries++;
            }
        }
        $this->assertGreaterThanOrEqual(2, $aggregates);
        $this->assertSame(1, $recentQueries);
    }

    public function test_shared_navigation_reaches_dashboard_and_recent_content_is_escaped(): void
    {
        $this->serviceRequest(['service_type' => '<script>alert(1)</script>']);
        foreach (['/residents', '/service-requests'] as $url) {
            $this->get($url)->assertOk()->assertSee('href="http://localhost/dashboard"', false);
        }
        $this->get('/dashboard')->assertOk()->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('method="POST"', false);
    }
}
