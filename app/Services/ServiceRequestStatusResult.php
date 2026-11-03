<?php

namespace App\Services;

use App\Models\ServiceRequest;

class ServiceRequestStatusResult
{
    public function __construct(
        public bool $success,
        public string $outcome,
        public ?ServiceRequest $serviceRequest = null
    ) {}

    public static function updated(ServiceRequest $serviceRequest): self
    {
        return new self(
            success: true,
            outcome: 'updated',
            serviceRequest: $serviceRequest
        );
    }

    public static function notFound(): self
    {
        return new self(
            success: false,
            outcome: 'not_found',
            serviceRequest: null
        );
    }

    public static function unsupportedStatus(): self
    {
        return new self(
            success: false,
            outcome: 'unsupported_status',
            serviceRequest: null
        );
    }

    public static function invalidTransition(): self
    {
        return new self(
            success: false,
            outcome: 'invalid_transition',
            serviceRequest: null
        );
    }
}
