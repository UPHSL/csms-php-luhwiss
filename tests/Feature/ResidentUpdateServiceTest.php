<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use App\Services\ResidentQueryService;
use App\Services\ResidentUpdateService;
use App\Services\ResidentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResidentUpdateService $service;
    private ResidentRepository $repository;
    private ResidentQueryService $queryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ResidentRepository;
        $this->service = new ResidentUpdateService(
            new ResidentValidator,
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

    private function validUpdateData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Miguel',
            'last_name' => 'Santos',
            'address' => 'Barangay San Jose',
            'contact_number' => '09181234567',
            'email' => 'miguel@example.com',
        ], $overrides);
    }

    public function test_valid_resident_update_succeeds(): void
    {
        $resident = $this->persistResident();

        $result = $this->service->updateResident(
            $resident->id,
            $this->validUpdateData()
        );

        $this->assertTrue($result->success);
        $this->assertFalse($result->notFound);
        $this->assertNotNull($result->resident);
        $this->assertSame([], $result->errors);
    }

    public function test_resident_id_is_preserved_after_update(): void
    {
        $resident = $this->persistResident();
        $originalId = $resident->id;

        $result = $this->service->updateResident(
            $originalId,
            $this->validUpdateData()
        );

        $this->assertTrue($result->success);
        $this->assertSame($originalId, $result->resident->id);
    }

    public function test_permitted_resident_information_is_persisted(): void
    {
        $resident = $this->persistResident();

        $this->service->updateResident(
            $resident->id,
            $this->validUpdateData()
        );

        $stored = $this->repository->findById($resident->id);

        $this->assertSame('Miguel', $stored->first_name);
        $this->assertSame('Santos', $stored->last_name);
        $this->assertSame('Barangay San Jose', $stored->address);
        $this->assertSame('09181234567', $stored->contact_number);
        $this->assertSame('miguel@example.com', $stored->email);
    }

    public function test_resident_status_is_preserved_after_update(): void
    {
        $active = $this->persistResident(['status' => 'Active']);
        $inactive = $this->persistResident([
            'email' => 'inactive@example.com',
            'status' => 'Inactive',
        ]);

        $this->service->updateResident($active->id, $this->validUpdateData());
        $this->service->updateResident($inactive->id, $this->validUpdateData([
            'email' => 'inactive2@example.com',
        ]));

        $this->assertSame('Active', $this->repository->findById($active->id)->status);
        $this->assertSame('Inactive', $this->repository->findById($inactive->id)->status);
    }

    public function test_invalid_update_fails(): void
    {
        $resident = $this->persistResident();

        $result = $this->service->updateResident(
            $resident->id,
            $this->validUpdateData(['first_name' => ''])
        );

        $this->assertFalse($result->success);
        $this->assertFalse($result->notFound);
        $this->assertContains('first_name', $result->errors);
    }

    public function test_invalid_update_does_not_modify_persisted_information(): void
    {
        $resident = $this->persistResident();

        $this->service->updateResident(
            $resident->id,
            $this->validUpdateData(['first_name' => ''])
        );

        $stored = $this->repository->findById($resident->id);

        $this->assertSame('Juan', $stored->first_name);
        $this->assertSame('Dela Cruz', $stored->last_name);
        $this->assertSame('09171234567', $stored->contact_number);
    }

    public function test_updating_nonexistent_resident_is_handled_safely(): void
    {
        $result = $this->service->updateResident(
            999999,
            $this->validUpdateData()
        );

        $this->assertFalse($result->success);
        $this->assertTrue($result->notFound);
        $this->assertNull($result->resident);
        $this->assertSame([], $result->errors);
    }

    public function test_nonexistent_update_does_not_create_a_resident(): void
    {
        $countBefore = Resident::count();

        $this->service->updateResident(
            999999,
            $this->validUpdateData()
        );

        $this->assertSame($countBefore, Resident::count());
    }

    public function test_updated_resident_is_visible_through_t05_querying(): void
    {
        $resident = $this->persistResident([
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
        ]);

        $this->service->updateResident(
            $resident->id,
            $this->validUpdateData([
                'first_name' => 'Miguel',
                'last_name' => 'Santos',
            ])
        );

        $results = $this->queryService->searchResidents('Miguel');

        $this->assertCount(1, $results);
        $this->assertSame($resident->id, $results->first()->id);
    }

    public function test_updated_information_and_contact_number_are_preserved(): void
    {
        $resident = $this->persistResident();

        $result = $this->service->updateResident(
            $resident->id,
            $this->validUpdateData(['contact_number' => '09181234567'])
        );

        $stored = $this->repository->findById($result->resident->id);

        $this->assertSame($resident->id, $stored->id);
        $this->assertSame('Miguel', $stored->first_name);
        $this->assertSame('Santos', $stored->last_name);
        $this->assertSame('Barangay San Jose', $stored->address);
        $this->assertSame('09181234567', $stored->contact_number);
        $this->assertStringStartsWith('0', $stored->contact_number);
        $this->assertSame('miguel@example.com', $stored->email);
        $this->assertSame('Active', $stored->status);
    }
}
