<?php

namespace App\Services;

use App\Repositories\ResidentRepository;

class ResidentUpdateService
{
    public function __construct(
        private ResidentValidator $validator,
        private ResidentRepository $repository
    ) {}

    public function updateResident(int $id, array $data): ResidentUpdateResult
    {
        $resident = $this->repository->findById($id);

        if ($resident === null) {
            return ResidentUpdateResult::notFound();
        }

        $candidate = clone $resident;
        $candidate->fill($data);

        $validation = $this->validator->validate($candidate);

        if ($validation->fails()) {
            return ResidentUpdateResult::failed(
                $validation->errors()->keys()
            );
        }

        $permitted = array_diff_key($data, ['status' => true, 'id' => true]);

        $updated = $this->repository->update($resident, $permitted);

        return ResidentUpdateResult::successful($updated);
    }
}
