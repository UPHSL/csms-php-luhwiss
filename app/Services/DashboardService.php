<?php

namespace App\Services;

use App\Repositories\ResidentRepository;
use App\Repositories\ServiceRequestRepository;

class DashboardService
{
    public function __construct(
        private ResidentRepository $residents,
        private ServiceRequestRepository $serviceRequests
    ) {}

    public function getSummary(): array
    {
        $residentCounts = [
            'total' => $this->residents->countAll(),
            'active' => $this->residents->countByStatus('Active'),
            'inactive' => $this->residents->countByStatus('Inactive'),
        ];
        $requestCounts = [
            'total' => $this->serviceRequests->countAll(),
            'pending' => $this->serviceRequests->countByStatus('Pending'),
            'in_progress' => $this->serviceRequests->countByStatus('In Progress'),
            'completed' => $this->serviceRequests->countByStatus('Completed'),
            'cancelled' => $this->serviceRequests->countByStatus('Cancelled'),
        ];
        $requestCounts['open'] = $requestCounts['pending'] + $requestCounts['in_progress'];

        return [
            'residents' => $residentCounts,
            'serviceRequests' => $requestCounts,
            'recentServiceRequests' => $this->serviceRequests->findRecent(5),
        ];
    }
}
