<?php

namespace App\Services;

use App\Models\Resident;

class ResidentUpdateResult
{
    public function __construct(
        public bool $success,
        public bool $notFound,
        public ?Resident $resident = null,
        public array $errors = []
    ) {}

    public static function successful(Resident $resident): self
    {
        return new self(
            success: true,
            notFound: false,
            resident: $resident,
            errors: []
        );
    }

    public static function failed(array $errors): self
    {
        return new self(
            success: false,
            notFound: false,
            resident: null,
            errors: $errors
        );
    }

    public static function notFound(): self
    {
        return new self(
            success: false,
            notFound: true,
            resident: null,
            errors: []
        );
    }
}
