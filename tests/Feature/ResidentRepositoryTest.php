<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ResidentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ResidentRepository();
    }

    private function makeValidResident(array $overrides = []): Resident
    {
        return new Resident(array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'address' => 'Barangay Santo Tomas',
            'contact_number' => '09171234567',
            'email' => 'juan@example.com',
            'status' => 'Active',
        ], $overrides));
    }

    public function test_resident_can_be_persisted(): void
    {
        $resident = $this->makeValidResident();

        $saved = $this->repository->save($resident);

        $this->assertNotNull($saved);
    }

    public function test_persisted_resident_receives_an_identifier(): void
    {
        $resident = $this->makeValidResident();

        $saved = $this->repository->save($resident);

        $this->assertNotNull($saved->id);
        $this->assertIsInt($saved->id);
    }

    public function test_resident_can_be_retrieved_by_identifier(): void
    {
        $resident = $this->makeValidResident();
        $saved = $this->repository->save($resident);

        $found = $this->repository->findById($saved->id);

        $this->assertNotNull($found);
        $this->assertSame($saved->id, $found->id);
    }

    public function test_resident_information_is_preserved_after_persistence(): void
    {
        $resident = $this->makeValidResident();
        $saved = $this->repository->save($resident);

        $found = $this->repository->findById($saved->id);

        $this->assertSame('Juan', $found->first_name);
        $this->assertSame('Dela Cruz', $found->last_name);
        $this->assertSame('Barangay Santo Tomas', $found->address);
        $this->assertSame('09171234567', $found->contact_number);
        $this->assertSame('juan@example.com', $found->email);
        $this->assertSame('Active', $found->status);
    }

    public function test_active_status_is_preserved_after_persistence(): void
    {
        $resident = $this->makeValidResident(['status' => 'Active']);
        $saved = $this->repository->save($resident);

        $found = $this->repository->findById($saved->id);

        $this->assertSame('Active', $found->status);
    }

    public function test_missing_resident_returns_null(): void
    {
        $found = $this->repository->findById(999999);

        $this->assertNull($found);
    }

    public function test_persisted_resident_is_available_from_another_repository_instance(): void
    {
        $resident = $this->makeValidResident();
        $saved = $this->repository->save($resident);

        $anotherRepository = new ResidentRepository();
        $found = $anotherRepository->findById($saved->id);

        $this->assertNotNull($found);
        $this->assertSame($saved->id, $found->id);
    }

    public function test_contact_number_leading_zero_is_preserved_after_persistence(): void
    {
        $resident = $this->makeValidResident(['contact_number' => '09171234567']);
        $saved = $this->repository->save($resident);

        $found = $this->repository->findById($saved->id);

        $this->assertSame('09171234567', $found->contact_number);
        $this->assertStringStartsWith('0', $found->contact_number);
    }
}
