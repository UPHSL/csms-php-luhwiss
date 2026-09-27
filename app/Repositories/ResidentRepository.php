<?php

namespace App\Repositories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * @return Collection<int, Resident>
     */
    public function findAll(): Collection
    {
        return $this->orderedQuery()->get();
    }

    /**
     * @return Collection<int, Resident>
     */
    public function searchByName(string $searchTerm): Collection
    {
        $pattern = '%'.strtolower($searchTerm).'%';

        return $this->orderedQuery()
            ->where(function (Builder $query) use ($pattern): void {
                $query
                    ->whereRaw(
                        'LOWER(first_name) LIKE ?',
                        [$pattern]
                    )
                    ->orWhereRaw(
                        'LOWER(last_name) LIKE ?',
                        [$pattern]
                    );
            })
            ->get();
    }

    public function deactivateById(int $id): ?Resident
    {
        $resident = $this->findById($id);

        if ($resident === null) {
            return null;
        }

        $resident->status = 'Inactive';
        $resident->save();

        return $resident;
    }

    public function update(Resident $resident, array $data): Resident
    {
        $resident->fill($data);
        $resident->save();

        return $resident;
    }

    private function orderedQuery(): Builder
    {
        return Resident::query()
            ->orderByRaw('LOWER(last_name) ASC')
            ->orderByRaw('LOWER(first_name) ASC')
            ->orderBy('id');
    }
}
