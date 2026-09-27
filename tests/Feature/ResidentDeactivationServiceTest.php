<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use App\Services\ResidentDeactivationService;
use App\Services\ResidentQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentDeactivationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResidentDeactivationService $service;
    private ResidentRepository $repository;
    private ResidentQueryService $queryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ResidentRepository;
        $this->service = new ResidentDeactivationService(
            $this->repository
        );
        $this->queryService = new ResidentQueryService(
            $this->repository
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

    public function test_active_resident_can_be_deactivated(): void
    {
        $resident = $this->persistResident();

        $result = $this->service->deactivateResident($resident->id);

        $this->assertTrue($result->success);
        $this->assertFalse($result->alreadyInactive);
        $this->assertFalse($result->notFound);
        $this->assertNotNull($result->resident);
    }

    public function test_resident_status_becomes_inactive_in_persistence(): void
    {
        $resident = $this->persistResident();

        $this->service->deactivateResident($resident->id);

        $stored = $this->repository->findById($resident->id);

        $this->assertSame('Inactive', $stored->status);
    }

    public function test_resident_id_is_preserved_after_deactivation(): void
    {
        $resident = $this->persistResident();
        $originalId = $resident->id;

        $result = $this->service->deactivateResident($originalId);

        $this->assertSame($originalId, $result->resident->id);
    }

    public function test_resident_information_is_preserved_after_deactivation(): void
    {
        $resident = $this->persistResident();

        $this->service->deactivateResident($resident->id);

        $stored = $this->repository->findById($resident->id);

        $this->assertSame('Juan', $stored->first_name);
        $this->assertSame('Dela Cruz', $stored->last_name);
        $this->assertSame('Barangay Santo Tomas', $stored->address);
        $this->assertSame('09171234567', $stored->contact_number);
        $this->assertStringStartsWith('0', $stored->contact_number);
        $this->assertSame('juan@example.com', $stored->email);
    }

    public function test_deactivated_resident_remains_persisted_and_retrievable(): void
    {
        $resident = $this->persistResident();

        $this->service->deactivateResident($resident->id);

        $stored = $this->repository->findById($resident->id);

        $this->assertNotNull($stored);
        $this->assertSame($resident->id, $stored->id);
        $this->assertSame('Inactive', $stored->status);
    }

    public function test_deactivated_resident_remains_available_through_t05(): void
    {
        $resident = $this->persistResident(['first_name' => 'Maria', 'last_name' => 'Santos']);

        $this->service->deactivateResident($resident->id);

        $searchResults = $this->queryService->searchResidents('Maria');
        $listResults = $this->queryService->listResidents();

        $this->assertTrue($searchResults->contains('id', $resident->id));
        $this->assertSame('Inactive', $searchResults->firstWhere('id', $resident->id)->status);
        $this->assertTrue($listResults->contains('id', $resident->id));
    }

    public function test_already_inactive_resident_is_handled_safely(): void
    {
        $resident = $this->persistResident(['status' => 'Inactive']);
        $countBefore = Resident::count();

        $result = $this->service->deactivateResident($resident->id);

        $this->assertTrue($result->success);
        $this->assertTrue($result->alreadyInactive);
        $this->assertFalse($result->notFound);
        $this->assertSame($resident->id, $result->resident->id);
        $this->assertSame('Inactive', $result->resident->status);
        $this->assertSame($countBefore, Resident::count());
    }

    public function test_nonexistent_resident_is_handled_safely(): void
    {
        $result = $this->service->deactivateResident(999999);

        $this->assertFalse($result->success);
        $this->assertTrue($result->notFound);
        $this->assertFalse($result->alreadyInactive);
        $this->assertNull($result->resident);
    }

    public function test_nonexistent_deactivation_does_not_create_or_delete_records(): void
    {
        $resident = $this->persistResident();
        $countBefore = Resident::count();

        $this->service->deactivateResident(999999);

        $this->assertSame($countBefore, Resident::count());
        $this->assertNotNull($this->repository->findById($resident->id));
    }

    public function test_deactivating_one_resident_does_not_affect_another(): void
    {
        $target = $this->persistResident(['email' => 'target@example.com']);
        $other = $this->persistResident(['email' => 'other@example.com']);

        $this->service->deactivateResident($target->id);

        $storedOther = $this->repository->findById($other->id);

        $this->assertSame('Active', $storedOther->status);
        $this->assertSame('Juan', $storedOther->first_name);
        $this->assertSame('Dela Cruz', $storedOther->last_name);
        $this->assertSame('09171234567', $storedOther->contact_number);
    }
}
