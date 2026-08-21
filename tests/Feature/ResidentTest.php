<?php

namespace Tests\Feature;

use App\Models\Resident;
use Tests\TestCase;

class ResidentTest extends TestCase
{
    public function test_resident_can_be_created_with_valid_information(): void
    {
        $resident = new Resident(
            id: 1,
            firstName: 'Juan',
            lastName: 'Dela Cruz',
            address: '123 Main St',
            contactNumber: '09171234567',
            email: 'juan@example.com',
            status: Resident::STATUS_ACTIVE,
        );

        $this->assertInstanceOf(Resident::class, $resident);
    }

    public function test_resident_information_can_be_accessed_correctly(): void
    {
        $resident = new Resident(
            id: 1,
            firstName: 'Juan',
            lastName: 'Dela Cruz',
            address: '123 Main St',
            contactNumber: '09171234567',
            email: 'juan@example.com',
            status: Resident::STATUS_ACTIVE,
        );

        $this->assertSame(1, $resident->id);
        $this->assertSame('Juan', $resident->firstName);
        $this->assertSame('Dela Cruz', $resident->lastName);
        $this->assertSame('123 Main St', $resident->address);
        $this->assertSame('09171234567', $resident->contactNumber);
        $this->assertSame('juan@example.com', $resident->email);
        $this->assertSame(Resident::STATUS_ACTIVE, $resident->status);
    }

    public function test_resident_status_can_represent_active(): void
    {
        $resident = new Resident(
            id: 1,
            firstName: 'Juan',
            lastName: 'Dela Cruz',
            address: '123 Main St',
            contactNumber: '09171234567',
            email: 'juan@example.com',
            status: Resident::STATUS_ACTIVE,
        );

        $this->assertSame('Active', $resident->status);
    }
}
