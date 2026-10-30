<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Repositories\ServiceRequestRepository;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ServiceRequestQueryService
{
    private const SUPPORTED_STATUSES = [
        'Pending',
        'In Progress',
        'Completed',
        'Cancelled',
    ];

    public function __construct(
        private ServiceRequestRepository $repository
    ) {}

    /**
     * @return Collection<int, ServiceRequest>
     */
    public function listServiceRequests(): Collection
    {
        return $this->repository->findAll();
    }

    public function viewServiceRequest(int $serviceRequestId): ?ServiceRequest
    {
        return $this->repository->findById($serviceRequestId);
    }

    /**
     * @return Collection<int, ServiceRequest>
     *
     * @throws InvalidArgumentException When a nonempty status is unsupported.
     */
    public function searchServiceRequests(
        ?string $searchTerm = null,
        ?string $statusFilter = null
    ): Collection {
        $normalizedSearchTerm = trim($searchTerm ?? '');
        $statusFilter = $statusFilter === '' ? null : $statusFilter;

        if ($statusFilter !== null && ! in_array($statusFilter, self::SUPPORTED_STATUSES, true)) {
            throw new InvalidArgumentException('Unsupported Service Request status filter.');
        }

        if ($normalizedSearchTerm === '' && $statusFilter === null) {
            return $this->listServiceRequests();
        }

        return $this->repository->search($normalizedSearchTerm, $statusFilter);
    }
}
