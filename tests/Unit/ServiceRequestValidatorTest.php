<?php

namespace Tests\Unit;

use App\Models\ServiceRequest;
use App\Services\ServiceRequestValidator;
use Tests\TestCase;

class ServiceRequestValidatorTest extends TestCase
{
    private ServiceRequestValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ServiceRequestValidator;
    }

    private function makeValidServiceRequest(
        array $overrides = []
    ): ServiceRequest {
        $idWasProvided = array_key_exists('id', $overrides);
        $id = $overrides['id'] ?? null;
        unset($overrides['id']);

        $serviceRequest = new ServiceRequest(array_merge([
            'resident_id' => 25,
            'service_type' => 'Barangay Clearance',
            'description' => 'Request for employment requirement',
            'date_requested' => '2026-10-01',
        ], $overrides));

        if ($idWasProvided) {
            $serviceRequest->id = $id;
        }

        return $serviceRequest;
    }

    public function test_valid_service_request_passes_validation(): void
    {
        $serviceRequest = $this->makeValidServiceRequest();

        $this->assertTrue(
            $this->validator->isValid($serviceRequest)
        );
    }

    public function test_assigned_service_request_id_fails_validation(): void
    {
        $serviceRequest = $this->makeValidServiceRequest(['id' => 17]);

        $validation = $this->validator->validate($serviceRequest);

        $this->assertTrue($validation->fails());
        $this->assertTrue($validation->errors()->has('id'));
    }

    public function test_zero_service_request_id_fails_validation(): void
    {
        $serviceRequest = $this->makeValidServiceRequest(['id' => 0]);

        $validation = $this->validator->validate($serviceRequest);

        $this->assertTrue($validation->fails());
        $this->assertTrue($validation->errors()->has('id'));
    }

    public function test_invalid_resident_ids_fail_validation(): void
    {
        foreach ([null, 0, -1, 'resident'] as $residentId) {
            $serviceRequest = $this->makeValidServiceRequest([
                'resident_id' => $residentId,
            ]);

            $validation = $this->validator->validate($serviceRequest);

            $this->assertTrue($validation->fails());
            $this->assertTrue(
                $validation->errors()->has('resident_id')
            );
        }
    }

    public function test_blank_service_types_fail_validation(): void
    {
        foreach (['', '   ', "\t\n"] as $serviceType) {
            $serviceRequest = $this->makeValidServiceRequest([
                'service_type' => $serviceType,
            ]);

            $validation = $this->validator->validate($serviceRequest);

            $this->assertTrue($validation->fails());
            $this->assertTrue(
                $validation->errors()->has('service_type')
            );
        }
    }

    public function test_blank_descriptions_fail_validation(): void
    {
        foreach (['', '   ', "\t\n"] as $description) {
            $serviceRequest = $this->makeValidServiceRequest([
                'description' => $description,
            ]);

            $validation = $this->validator->validate($serviceRequest);

            $this->assertTrue($validation->fails());
            $this->assertTrue(
                $validation->errors()->has('description')
            );
        }
    }

    public function test_invalid_request_dates_fail_validation(): void
    {
        foreach ([null, '', '10/01/2026', '2026-02-30'] as $date) {
            $serviceRequest = $this->makeValidServiceRequest([
                'date_requested' => $date,
            ]);

            $validation = $this->validator->validate($serviceRequest);

            $this->assertTrue($validation->fails());
            $this->assertTrue(
                $validation->errors()->has('date_requested')
            );
        }
    }

    public function test_non_pending_statuses_fail_validation(): void
    {
        foreach (['In Progress', 'Completed', 'Cancelled'] as $status) {
            $serviceRequest = $this->makeValidServiceRequest([
                'status' => $status,
            ]);

            $validation = $this->validator->validate($serviceRequest);

            $this->assertTrue($validation->fails());
            $this->assertTrue($validation->errors()->has('status'));
        }
    }
}
