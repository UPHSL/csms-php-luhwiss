<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Services\ResidentDeactivationService;
use App\Services\ResidentQueryService;
use App\Services\ResidentRegistrationService;
use App\Services\ResidentUpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request, ResidentQueryService $queries): View
    {
        $search = $request->query('q', '');
        $search = is_string($search) ? $search : '';

        return view('residents.index', [
            'residents' => $queries->searchResidents($search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('residents.create');
    }

    public function store(Request $request, ResidentRegistrationService $registration): RedirectResponse
    {
        $input = $this->residentInput($request);
        $result = $registration->registerResident(new Resident($input));

        if (! $result->success) {
            return redirect()->route('residents.create')
                ->withErrors($this->validationMessages($result->errors))
                ->withInput($input);
        }

        return redirect()->route('residents.index')
            ->with('success', 'Resident registered successfully.');
    }

    public function edit(string $resident, ResidentQueryService $queries): View
    {
        $record = $queries->viewResident($this->residentId($resident));
        abort_if($record === null, 404, 'Resident not found.');

        return view('residents.edit', ['resident' => $record]);
    }

    public function update(string $resident, Request $request, ResidentUpdateService $updates): RedirectResponse
    {
        $input = $this->residentInput($request);
        $result = $updates->updateResident($this->residentId($resident), $input);
        abort_if($result->notFound, 404, 'Resident not found.');

        if (! $result->success) {
            return redirect()->route('residents.edit', $resident)
                ->withErrors($this->validationMessages($result->errors))
                ->withInput($input);
        }

        return redirect()->route('residents.index')
            ->with('success', 'Resident information updated successfully.');
    }

    public function deactivate(string $resident, ResidentDeactivationService $deactivation): RedirectResponse
    {
        $result = $deactivation->deactivateResident($this->residentId($resident));
        abort_if($result->notFound, 404, 'Resident not found.');

        return redirect()->route('residents.index')->with(
            'success',
            $result->alreadyInactive ? 'Resident is already Inactive.' : 'Resident deactivated successfully.'
        );
    }

    private function residentId(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404, 'Resident not found.');

        return $id;
    }

    private function residentInput(Request $request): array
    {
        $input = [];

        // Accept only form fields; identity and lifecycle status are never editable.
        foreach (['first_name', 'last_name', 'address', 'contact_number', 'email'] as $field) {
            $value = $request->input($field);
            $input[$field] = is_string($value) ? $value : '';
        }

        return $input;
    }

    private function validationMessages(array $fields): array
    {
        // Services return failed field names. Translate those into presentation feedback.
        $messages = [
            'first_name' => 'Enter a first name.',
            'last_name' => 'Enter a last name.',
            'address' => 'Enter an address.',
            'contact_number' => 'Contact number must follow 09XXXXXXXXX.',
            'email' => 'Enter a valid email address.',
        ];

        $errors = [];
        foreach ($fields as $field) {
            $errors[$field] = $messages[$field] ?? 'Please check the Resident information.';
        }

        return $errors;
    }
}
