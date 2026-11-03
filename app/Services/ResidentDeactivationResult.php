<?php

namespace App\Services;

use App\Models\Resident;

class ResidentDeactivationResult
{
    public function __construct(
        public bool $success,
        public bool $alreadyInactive,
        public bool $notFound,
        public ?Resident $resident = null
    ) {}

    public static function deactivated(Resident $resident): self
    {
        return new self(
            success: true,
            alreadyInactive: false,
            notFound: false,
            resident: $resident
        );
    }

    public static function alreadyInactive(Resident $resident): self
    {
        return new self(
            success: true,
            alreadyInactive: true,
            notFound: false,
            resident: $resident
        );
    }

    public static function notFound(): self
    {
        return new self(
            success: false,
            alreadyInactive: false,
            notFound: true,
            resident: null
        );
    }
}
