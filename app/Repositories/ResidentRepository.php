<?php

namespace App\Repositories;

use App\Models\Resident;

class ResidentRepository
{
    public function save(Resident $resident): Resident
    {
        $resident->save();

        return $resident;
    }

    public function findById(int $id): ?Resident
    {
        return Resident::find($id);
    }
}
