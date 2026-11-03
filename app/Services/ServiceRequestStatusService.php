<?php

namespace App\Services;

use App\Repositories\ServiceRequestRepository;

class ServiceRequestStatusService
{
    private const SUPPORTED_STATUSES = [
        'Pending',
        'In Progress',
        'Completed',
        'Cancelled',
    ];

    private const ALLOWED_TRANSITIONS = [
        'Pending' => ['In Progress', 'Cancelled'],
        'In Progress' => ['Completed', 'Cancelled'],
        'Completed' => [],
        'Cancelled' => [],
    ];

    public function __construct(
        private ServiceRequestRepository $repository
    ) {}

    public function manageStatus(
        int $serviceRequestId,
        string $requestedStatus
    ): ServiceRequestStatusResult {
        $serviceRequest = $this->repository->findById($serviceRequestId);

        if ($serviceRequest === null) {
            return ServiceRequestStatusResult::notFound();
        }

        if (! in_array($requestedStatus, self::SUPPORTED_STATUSES, true)) {
            return ServiceRequestStatusResult::unsupportedStatus();
        }

        $currentStatus = $serviceRequest->status;
        $allowedTargets = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($requestedStatus, $allowedTargets, true)) {
            return ServiceRequestStatusResult::invalidTransition();
        }

        $updated = $this->repository->updateStatus(
            $serviceRequest,
            $requestedStatus
        );

        return ServiceRequestStatusResult::updated($updated);
    }
}
