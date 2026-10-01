<?php

namespace App\Services;

use App\Models\ServiceRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;

class ServiceRequestValidator
{
    public function validate(
        ServiceRequest $serviceRequest
    ): ValidatorContract {
        return Validator::make(
            [
                'id' => $serviceRequest->id,
                'resident_id' => $serviceRequest->resident_id,
                'service_type' => $serviceRequest->service_type,
                'description' => $serviceRequest->description,
                'date_requested' => $serviceRequest->date_requested,
                'status' => $serviceRequest->status,
            ],
            self::rules()
        );
    }

    public function isValid(ServiceRequest $serviceRequest): bool
    {
        return ! $this->validate($serviceRequest)->fails();
    }

    public static function rules(): array
    {
        return [
            'id' => [
                'prohibited',
            ],
            'resident_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'service_type' => [
                'required',
                'string',
                'not_regex:/^\s*$/u',
            ],
            'description' => [
                'required',
                'string',
                'not_regex:/^\s*$/u',
            ],
            'date_requested' => [
                'required',
                'date_format:Y-m-d',
            ],
            'status' => [
                'required',
                'string',
                'in:Pending',
            ],
        ];
    }
}
