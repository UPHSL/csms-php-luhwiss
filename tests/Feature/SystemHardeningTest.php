<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function residentInput(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Maria', 'last_name' => "O'Neil",
            'address' => 'Barangay Santo Tomas', 'contact_number' => '09171234567',
            'email' => 'maria@example.com',
        ], $overrides);
    }

    private function resident(array $overrides = []): Resident
    {
        return Resident::create($this->residentInput($overrides));
    }

    private function requestInput(Resident $resident, array $overrides = []): array
    {
        return array_merge([
            'resident_id' => $resident->id, 'service_type' => "Resident's Clearance",
            'description' => 'Employment requirements.', 'date_requested' => '2026-11-03',
        ], $overrides);
    }

    private function snapshot(): array
    {
        return [
            Resident::orderBy('id')->get()->toArray(),
            ServiceRequest::orderBy('id')->get()->toArray(),
        ];
    }

    // Category 1: normal invalid browser requests must not expose debug internals.
    public function test_malformed_identifiers_and_wrong_http_methods_have_safe_feedback_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        $resident = $this->resident();
        $record = ServiceRequest::create($this->requestInput($resident));
        $before = $this->snapshot();

        foreach (['/residents/abc/edit', '/service-requests/not-a-number'] as $url) {
            $this->get($url)->assertNotFound()->assertDontSee('Stack trace');
        }
        foreach ([
            '/residents/'.$resident->id.'/deactivate',
            '/service-requests/'.$record->id.'/start',
            '/service-requests/'.$record->id.'/complete',
            '/service-requests/'.$record->id.'/cancel',
        ] as $url) {
            $this->get($url)->assertStatus(405)->assertSee('Method not allowed.')
                ->assertSee('Return to the appropriate form')
                ->assertDontSee('Stack trace')->assertDontSee('vendor/laravel');
        }
        $this->assertSame($before, $this->snapshot());
    }

    // Category 2: malformed multi-field updates cannot partially save or reactivate.
    public function test_rejected_malformed_update_preserves_inactive_resident_and_unrelated_records(): void
    {
        $resident = $this->resident(['status' => 'Inactive']);
        $other = $this->resident(['first_name' => 'Juan']);
        ServiceRequest::create($this->requestInput($other));
        $before = $this->snapshot();

        $response = $this->put('/residents/'.$resident->id, $this->residentInput([
            'first_name' => '<script>partial update</script>', 'contact_number' => ['unexpected'],
            'id' => $other->id, 'status' => 'Active',
        ]))->assertRedirect('/residents/'.$resident->id.'/edit');
        $this->followRedirects($response)->assertOk()->assertSee('Contact number must follow')
            ->assertSee('&lt;script&gt;partial update&lt;/script&gt;', false)
            ->assertDontSee('<script>partial update</script>', false);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame('Inactive', $resident->fresh()->status);
    }

    // Category 3: hostile search text stays data and repeated mixed reads stay safe.
    public function test_quoted_and_hostile_searches_remain_read_only_across_both_modules_and_dashboard(): void
    {
        $resident = $this->resident();
        $record = ServiceRequest::create($this->requestInput($resident));
        $this->resident(['first_name' => 'Unrelated', 'last_name' => 'Person']);
        $before = $this->snapshot();
        $this->get('/residents?'.http_build_query(['q' => "O'Neil"]))->assertOk()->assertSee('Maria')->assertDontSee('Unrelated');
        $this->get('/service-requests?'.http_build_query(['q' => "Resident's"]))->assertOk()->assertSee('Clearance');

        foreach (["' OR 1=1 --", '<script>alert(1)</script>'] as $query) {
            for ($i = 0; $i < 2; $i++) {
                $this->get('/residents?'.http_build_query(['q' => $query]))->assertOk()
                    ->assertSee('No Residents found.')->assertDontSee('<script>alert(1)</script>', false);
                $this->get('/service-requests?'.http_build_query(['q' => $query, 'status' => 'Pending']))->assertOk()
                    ->assertSee('No Service Requests found.')->assertDontSee('<script>alert(1)</script>', false);
                $this->get('/dashboard')->assertOk();
                $this->get('/service-requests/'.$record->id)->assertOk();
            }
        }
        $this->assertSame($before, $this->snapshot());
    }

    // Category 4: deactivation blocks future requests while preserving history.
    public function test_deactivation_blocks_future_submissions_but_preserves_request_history_and_dashboard(): void
    {
        $this->post('/residents', $this->residentInput())->assertRedirect('/residents');
        $resident = Resident::sole();
        $input = $this->requestInput($resident);
        $this->post('/service-requests', $input)->assertRedirect('/service-requests');
        $record = ServiceRequest::sole();
        $requestBefore = $record->fresh()->getAttributes();

        $this->post('/residents/'.$resident->id.'/deactivate')->assertRedirect('/residents');
        $response = $this->post('/service-requests', $input)->assertRedirect('/service-requests/create');
        $this->followRedirects($response)->assertOk()->assertSee('Inactive Residents cannot submit new Service Requests.');

        $this->assertDatabaseCount('service_requests', 1);
        $this->assertSame($requestBefore, $record->fresh()->getAttributes());
        $this->get('/residents?q=Maria')->assertOk()->assertSee('Inactive');
        $this->get('/service-requests/'.$record->id)->assertOk()->assertSee('Pending')->assertSee('Employment requirements.');
        $this->get('/dashboard')->assertOk()
            ->assertViewHas('residents', ['total' => 1, 'active' => 0, 'inactive' => 1])
            ->assertViewHas('serviceRequests', fn (array $counts) => $counts['total'] === 1 && $counts['open'] === 1);
    }

    // Category 5: a newly updated old request cannot displace the fifth recent row.
    public function test_workflow_updates_do_not_promote_old_requests_into_recent_five(): void
    {
        $resident = $this->resident();
        $records = [];
        for ($i = 0; $i < 6; $i++) {
            $records[] = ServiceRequest::create($this->requestInput($resident, ['service_type' => 'Boundary request '.$i]));
        }
        $old = $records[0];
        $expectedIds = array_reverse(array_map(fn ($record) => $record->id, array_slice($records, 1)));
        $this->get('/dashboard')->assertViewHas('recentServiceRequests', fn ($recent) => $recent->modelKeys() === $expectedIds);
        $this->travel(1)->days();
        $this->post('/service-requests/'.$old->id.'/start')->assertRedirect('/service-requests/'.$old->id);
        $this->post('/service-requests/'.$old->id.'/complete')->assertRedirect('/service-requests/'.$old->id);
        $beforeReplay = $this->snapshot();
        $response = $this->post('/service-requests/'.$old->id.'/complete')->assertRedirect('/service-requests/'.$old->id);
        $this->followRedirects($response)->assertOk()->assertSee('This action is not allowed');
        $this->assertSame($beforeReplay, $this->snapshot());
        $this->get('/dashboard')->assertOk()
            ->assertViewHas('recentServiceRequests', fn ($recent) => $recent->modelKeys() === $expectedIds)
            ->assertViewHas('serviceRequests', fn (array $counts) => $counts['total'] === 6 && $counts['open'] === 5 && $counts['completed'] === 1)
            ->assertDontSee('Boundary request 0');
        $this->get('/service-requests?status=Completed')->assertOk()->assertSee('Boundary request 0');
        $this->assertSame('2026-11-03', $old->fresh()->date_requested);
        $this->travelBack();
    }
}
