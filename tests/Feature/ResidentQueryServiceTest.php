<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use App\Services\ResidentQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResidentQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ResidentQueryService(
            new ResidentRepository
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

    public function test_lists_all_persisted_residents(): void
    {
        $first = $this->persistResident();
        $second = $this->persistResident([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana@example.com',
        ]);

        $results = $this->service->listResidents();

        $this->assertCount(2, $results);
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $results->pluck('id')->all()
        );
    }

    public function test_empty_listing_returns_an_empty_collection(): void
    {
        $results = $this->service->listResidents();

        $this->assertCount(0, $results);
        $this->assertTrue($results->isEmpty());
    }

    public function test_listing_uses_last_name_first_name_and_id_order(): void
    {
        $this->persistResident(['first_name' => 'Ana', 'last_name' => 'Santos']);
        $pedro = $this->persistResident(['first_name' => 'Pedro', 'last_name' => 'Cruz']);
        $this->persistResident(['first_name' => 'Maria', 'last_name' => 'Andres']);
        $juanFirst = $this->persistResident(['first_name' => 'Juan', 'last_name' => 'Cruz']);
        $juanSecond = $this->persistResident(['first_name' => 'juan', 'last_name' => 'cruz']);

        $results = $this->service->listResidents();

        $this->assertSame(
            ['Maria', 'Juan', 'juan', 'Pedro', 'Ana'],
            $results->pluck('first_name')->all()
        );
        $this->assertSame(
            [$juanFirst->id, $juanSecond->id],
            $results->whereIn('id', [$juanFirst->id, $juanSecond->id])
                ->pluck('id')
                ->values()
                ->all()
        );
        $this->assertNotNull($pedro->id);
    }

    public function test_partial_first_name_search_is_case_insensitive(): void
    {
        $resident = $this->persistResident(['first_name' => 'Juan']);
        $this->persistResident(['first_name' => 'Maria', 'last_name' => 'Santos']);

        $results = $this->service->searchResidents('jUa');

        $this->assertSame([$resident->id], $results->pluck('id')->all());
    }

    public function test_partial_last_name_search_is_case_insensitive(): void
    {
        $resident = $this->persistResident(['last_name' => 'Dela Cruz']);

        $results = $this->service->searchResidents('cRuZ');

        $this->assertSame([$resident->id], $results->pluck('id')->all());
    }

    public function test_blank_search_returns_the_normal_listing(): void
    {
        $this->persistResident(['first_name' => 'Ana', 'last_name' => 'Santos']);
        $this->persistResident(['first_name' => 'Juan', 'last_name' => 'Cruz']);

        $this->assertSame(
            $this->service->listResidents()->pluck('id')->all(),
            $this->service->searchResidents('   ')->pluck('id')->all()
        );
    }

    public function test_search_ignores_leading_and_trailing_spaces(): void
    {
        $resident = $this->persistResident(['first_name' => 'Juan']);

        $results = $this->service->searchResidents('  Juan  ');

        $this->assertSame([$resident->id], $results->pluck('id')->all());
    }

    public function test_search_with_no_match_returns_an_empty_collection(): void
    {
        $this->persistResident();

        $results = $this->service->searchResidents('ZzzUnknownResident');

        $this->assertTrue($results->isEmpty());
    }

    public function test_search_result_preserves_all_resident_information(): void
    {
        $resident = $this->persistResident([
            'first_name' => 'Maria',
            'last_name' => 'Andres',
            'address' => 'Barangay San Jose',
            'contact_number' => '09170000001',
            'email' => 'maria@example.com',
            'status' => 'Inactive',
        ]);

        $result = $this->service->searchResidents('ari')->sole();

        $this->assertSame($resident->id, $result->id);
        $this->assertSame('Maria', $result->first_name);
        $this->assertSame('Andres', $result->last_name);
        $this->assertSame('Barangay San Jose', $result->address);
        $this->assertSame('09170000001', $result->contact_number);
        $this->assertStringStartsWith('0', $result->contact_number);
        $this->assertSame('maria@example.com', $result->email);
        $this->assertSame('Inactive', $result->status);
    }

    public function test_listing_includes_active_and_inactive_residents(): void
    {
        $this->persistResident(['first_name' => 'Active', 'status' => 'Active']);
        $this->persistResident(['first_name' => 'Inactive', 'status' => 'Inactive']);

        $results = $this->service->listResidents();

        $this->assertEqualsCanonicalizing(
            ['Active', 'Inactive'],
            $results->pluck('status')->all()
        );
    }

    public function test_resident_matching_both_names_appears_only_once(): void
    {
        $resident = $this->persistResident([
            'first_name' => 'Ana',
            'last_name' => 'Anasco',
        ]);

        $results = $this->service->searchResidents('ana');

        $this->assertCount(1, $results);
        $this->assertSame([$resident->id], $results->pluck('id')->all());
    }
}
