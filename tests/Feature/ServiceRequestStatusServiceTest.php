<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ServiceRequestRepository;
use App\Services\ServiceRequestStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequestStatusService $service;

    private ServiceRequestRepository $repository;

    private Resident $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ServiceRequestRepository;
        $this->service = new ServiceRequestStatusService($this->repository);
        $this->resident = Resident::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contact_number' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ]);
    }

    private function persistServiceRequest(array $overrides = []): ServiceRequest
    {
        return ServiceRequest::create(array_merge([
            'resident_id' => $this->resident->id,
            'service_type' => 'Barangay Clearance',
            'description' => 'Employment requirement',
            'date_requested' => '2026-09-15',
            'status' => 'Pending',
        ], $overrides));
    }

    public function test_pending_can_move_to_in_progress(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'In Progress'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertTrue($result->success);
        $this->assertSame('updated', $result->outcome);
        $this->assertSame('In Progress', $result->serviceRequest->status);
        $this->assertSame('In Progress', $stored->status);
    }

    public function test_pending_can_move_to_cancelled(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Cancelled'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertTrue($result->success);
        $this->assertSame('updated', $result->outcome);
        $this->assertSame('Cancelled', $stored->status);
    }

    public function test_in_progress_can_move_to_completed(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $this->service->manageStatus($serviceRequest->id, 'In Progress');
        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Completed'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertTrue($result->success);
        $this->assertSame('updated', $result->outcome);
        $this->assertSame('Completed', $stored->status);
    }

    public function test_in_progress_can_move_to_cancelled(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $this->service->manageStatus($serviceRequest->id, 'In Progress');
        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Cancelled'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertTrue($result->success);
        $this->assertSame('updated', $result->outcome);
        $this->assertSame('Cancelled', $stored->status);
    }

    public function test_pending_cannot_move_directly_to_completed(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Completed'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->outcome);
        $this->assertNull($result->serviceRequest);
        $this->assertSame('Pending', $stored->status);
    }

    public function test_in_progress_cannot_return_to_pending(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $this->service->manageStatus($serviceRequest->id, 'In Progress');
        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Pending'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->outcome);
        $this->assertSame('In Progress', $stored->status);
    }

    public function test_completed_is_terminal(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $this->service->manageStatus($serviceRequest->id, 'In Progress');
        $this->service->manageStatus($serviceRequest->id, 'Completed');
        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Cancelled'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->outcome);
        $this->assertSame('Completed', $stored->status);
    }

    public function test_cancelled_is_terminal(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $this->service->manageStatus($serviceRequest->id, 'Cancelled');
        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'In Progress'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->outcome);
        $this->assertSame('Cancelled', $stored->status);
    }

    public function test_unsupported_status_is_rejected(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Approved'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertFalse($result->success);
        $this->assertSame('unsupported_status', $result->outcome);
        $this->assertNull($result->serviceRequest);
        $this->assertSame('Pending', $stored->status);
    }

    public function test_nonexistent_service_request_is_handled_safely(): void
    {
        $existing = $this->persistServiceRequest();
        $countBefore = ServiceRequest::count();

        $result = $this->service->manageStatus(999999, 'In Progress');

        $this->assertFalse($result->success);
        $this->assertSame('not_found', $result->outcome);
        $this->assertNull($result->serviceRequest);
        $this->assertSame($countBefore, ServiceRequest::count());
        $this->assertNotNull($this->repository->findById($existing->id));
    }

    public function test_successful_transition_preserves_service_request_information(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'In Progress'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertSame($serviceRequest->id, $result->serviceRequest->id);
        $this->assertSame($serviceRequest->id, $stored->id);
        $this->assertSame($this->resident->id, $stored->resident_id);
        $this->assertSame('Barangay Clearance', $stored->service_type);
        $this->assertSame('Employment requirement', $stored->description);
        $this->assertSame('2026-09-15', $stored->date_requested);
        $this->assertSame('In Progress', $stored->status);
    }

    public function test_invalid_transition_does_not_modify_persistence(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $this->service->manageStatus($serviceRequest->id, 'Completed');

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertSame($serviceRequest->id, $stored->id);
        $this->assertSame($this->resident->id, $stored->resident_id);
        $this->assertSame('Barangay Clearance', $stored->service_type);
        $this->assertSame('Employment requirement', $stored->description);
        $this->assertSame('2026-09-15', $stored->date_requested);
        $this->assertSame('Pending', $stored->status);
    }

    public function test_same_status_request_is_rejected(): void
    {
        $serviceRequest = $this->persistServiceRequest();

        $result = $this->service->manageStatus(
            $serviceRequest->id,
            'Pending'
        );

        $stored = $this->repository->findById($serviceRequest->id);

        $this->assertFalse($result->success);
        $this->assertSame('invalid_transition', $result->outcome);
        $this->assertSame('Pending', $stored->status);
    }

    public function test_student_designed_transition_only_updates_target_request(): void
    {
        $target = $this->persistServiceRequest([
            'description' => 'Target request',
        ]);
        $other = $this->persistServiceRequest([
            'service_type' => 'Certificate of Indigency',
            'description' => 'Other request',
        ]);

        $this->service->manageStatus($target->id, 'In Progress');

        $storedTarget = $this->repository->findById($target->id);
        $storedOther = $this->repository->findById($other->id);

        $this->assertSame('In Progress', $storedTarget->status);
        $this->assertSame('Target request', $storedTarget->description);
        $this->assertSame('Pending', $storedOther->status);
        $this->assertSame(
            'Certificate of Indigency',
            $storedOther->service_type
        );
        $this->assertSame('Other request', $storedOther->description);
    }
}
