<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Services\ServiceRequestQueryService;
use App\Services\ServiceRequestStatusService;
use App\Services\ServiceRequestSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ServiceRequestController extends Controller
{
    public function index(Request $request, ServiceRequestQueryService $queries): View
    {
        $search = $request->query('q', '');
        $search = is_string($search) ? $search : '';
        $status = $request->query('status', '');
        $status = is_string($status) ? $status : '';

        try {
            $records = $queries->searchServiceRequests($search, $status);
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }

        return view('service-requests.index', [
            'serviceRequests' => $records, 'search' => $search, 'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('service-requests.create');
    }

    public function store(Request $request, ServiceRequestSubmissionService $submission): RedirectResponse
    {
        $input = [];
        // Only browser form fields are accepted; T08/T09 own identity and initial status.
        foreach (['resident_id', 'service_type', 'description', 'date_requested'] as $field) {
            $value = $request->input($field);
            $input[$field] = is_string($value) || is_int($value) ? $value : '';
        }

        $result = $submission->submitServiceRequest(new ServiceRequest($input));

        if (! $result->success) {
            $messages = [
                'resident_id' => 'Enter a positive whole-number Resident ID.',
                'service_type' => 'Enter a service type.',
                'description' => 'Enter a description.',
                'date_requested' => 'Enter a valid date requested (YYYY-MM-DD).',
            ];
            $errors = [];
            foreach ($result->errors as $field) {
                $errors[$field] = $messages[$field] ?? 'Please check the Service Request information.';
            }
            if ($result->outcome === 'resident_not_found') {
                $errors['resident_id'] = 'Resident not found.';
            } elseif ($result->outcome === 'resident_inactive') {
                $errors['resident_id'] = 'Inactive Residents cannot submit new Service Requests.';
            }

            return redirect()->route('service-requests.create')->withErrors($errors)->withInput($input);
        }

        return redirect()->route('service-requests.index')
            ->with('success', 'Service Request submitted successfully.');
    }

    public function show(string $serviceRequest, ServiceRequestQueryService $queries): View
    {
        $record = $queries->viewServiceRequest($this->requestId($serviceRequest));
        abort_if($record === null, 404, 'Service Request not found.');

        return view('service-requests.show', ['serviceRequest' => $record]);
    }

    public function start(string $serviceRequest, ServiceRequestStatusService $statuses): RedirectResponse
    {
        return $this->transition($serviceRequest, 'In Progress', $statuses);
    }

    public function complete(string $serviceRequest, ServiceRequestStatusService $statuses): RedirectResponse
    {
        return $this->transition($serviceRequest, 'Completed', $statuses);
    }

    public function cancel(string $serviceRequest, ServiceRequestStatusService $statuses): RedirectResponse
    {
        return $this->transition($serviceRequest, 'Cancelled', $statuses);
    }

    private function transition(string $id, string $target, ServiceRequestStatusService $statuses): RedirectResponse
    {
        // T10 decides whether this operation is permitted, including direct/stale requests.
        $result = $statuses->manageStatus($this->requestId($id), $target);
        abort_if($result->outcome === 'not_found', 404, 'Service Request not found.');

        if (! $result->success) {
            return redirect()->route('service-requests.show', $id)
                ->withErrors(['workflow' => 'This action is not allowed for the current Service Request status.']);
        }

        return redirect()->route('service-requests.show', $id)
            ->with('success', 'Service Request is now '.$result->serviceRequest->status.'.');
    }

    private function requestId(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404, 'Service Request not found.');

        return $id;
    }
}
