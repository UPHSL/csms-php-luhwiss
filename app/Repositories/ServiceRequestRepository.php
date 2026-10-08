<?php

namespace App\Repositories;

use App\Models\ServiceRequest;

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

    public function updateStatus(
        ServiceRequest $serviceRequest,
        string $status
    ): ServiceRequest {
        $serviceRequest->status = $status;
        $serviceRequest->save();

        return $serviceRequest;
    }
}
