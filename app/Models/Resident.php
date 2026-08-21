<?php

namespace App\Models;

class Resident
{
    const STATUS_ACTIVE = 'Active';

    public function __construct(
        public readonly ?int $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $address,
        public readonly string $contactNumber,
        public readonly string $email,
        public readonly string $status = self::STATUS_ACTIVE,
    ) {}
}
