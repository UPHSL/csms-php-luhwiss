<?php

namespace Tests\Unit;

use App\Models\ServiceRequest;
use PHPUnit\Framework\TestCase;

class ServiceRequestTest extends TestCase
{
    public function test_service_request_can_be_created(): void
    {
        $serviceRequest = new ServiceRequest([
            'resident_id' => 25,
            'service_type' => 'Barangay Clearance',
            'description' => 'Request for employment requirement',
            'date_requested' => '2026-10-01',
        ]);

        $this->assertInstanceOf(ServiceRequest::class, $serviceRequest);
    }

    public function test_service_request_information_is_accessible(): void
    {
        $serviceRequest = new ServiceRequest([
            'resident_id' => 25,
            'service_type' => 'Barangay Clearance',
            'description' => 'Request for employment requirement',
            'date_requested' => '2026-10-01',
        ]);

        $this->assertSame(25, $serviceRequest->resident_id);
        $this->assertSame('Barangay Clearance', $serviceRequest->service_type);
        $this->assertSame('Request for employment requirement', $serviceRequest->description);
        $this->assertSame('2026-10-01', $serviceRequest->date_requested);
    }

    public function test_resident_id_is_preserved(): void
    {
        $serviceRequest = new ServiceRequest([
            'resident_id' => 25,
            'service_type' => 'Certificate Request',
            'description' => 'Requesting a residency certificate',
            'date_requested' => '2026-10-02',
        ]);

        $this->assertSame(25, $serviceRequest->resident_id);
    }

    public function test_new_service_request_has_an_unassigned_id(): void
    {
        $serviceRequest = new ServiceRequest([
            'resident_id' => 25,
            'service_type' => 'Community Assistance',
            'description' => 'Requesting community assistance',
            'date_requested' => '2026-10-03',
        ]);

        $this->assertNull($serviceRequest->id);
    }

    public function test_new_service_request_defaults_to_pending(): void
    {
        $serviceRequest = new ServiceRequest([
            'resident_id' => 25,
            'service_type' => 'Permit Request',
            'description' => 'Requesting a community permit',
            'date_requested' => '2026-10-04',
        ]);

        $this->assertSame('Pending', $serviceRequest->status);
    }

    public function test_service_request_information_is_independent_between_objects(): void
    {
        $firstRequest = new ServiceRequest([
            'resident_id' => 25,
            'service_type' => 'Barangay Clearance',
            'description' => 'Employment requirement',
            'date_requested' => '2026-10-05',
        ]);
        $secondRequest = new ServiceRequest([
            'resident_id' => 42,
            'service_type' => 'Community Assistance',
            'description' => 'Medical assistance request',
            'date_requested' => '2026-10-06',
        ]);

        $this->assertSame(25, $firstRequest->resident_id);
        $this->assertSame('Barangay Clearance', $firstRequest->service_type);
        $this->assertSame('Employment requirement', $firstRequest->description);
        $this->assertSame('2026-10-05', $firstRequest->date_requested);

        $this->assertSame(42, $secondRequest->resident_id);
        $this->assertSame('Community Assistance', $secondRequest->service_type);
        $this->assertSame('Medical assistance request', $secondRequest->description);
        $this->assertSame('2026-10-06', $secondRequest->date_requested);
    }
}
