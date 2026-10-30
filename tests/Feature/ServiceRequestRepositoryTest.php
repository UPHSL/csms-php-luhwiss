<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Repositories\ServiceRequestRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceRequestRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ServiceRequestRepository $repository;
    private Resident $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ServiceRequestRepository;
        $this->resident = Resident::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contact_number' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ]);
    }

    private function makeValidServiceRequest(): ServiceRequest
    {
        return new ServiceRequest([
            'resident_id' => $this->resident->id,
            'service_type' => 'Barangay Clearance',
            'description' => 'Request for employment requirement',
            'date_requested' => '2026-10-01',
        ]);
    }

    public function test_service_request_can_be_persisted_and_receives_an_identifier(): void
    {
        $saved = $this->repository->save(
            $this->makeValidServiceRequest()
        );

        $this->assertNotNull($saved->id);
        $this->assertIsInt($saved->id);
        $this->assertSame(1, ServiceRequest::count());
    }

    public function test_service_request_can_be_retrieved_by_identifier(): void
    {
        $saved = $this->repository->save(
            $this->makeValidServiceRequest()
        );

        $found = $this->repository->findById($saved->id);

        $this->assertNotNull($found);
        $this->assertSame($saved->id, $found->id);
    }

    public function test_missing_service_request_returns_null(): void
    {
        $this->assertNull(
            $this->repository->findById(999999)
        );
    }

    public function test_service_request_information_is_preserved_after_persistence(): void
    {
        $saved = $this->repository->save(
            $this->makeValidServiceRequest()
        );

        $found = $this->repository->findById($saved->id);

        $this->assertNotNull($found);
        $this->assertSame($this->resident->id, $found->resident_id);
        $this->assertSame('Barangay Clearance', $found->service_type);
        $this->assertSame(
            'Request for employment requirement',
            $found->description
        );
        $this->assertSame('2026-10-01', $found->date_requested);
        $this->assertSame('Pending', $found->status);
    }

    public function test_service_request_persists_across_repository_instances(): void
    {
        $saved = $this->repository->save(
            $this->makeValidServiceRequest()
        );

        $anotherRepository = new ServiceRequestRepository;
        $found = $anotherRepository->findById($saved->id);

        $this->assertNotNull($found);
        $this->assertSame($saved->id, $found->id);
    }

    public function test_service_request_schema_contains_only_the_required_domain_fields(): void
    {
        $this->assertTrue(Schema::hasColumns('service_requests', [
            'id',
            'resident_id',
            'service_type',
            'description',
            'date_requested',
            'status',
        ]));

        foreach ([
            'resident_first_name',
            'resident_last_name',
            'resident_address',
            'resident_contact_number',
            'resident_email',
        ] as $duplicatedResidentColumn) {
            $this->assertFalse(
                Schema::hasColumn(
                    'service_requests',
                    $duplicatedResidentColumn
                )
            );
        }
    }
}
