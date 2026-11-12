<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;
use App\Services\ServiceRequestSubmissionService;
use App\Services\ServiceRequestValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequestSubmissionService $service;
    private ResidentRepository $residentRepository;
    private ServiceRequestRepository $serviceRequestRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->residentRepository = new ResidentRepository;
        $this->serviceRequestRepository = new ServiceRequestRepository;
        $this->service = new ServiceRequestSubmissionService(
            new ServiceRequestValidator,
            $this->residentRepository,
            $this->serviceRequestRepository
        );
    }

    private function persistResident(array $overrides = []): Resident
    {
        return Resident::create(array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contact_number' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ], $overrides));
    }

    private function makeValidServiceRequest(
        int $residentId,
        array $overrides = []
    ): ServiceRequest {
        $idWasProvided = array_key_exists('id', $overrides);
        $id = $overrides['id'] ?? null;
        unset($overrides['id']);

        $serviceRequest = new ServiceRequest(array_merge([
            'resident_id' => $residentId,
            'service_type' => 'Barangay Clearance',
            'description' => 'Request for employment requirement',
            'date_requested' => '2026-10-01',
        ], $overrides));

        if ($idWasProvided) {
            $serviceRequest->id = $id;
        }

        return $serviceRequest;
    }

    public function test_active_resident_can_submit_a_valid_service_request(): void
    {
        $resident = $this->persistResident();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest($resident->id)
        );

        $this->assertTrue($result->success);
        $this->assertSame('submitted', $result->outcome);
        $this->assertSame([], $result->errors);
        $this->assertNotNull($result->serviceRequest);
        $this->assertNotNull($result->serviceRequest->id);
        $this->assertIsInt($result->serviceRequest->id);
        $this->assertSame(1, ServiceRequest::count());
    }

    public function test_submitted_service_request_is_retrievable_with_preserved_information(): void
    {
        $resident = $this->persistResident();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest($resident->id)
        );

        $stored = $this->serviceRequestRepository->findById(
            $result->serviceRequest->id
        );

        $this->assertNotNull($stored);
        $this->assertSame($resident->id, $stored->resident_id);
        $this->assertSame('Barangay Clearance', $stored->service_type);
        $this->assertSame(
            'Request for employment requirement',
            $stored->description
        );
        $this->assertSame('2026-10-01', $stored->date_requested);
        $this->assertSame('Pending', $stored->status);
    }

    public function test_submitted_service_request_preserves_default_pending_status(): void
    {
        $resident = $this->persistResident();
        $serviceRequest = $this->makeValidServiceRequest($resident->id);

        $this->assertSame('Pending', $serviceRequest->status);

        $result = $this->service->submitServiceRequest($serviceRequest);

        $stored = $this->serviceRequestRepository->findById(
            $result->serviceRequest->id
        );

        $this->assertSame('Pending', $result->serviceRequest->status);
        $this->assertSame('Pending', $stored->status);
    }

    public function test_blank_service_type_fails_before_persistence(): void
    {
        $resident = $this->persistResident();
        $countBefore = ServiceRequest::count();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest(
                $resident->id,
                ['service_type' => '   ']
            )
        );

        $this->assertFalse($result->success);
        $this->assertSame('validation_failed', $result->outcome);
        $this->assertNull($result->serviceRequest);
        $this->assertContains('service_type', $result->errors);
        $this->assertSame($countBefore, ServiceRequest::count());
    }

    public function test_blank_description_fails_before_persistence(): void
    {
        $resident = $this->persistResident();
        $countBefore = ServiceRequest::count();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest(
                $resident->id,
                ['description' => '']
            )
        );

        $this->assertFalse($result->success);
        $this->assertSame('validation_failed', $result->outcome);
        $this->assertNull($result->serviceRequest);
        $this->assertContains('description', $result->errors);
        $this->assertSame($countBefore, ServiceRequest::count());
    }

    public function test_invalid_or_absent_date_fails_before_persistence(): void
    {
        $resident = $this->persistResident();

        foreach ([null, '2026-02-30'] as $dateRequested) {
            $countBefore = ServiceRequest::count();
            $result = $this->service->submitServiceRequest(
                $this->makeValidServiceRequest(
                    $resident->id,
                    ['date_requested' => $dateRequested]
                )
            );

            $this->assertFalse($result->success);
            $this->assertSame('validation_failed', $result->outcome);
            $this->assertNull($result->serviceRequest);
            $this->assertContains('date_requested', $result->errors);
            $this->assertSame($countBefore, ServiceRequest::count());
        }
    }

    public function test_assigned_id_fails_before_persistence(): void
    {
        $resident = $this->persistResident();
        $countBefore = ServiceRequest::count();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest($resident->id, ['id' => 17])
        );

        $this->assertFalse($result->success);
        $this->assertSame('validation_failed', $result->outcome);
        $this->assertContains('id', $result->errors);
        $this->assertSame($countBefore, ServiceRequest::count());
    }

    public function test_non_pending_status_fails_before_persistence(): void
    {
        $resident = $this->persistResident();
        $countBefore = ServiceRequest::count();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest(
                $resident->id,
                ['status' => 'Completed']
            )
        );

        $this->assertFalse($result->success);
        $this->assertSame('validation_failed', $result->outcome);
        $this->assertContains('status', $result->errors);
        $this->assertSame($countBefore, ServiceRequest::count());
    }

    public function test_nonexistent_resident_prevents_submission(): void
    {
        $countBefore = ServiceRequest::count();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest(999999)
        );

        $this->assertFalse($result->success);
        $this->assertSame('resident_not_found', $result->outcome);
        $this->assertNull($result->serviceRequest);
        $this->assertSame([], $result->errors);
        $this->assertSame($countBefore, ServiceRequest::count());
    }

    public function test_inactive_resident_cannot_submit_a_service_request(): void
    {
        $resident = $this->persistResident(['status' => 'Inactive']);
        $countBefore = ServiceRequest::count();

        $result = $this->service->submitServiceRequest(
            $this->makeValidServiceRequest($resident->id)
        );

        $this->assertFalse($result->success);
        $this->assertSame('resident_inactive', $result->outcome);
        $this->assertNull($result->serviceRequest);
        $this->assertSame([], $result->errors);
        $this->assertSame($countBefore, ServiceRequest::count());
    }

    public function test_successful_submission_does_not_modify_the_active_resident(): void
    {
        $resident = $this->persistResident();

        $this->service->submitServiceRequest(
            $this->makeValidServiceRequest($resident->id)
        );

        $stored = $this->residentRepository->findById($resident->id);

        $this->assertSame($resident->id, $stored->id);
        $this->assertSame('Juan', $stored->first_name);
        $this->assertSame('Dela Cruz', $stored->last_name);
        $this->assertSame('Barangay Santo Tomas', $stored->address);
        $this->assertSame('09171234567', $stored->contact_number);
        $this->assertSame('juan@example.com', $stored->email);
        $this->assertSame('Active', $stored->status);
    }

    public function test_rejected_submission_does_not_modify_the_inactive_resident(): void
    {
        $resident = $this->persistResident(['status' => 'Inactive']);

        $this->service->submitServiceRequest(
            $this->makeValidServiceRequest($resident->id)
        );

        $stored = $this->residentRepository->findById($resident->id);

        $this->assertSame($resident->id, $stored->id);
        $this->assertSame('Juan', $stored->first_name);
        $this->assertSame('Dela Cruz', $stored->last_name);
        $this->assertSame('Barangay Santo Tomas', $stored->address);
        $this->assertSame('09171234567', $stored->contact_number);
        $this->assertSame('juan@example.com', $stored->email);
        $this->assertSame('Inactive', $stored->status);
    }
}
