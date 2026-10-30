<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private function resident(string $status = 'Active'): Resident
    {
        return Resident::create([
            'first_name' => 'Maria', 'last_name' => 'Santos',
            'address' => 'Barangay Santo Tomas', 'contact_number' => '09171234567',
            'email' => 'maria@example.com', 'status' => $status,
        ]);
    }

    private function input(array $overrides = []): array
    {
        return array_merge([
            'resident_id' => $this->resident()->id,
            'service_type' => 'Barangay Clearance',
            'description' => 'Request for employment requirements.',
            'date_requested' => '2026-10-01',
        ], $overrides);
    }

    private function record(array $overrides = []): ServiceRequest
    {
        return ServiceRequest::create($this->input($overrides));
    }

    public function test_management_page_loads_and_handles_an_empty_list(): void
    {
        $this->get('/service-requests')->assertOk()
            ->assertSee('Service Request Management')->assertSee('Submit Service Request')
            ->assertSee('Search service requests')->assertSee('All Statuses')
            ->assertSee('No Service Requests found.');
    }

    public function test_persisted_requests_display_required_information_and_actions(): void
    {
        $pending = $this->record();
        $processing = $this->record(['service_type' => 'Certificate of Indigency']);
        app(ServiceRequestStatusService::class)->manageStatus($processing->id, 'In Progress');
        $response = $this->get('/service-requests')->assertOk();
        foreach ([$pending, $processing->fresh()] as $record) {
            foreach (['id', 'resident_id', 'service_type', 'date_requested', 'status'] as $field) {
                $response->assertSee((string) $record->$field);
            }
            $response->assertSee('/service-requests/'.$record->id);
        }
        $response->assertSee('/service-requests/'.$pending->id.'/start')
            ->assertDontSee('/service-requests/'.$pending->id.'/complete')
            ->assertSee('/service-requests/'.$processing->id.'/complete')
            ->assertDontSee('/service-requests/'.$processing->id.'/start');
    }

    public function test_listing_and_search_preserve_date_then_id_descending_order(): void
    {
        $old = $this->record(['date_requested' => '2026-01-01']);
        $first = $this->record(['date_requested' => '2026-10-10']);
        $second = $this->record(['date_requested' => '2026-10-10']);
        foreach (['/service-requests', '/service-requests?q=clear'] as $url) {
            $this->get($url)->assertOk()->assertSeeInOrder([
                '/service-requests/'.$second->id.'"',
                '/service-requests/'.$first->id.'"',
                '/service-requests/'.$old->id.'"',
            ], false);
        }
    }

    public static function searchTerms(): array
    {
        return [['cLeAr'], ['EMPLOY'], ['   clearance   ']];
    }

    #[DataProvider('searchTerms')]
    public function test_search_uses_partial_case_insensitive_trimmed_t11_behavior(string $term): void
    {
        $this->record();
        $this->record(['service_type' => 'Other service', 'description' => 'Unrelated description']);
        $this->get('/service-requests?'.http_build_query(['q' => $term]))->assertOk()
            ->assertSee('Barangay Clearance')->assertDontSee('Other service');
    }

    public static function statuses(): array
    {
        return [['Pending'], ['In Progress'], ['Completed'], ['Cancelled']];
    }

    #[DataProvider('statuses')]
    public function test_each_status_filter_uses_t11_behavior(string $status): void
    {
        foreach (self::statuses() as [$value]) {
            $this->record(['status' => $value, 'service_type' => 'Only '.$value]);
        }
        $response = $this->get('/service-requests?'.http_build_query(['status' => $status]))->assertOk()
            ->assertSee('Only '.$status);
        foreach (self::statuses() as [$value]) {
            if ($value !== $status) {
                $response->assertDontSee('Only '.$value);
            }
        }
    }

    public function test_combined_search_and_filter_require_both_and_preserve_inputs(): void
    {
        $this->record(['service_type' => 'Clearance both']);
        $this->record(['service_type' => 'Clearance text only', 'status' => 'Completed']);
        $this->record(['service_type' => 'Status only', 'description' => 'Other']);
        $this->record(['service_type' => 'Neither', 'description' => 'Other', 'status' => 'Cancelled']);
        $this->get('/service-requests?q=clearance&status=Pending')->assertOk()
            ->assertSee('Clearance both')->assertDontSee('Clearance text only')
            ->assertDontSee('Status only')->assertDontSee('Neither')
            ->assertSee('value="clearance"', false)->assertSee('value="Pending" selected', false);
    }

    public function test_submission_form_contains_only_the_required_editable_fields(): void
    {
        $response = $this->get('/service-requests/create')->assertOk();
        foreach (['resident_id', 'service_type', 'description', 'date_requested'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
        $response->assertDontSee('name="id"', false)->assertDontSee('name="status"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_valid_submission_persists_generated_identity_and_pending_status(): void
    {
        $input = $this->input();
        $this->post('/service-requests', $input + ['id' => 98765, 'status' => 'Completed'])
            ->assertRedirect('/service-requests')->assertSessionHas('success');
        $record = ServiceRequest::sole();
        $this->assertNotSame(98765, $record->id);
        $this->assertGreaterThan(0, $record->id);
        $this->assertDatabaseHas('service_requests', $input + ['status' => 'Pending']);
        $this->get('/service-requests')->assertOk()->assertSee('Service Request submitted successfully.');
        $this->get('/service-requests')->assertOk();
        $this->assertDatabaseCount('service_requests', 1);
    }

    public static function invalidFields(): array
    {
        return [['resident_id', '0'], ['service_type', '   '], ['description', ''], ['date_requested', 'invalid']];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_submission_shows_feedback_preserves_input_and_does_not_persist(string $field, string $value): void
    {
        $input = $this->input([$field => $value]);
        $response = $this->post('/service-requests', $input)->assertRedirect('/service-requests/create');
        // Follow redirects before inspecting Laravel's JSON-serialized session errors.
        $this->followRedirects($response)->assertOk()->assertSee('Please correct')
            ->assertSee('id="'.$field.'-error"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('value="'.trim($input['service_type']).'"', false);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_missing_resident_submission_is_rejected_with_feedback(): void
    {
        $response = $this->post('/service-requests', $this->input(['resident_id' => 999999]))
            ->assertRedirect('/service-requests/create');
        $this->followRedirects($response)->assertOk()->assertSee('Resident not found.');
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_inactive_resident_submission_is_rejected_with_eligibility_feedback(): void
    {
        $resident = $this->resident('Inactive');
        $response = $this->post('/service-requests', $this->input(['resident_id' => $resident->id]))
            ->assertRedirect('/service-requests/create');
        $this->followRedirects($response)->assertOk()->assertSee('Inactive Residents cannot submit new Service Requests.');
        $this->assertSame('Inactive', $resident->fresh()->status);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_details_display_every_persisted_field_and_pending_actions(): void
    {
        $record = $this->record();
        $response = $this->get('/service-requests/'.$record->id)->assertOk();
        foreach (['id', 'resident_id', 'service_type', 'description', 'date_requested', 'status'] as $field) {
            $response->assertSee((string) $record->$field);
        }
        $response->assertSee('Start Processing')->assertSee('Cancel')->assertDontSee('>Complete<', false);
    }

    public function test_missing_and_oversized_ids_return_controlled_not_found_for_views_and_actions(): void
    {
        foreach (['999999', '9999999999999999999999999'] as $id) {
            $this->get('/service-requests/'.$id)->assertNotFound()->assertSee('Service Request not found.');
            foreach (['start', 'complete', 'cancel'] as $action) {
                $this->post('/service-requests/'.$id.'/'.$action)->assertNotFound();
            }
        }
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_pending_request_can_start_processing(): void
    {
        $record = $this->record();
        $this->assertWorkflow($record, 'start', 'In Progress');
        $this->get('/service-requests/'.$record->id)->assertOk()->assertSee('Complete')
            ->assertSee('Cancel')->assertDontSee('Start Processing');
    }

    public function test_pending_request_can_be_cancelled(): void
    {
        $this->assertWorkflow($this->record(), 'cancel', 'Cancelled');
    }

    public function test_in_progress_request_can_be_completed(): void
    {
        $record = $this->record();
        app(ServiceRequestStatusService::class)->manageStatus($record->id, 'In Progress');
        $this->assertWorkflow($record->fresh(), 'complete', 'Completed');
    }

    public function test_in_progress_request_can_be_cancelled(): void
    {
        $record = $this->record();
        app(ServiceRequestStatusService::class)->manageStatus($record->id, 'In Progress');
        $this->assertWorkflow($record->fresh(), 'cancel', 'Cancelled');
    }

    private function assertWorkflow(ServiceRequest $record, string $action, string $target): void
    {
        $before = $record->getAttributes();
        $this->post('/service-requests/'.$record->id.'/'.$action, ['status' => 'Pending'])
            ->assertRedirect('/service-requests/'.$record->id)->assertSessionHas('success');
        $after = $record->fresh()->getAttributes();
        $this->assertSame($target, $after['status']);
        foreach (['id', 'resident_id', 'service_type', 'description', 'date_requested'] as $field) {
            $this->assertSame($before[$field], $after[$field]);
        }
        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_invalid_direct_transitions_and_repeated_actions_cannot_change_persistence(): void
    {
        foreach (['Pending' => ['complete'], 'In Progress' => ['start'], 'Completed' => ['start', 'complete', 'cancel'], 'Cancelled' => ['start', 'complete', 'cancel']] as $status => $actions) {
            $record = $this->record(['status' => $status]);
            $before = $record->fresh()->getAttributes();
            foreach ($actions as $action) {
                $response = $this->post('/service-requests/'.$record->id.'/'.$action)
                    ->assertRedirect('/service-requests/'.$record->id);
                $this->followRedirects($response)->assertOk()
                    ->assertSee('This action is not allowed for the current Service Request status.');
                $this->assertSame($before, $record->fresh()->getAttributes());
            }
        }
    }

    public function test_terminal_requests_remain_listed_searchable_filterable_and_viewable_without_actions(): void
    {
        foreach (['Completed', 'Cancelled'] as $status) {
            $record = $this->record(['status' => $status, 'service_type' => 'Terminal '.$status]);
            foreach (['/service-requests', '/service-requests?q=terminal', '/service-requests?'.http_build_query(['q' => 'terminal', 'status' => $status]), '/service-requests/'.$record->id] as $url) {
                $response = $this->get($url)->assertOk()->assertSee('Terminal '.$status);
                foreach (['start', 'complete', 'cancel'] as $action) {
                    $response->assertDontSee('/service-requests/'.$record->id.'/'.$action);
                }
            }
            $this->get('/service-requests/'.$record->id)->assertSee('Terminal state');
        }
    }

    public function test_get_requests_do_not_create_requests_or_change_state(): void
    {
        $record = $this->record();
        $before = $record->fresh()->getAttributes();
        foreach (['/service-requests', '/service-requests?q=clear', '/service-requests?status=Pending', '/service-requests/create', '/service-requests/'.$record->id] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['start', 'complete', 'cancel'] as $action) {
            $this->get('/service-requests/'.$record->id.'/'.$action)->assertStatus(405);
        }
        $this->assertSame($before, $record->fresh()->getAttributes());
        $this->assertDatabaseCount('service_requests', 1);
    }

    public function test_blank_and_all_status_queries_list_everything_and_no_match_is_safe(): void
    {
        $this->record(['service_type' => 'First', 'status' => 'Completed']);
        $this->record(['service_type' => 'Second']);
        foreach (['/service-requests?q=', '/service-requests?q=%20%20&status='] as $url) {
            $this->get($url)->assertOk()->assertSee('First')->assertSee('Second');
        }
        $this->get('/service-requests?q=nonexistent')->assertOk()->assertSee('No Service Requests found.');
    }

    public function test_malformed_inputs_and_unsupported_filter_are_handled_without_server_errors(): void
    {
        $this->post('/service-requests', $this->input(['description' => ['bad']]))
            ->assertRedirect('/service-requests/create')->assertSessionHasErrors('description');
        $this->get('/service-requests/create')->assertOk();
        $this->get('/service-requests?q[]=bad')->assertOk();
        $this->get('/service-requests?status=unsupported')->assertStatus(422)->assertSee('Unsupported Service Request status filter.');
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_navigation_between_modules_and_html_escaping(): void
    {
        $record = $this->record(['service_type' => '<script>alert(1)</script>', 'description' => '<b>Full description</b>']);
        $this->get('/residents')->assertOk()->assertSee('href="http://localhost/service-requests"', false);
        $this->get('/service-requests')->assertOk()->assertSee('href="http://localhost/residents"', false)
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/service-requests/'.$record->id)->assertOk()->assertSee('&lt;b&gt;Full description&lt;/b&gt;', false);
    }
}
