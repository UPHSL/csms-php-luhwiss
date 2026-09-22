<?php

namespace App\Services;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use Illuminate\Database\Eloquent\Collection;

class ResidentQueryService
{
    public function __construct(
        private ResidentRepository $repository
    ) {}

    /**
     * @return Collection<int, Resident>
     */
    public function listResidents(): Collection
    {
        return $this->repository->findAll();
    }

    /**
     * @return Collection<int, Resident>
     */
    public function searchResidents(string $searchTerm): Collection
    {
        $normalizedSearchTerm = trim($searchTerm);

        if ($normalizedSearchTerm === '') {
            return $this->listResidents();
        }

        return $this->repository->searchByName(
            $normalizedSearchTerm
        );
    }
}
