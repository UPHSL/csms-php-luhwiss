<?php

namespace App\Services;

use App\Models\ServiceRequest;

class ServiceRequestSubmissionResult
{
    public function __construct(
        public bool $success,
        public ?ServiceRequest $serviceRequest,
        public string $outcome,
        public array $errors = []
    ) {}

    public static function submitted(
        ServiceRequest $serviceRequest
    ): self {
        return new self(
            success: true,
            serviceRequest: $serviceRequest,
            outcome: 'submitted',
            errors: []
        );
    }

    public static function validationFailed(array $errors): self
    {
        return new self(
            success: false,
            serviceRequest: null,
            outcome: 'validation_failed',
            errors: $errors
        );
    }

    public static function residentNotFound(): self
    {
        return new self(
            success: false,
            serviceRequest: null,
            outcome: 'resident_not_found',
            errors: []
        );
    }

    public static function residentInactive(): self
    {
        return new self(
            success: false,
            serviceRequest: null,
            outcome: 'resident_inactive',
            errors: []
        );
    }
}
