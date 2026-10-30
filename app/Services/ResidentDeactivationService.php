<?php

namespace App\Services;

use App\Repositories\ResidentRepository;

class ResidentDeactivationService
{
    public function __construct(
        private ResidentRepository $repository
    ) {}

    public function deactivateResident(int $id): ResidentDeactivationResult
    {
        $resident = $this->repository->findById($id);

        if ($resident === null) {
            return ResidentDeactivationResult::notFound();
        }

        if ($resident->status === 'Inactive') {
            return ResidentDeactivationResult::alreadyInactive($resident);
        }

        $deactivated = $this->repository->deactivateById($id);

        return ResidentDeactivationResult::deactivated($deactivated);
    }
}
