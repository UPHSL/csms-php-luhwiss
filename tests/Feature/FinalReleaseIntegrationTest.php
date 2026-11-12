<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalReleaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_update_preserves_request_ownership_through_mixed_workflows_and_dashboard_queries(): void
    {
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('service_requests', 0);
        $input = [
            'first_name' => 'Elena', 'last_name' => 'Rivera',
            'address' => 'Barangay San Antonio', 'contact_number' => '09171234567',
            'email' => 'elena@example.com',
        ];
        $this->post('/residents', $input)->assertRedirect('/residents');
        $resident = Resident::sole();
        $this->post('/residents', array_replace($input, [
            'first_name' => 'Tomas', 'last_name' => 'Reyes', 'email' => 'tomas@example.com',
        ]))->assertRedirect('/residents');
        $other = Resident::where('id', '!=', $resident->id)->sole();
        $otherBefore = $other->getAttributes();

        $requestInput = [
            'resident_id' => $resident->id, 'service_type' => 'Barangay Clearance',
            'description' => 'Employment verification', 'date_requested' => '2026-11-13',
        ];
        $this->post('/service-requests', $requestInput)->assertRedirect('/service-requests');
        $completed = ServiceRequest::sole();
        $this->post('/service-requests', array_replace($requestInput, [
            'description' => 'Duplicate employment application',
        ]))->assertRedirect('/service-requests');
        $cancelled = ServiceRequest::where('id', '!=', $completed->id)->sole();
        $this->post('/service-requests', array_replace($requestInput, [
            'resident_id' => $other->id, 'service_type' => 'Residency Certificate',
            'description' => 'School enrollment',
        ]))->assertRedirect('/service-requests');
        $untouched = ServiceRequest::where('resident_id', $other->id)->sole();
        $untouchedBefore = $untouched->getAttributes();

        $this->post('/service-requests/'.$completed->id.'/start')->assertRedirect('/service-requests/'.$completed->id);
        $this->assertSame('In Progress', $completed->fresh()->status);
        $requestsBeforeUpdate = ServiceRequest::orderBy('id')->get()->toArray();
        $updatedInput = array_replace($input, ['last_name' => 'Santos', 'address' => 'Barangay Poblacion']);
        $this->put('/residents/'.$resident->id, $updatedInput)->assertRedirect('/residents');
        $this->assertDatabaseHas('residents', $updatedInput + ['id' => $resident->id, 'status' => 'Active']);
        $this->assertSame($requestsBeforeUpdate, ServiceRequest::orderBy('id')->get()->toArray());

        $this->post('/service-requests/'.$completed->id.'/complete')->assertRedirect('/service-requests/'.$completed->id);
        $this->post('/service-requests/'.$cancelled->id.'/cancel')->assertRedirect('/service-requests/'.$cancelled->id);
        $this->assertDatabaseHas('service_requests', $requestInput + ['id' => $completed->id, 'status' => 'Completed']);
        $this->assertDatabaseHas('service_requests', [
            'id' => $cancelled->id, 'resident_id' => $resident->id, 'status' => 'Cancelled',
        ]);

        $beforeRejectedAction = ServiceRequest::orderBy('id')->get()->toArray();
        $response = $this->post('/service-requests/'.$completed->id.'/cancel')
            ->assertRedirect('/service-requests/'.$completed->id);
        $this->followRedirects($response)->assertOk()->assertSee('This action is not allowed');
        $this->assertSame($beforeRejectedAction, ServiceRequest::orderBy('id')->get()->toArray());

        $residentsBeforeReads = Resident::orderBy('id')->get()->toArray();
        $this->get('/residents?q=Santos')->assertOk()->assertSee('Elena')->assertDontSee('Tomas');
        $this->get('/residents?q=Rivera')->assertOk()->assertSee('No Residents found.');
        $this->get('/service-requests?q=employment&status=Completed')->assertOk()
            ->assertViewHas('serviceRequests', fn ($records) => $records->modelKeys() === [$completed->id]);
        $this->get('/service-requests/'.$completed->id)->assertOk()
            ->assertViewHas('serviceRequest', fn ($record) => $record->resident_id === $resident->id && $record->status === 'Completed');
        $this->get('/dashboard')->assertOk()
            ->assertViewHas('residents', ['total' => 2, 'active' => 2, 'inactive' => 0])
            ->assertViewHas('serviceRequests', [
                'total' => 3, 'pending' => 1, 'in_progress' => 0,
                'completed' => 1, 'cancelled' => 1, 'open' => 1,
            ])
            ->assertViewHas('recentServiceRequests', fn ($records) => $records->modelKeys() === [$untouched->id, $cancelled->id, $completed->id]);

        $this->assertDatabaseCount('residents', 2);
        $this->assertDatabaseCount('service_requests', 3);
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSame($untouchedBefore, $untouched->fresh()->getAttributes());
        $this->assertSame($residentsBeforeReads, Resident::orderBy('id')->get()->toArray());
        $this->assertSame($beforeRejectedAction, ServiceRequest::orderBy('id')->get()->toArray());
    }
}
