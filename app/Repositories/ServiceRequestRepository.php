<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ServiceRequestRepository
{
    public function save(ServiceRequest $serviceRequest): ServiceRequest
    {
        $serviceRequest->save();

        return $serviceRequest;
    }

    public function findById(int $id): ?ServiceRequest
    {
        return ServiceRequest::find($id);
    }

    /**
     * @return Collection<int, ServiceRequest>
     */
    public function findAll(): Collection
    {
        return $this->orderedQuery()->get();
    }

    /**
     * @return Collection<int, ServiceRequest>
     */
    public function search(string $searchTerm, ?string $statusFilter = null): Collection
    {
        $query = $this->orderedQuery();

        if ($searchTerm !== '') {
            // Escape LIKE metacharacters so the input remains a literal substring.
            $pattern = '%'.str_replace(
                ['!', '%', '_'],
                ['!!', '!%', '!_'],
                strtolower($searchTerm)
            ).'%';

            $query->where(function (Builder $query) use ($pattern): void {
                $query
                    ->whereRaw("LOWER(service_type) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(description) LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        return $query->get();
    }

    public function updateStatus(
        ServiceRequest $serviceRequest,
        string $status
    ): ServiceRequest {
        $serviceRequest->status = $status;
        $serviceRequest->save();

        return $serviceRequest;
    }

    private function orderedQuery(): Builder
    {
        return ServiceRequest::query()
            ->orderByDesc('date_requested')
            ->orderByDesc('id');
    }
}
