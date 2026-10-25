<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Repositories\ResidentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResidentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'address' => 'Barangay Santo Tomas',
            'contact_number' => '09171234567',
            'email' => 'maria@example.com',
        ], $overrides);
    }

    private function persistResident(array $overrides = []): Resident
    {
        return (new ResidentRepository)->save(new Resident($this->validInput($overrides)));
    }

    public function test_resident_management_page_loads_with_clear_actions_and_empty_message(): void
    {
        $this->get('/residents')->assertOk()
            ->assertSee('Resident Management')
            ->assertSee('Register Resident')
            ->assertSee('Search residents')
            ->assertSee('No Residents found.');
    }

    public function test_list_displays_all_persisted_resident_information_and_status(): void
    {
        $first = $this->persistResident();
        $second = $this->persistResident([
            'first_name' => 'Juan', 'last_name' => 'Reyes', 'status' => 'Inactive',
        ]);

        $response = $this->get('/residents')->assertOk();

        foreach ([$first, $second] as $resident) {
            foreach (['id', 'first_name', 'last_name', 'address', 'contact_number', 'email', 'status'] as $field) {
                $response->assertSee((string) $resident->$field);
            }
            $response->assertSee('/residents/'.$resident->id.'/edit');
        }
        $response->assertSee('/residents/'.$first->id.'/deactivate');
        $response->assertDontSee('/residents/'.$second->id.'/deactivate');
    }

    public static function searchTerms(): array
    {
        return [['mAr'], ['sAn'], ['   SANTOS   ']];
    }

    #[DataProvider('searchTerms')]
    public function test_search_uses_partial_case_insensitive_trimmed_name_matching(string $term): void
    {
        $this->persistResident();
        $this->persistResident(['first_name' => 'Pedro', 'last_name' => 'Reyes']);

        $this->get('/residents?'.http_build_query(['q' => $term]))->assertOk()
            ->assertSee('Maria')->assertSee('Santos')->assertDontSee('Pedro');
    }

    public static function blankSearchTerms(): array
    {
        return [[''], ['   ']];
    }

    #[DataProvider('blankSearchTerms')]
    public function test_blank_search_displays_all_residents(string $term): void
    {
        $this->persistResident();
        $this->persistResident(['first_name' => 'Pedro']);

        $this->get('/residents?'.http_build_query(['q' => $term]))->assertOk()
            ->assertSee('Maria')->assertSee('Pedro');
    }

    public function test_list_and_search_preserve_t05_last_name_first_name_and_id_order(): void
    {
        $this->persistResident(['first_name' => 'Zoe', 'last_name' => 'Santos']);
        $first = $this->persistResident(['first_name' => 'Ana', 'last_name' => 'Santos']);
        $tie = $this->persistResident(['first_name' => 'Ana', 'last_name' => 'Santos']);
        $this->persistResident(['first_name' => 'Luis', 'last_name' => 'Cruz']);

        $this->get('/residents')->assertOk()->assertSeeInOrder(['Luis', 'Ana', 'Zoe']);
        $this->get('/residents?q=sAn')->assertOk()->assertSeeInOrder([
            '/residents/'.$first->id.'/edit', '/residents/'.$tie->id.'/edit', 'Zoe',
        ]);
    }

    public function test_search_with_no_matches_renders_a_normal_empty_result(): void
    {
        $this->persistResident();

        $this->get('/residents?q=nonexistent')->assertOk()
            ->assertSee('No Residents found.')->assertDontSee('Maria');
    }

    public function test_registration_form_has_labeled_fields_and_no_id_or_status_input(): void
    {
        $response = $this->get('/residents/create')->assertOk()
            ->assertSee('Register Resident')->assertSee('Back to Resident Management')
            ->assertSee('name="_token"', false);

        foreach (['first_name', 'last_name', 'address', 'contact_number', 'email'] as $field) {
            $response->assertSee('name="'.$field.'"', false)
                ->assertSee('for="'.$field.'"', false);
        }
        $response->assertDontSee('name="id"', false)->assertDontSee('name="status"', false);
    }

    public function test_valid_registration_persists_with_generated_id_active_status_and_redirect(): void
    {
        $this->post('/residents', $this->validInput())->assertRedirect('/residents')
            ->assertSessionHas('success', 'Resident registered successfully.');

        $resident = Resident::first();
        $this->assertNotNull($resident);
        $this->assertIsInt($resident->id);
        $this->assertSame('Active', $resident->status);
        $this->assertDatabaseHas('residents', $this->validInput());
        $this->get('/residents')->assertOk()->assertSee('Maria')->assertSee('Resident registered successfully.');
        $this->get('/residents')->assertOk();
        $this->assertDatabaseCount('residents', 1);
    }

    public static function invalidInputs(): array
    {
        return [
            ['first_name', ''], ['last_name', '   '], ['address', ''],
            ['contact_number', '123'], ['email', 'invalid-email'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_registration_shows_feedback_keeps_input_and_does_not_persist(string $field, string $value): void
    {
        $input = $this->validInput([$field => $value]);

        $response = $this->from('/residents/create')->post('/residents', $input)
            ->assertRedirect('/residents/create');
        // Follow the redirect before inspecting session data: Laravel's JSON session
        // error bag must be hydrated once per request, as in a real browser.
        $this->followRedirects($response)->assertOk()
            ->assertSee('Please correct the highlighted fields.')
            ->assertSee('id="'.$field.'-error"', false)
            ->assertSee('value="'.$input['email'].'"', false)
            ->assertSee('aria-invalid="true"', false);
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_edit_form_prefills_fields_and_displays_id_and_status_without_editable_inputs(): void
    {
        $resident = $this->persistResident(['status' => 'Inactive']);

        $response = $this->get('/residents/'.$resident->id.'/edit')->assertOk()
            ->assertSee('Edit Resident')->assertSee('Resident #'.$resident->id)
            ->assertSee('Inactive')->assertDontSee('name="id"', false)
            ->assertDontSee('name="status"', false)->assertSee('name="_token"', false);
        foreach ($this->validInput() as $value) {
            $response->assertSee($value);
        }
    }

    public static function residentStatuses(): array
    {
        return [['Active'], ['Inactive']];
    }

    #[DataProvider('residentStatuses')]
    public function test_valid_update_changes_permitted_fields_and_preserves_identity_and_status(string $status): void
    {
        $resident = $this->persistResident(['status' => $status]);
        $input = $this->validInput([
            'first_name' => 'Ana', 'last_name' => 'Garcia', 'address' => 'New address',
            'contact_number' => '09987654321', 'email' => 'ana@example.com',
        ]);

        $this->put('/residents/'.$resident->id, $input)->assertRedirect('/residents')
            ->assertSessionHas('success', 'Resident information updated successfully.');

        $this->assertDatabaseHas('residents', array_merge($input, ['id' => $resident->id, 'status' => $status]));
        $this->get('/residents')->assertOk()->assertSee('Ana')->assertSee('New address');
    }

    public function test_invalid_update_shows_feedback_preserves_input_and_leaves_persistence_unchanged(): void
    {
        $resident = $this->persistResident();
        $before = $resident->fresh()->getAttributes();
        $editUrl = '/residents/'.$resident->id.'/edit';

        $response = $this->from($editUrl)->put('/residents/'.$resident->id, $this->validInput([
            'first_name' => 'Correction', 'contact_number' => 'bad',
        ]))->assertRedirect($editUrl);

        $this->assertSame($before, $resident->fresh()->getAttributes());
        $this->followRedirects($response)->assertOk()->assertSee('value="Correction"', false)
            ->assertSee('Contact number must follow 09XXXXXXXXX.');
    }

    public function test_missing_edit_returns_a_controlled_not_found_page(): void
    {
        $this->get('/residents/999999/edit')->assertNotFound()
            ->assertSee('Resident not found.')->assertSee('Back to Resident Management')
            ->assertDontSee('Stack trace');
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_active_resident_can_be_soft_deactivated_without_losing_information(): void
    {
        $resident = $this->persistResident();
        $before = $resident->only(['id', 'first_name', 'last_name', 'address', 'contact_number', 'email']);

        $this->post('/residents/'.$resident->id.'/deactivate')->assertRedirect('/residents')
            ->assertSessionHas('success', 'Resident deactivated successfully.');

        $this->assertSame('Inactive', $resident->fresh()->status);
        $this->assertSame($before, $resident->fresh()->only(array_keys($before)));
        $this->assertDatabaseCount('residents', 1);
        $this->get('/residents')->assertOk()->assertSee('Maria')->assertSee('Inactive');
    }

    public function test_deactivated_resident_remains_visible_in_listing_search_and_edit(): void
    {
        $resident = $this->persistResident();
        $this->post('/residents/'.$resident->id.'/deactivate')->assertRedirect();

        foreach (['/residents', '/residents?q=san', '/residents/'.$resident->id.'/edit'] as $url) {
            $this->get($url)->assertOk()->assertSee('Maria')->assertSee('Inactive')
                ->assertDontSee('/residents/'.$resident->id.'/deactivate');
        }
    }

    public function test_get_requests_never_mutate_or_deactivate_residents(): void
    {
        $resident = $this->persistResident();
        $before = $resident->fresh()->getAttributes();

        foreach (['/residents', '/residents?q=san', '/residents/create', '/residents/'.$resident->id.'/edit'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/residents/'.$resident->id.'/deactivate')->assertStatus(405);
        $this->assertSame($before, $resident->fresh()->getAttributes());
        $this->assertDatabaseCount('residents', 1);
    }

    public function test_direct_repeated_deactivation_is_safe_and_does_not_affect_another_resident(): void
    {
        $resident = $this->persistResident();
        $other = $this->persistResident(['first_name' => 'Pedro']);
        $this->post('/residents/'.$resident->id.'/deactivate')->assertRedirect('/residents');
        $before = $resident->fresh()->getAttributes();

        $this->post('/residents/'.$resident->id.'/deactivate')->assertRedirect('/residents')
            ->assertSessionHas('success', 'Resident is already Inactive.');

        $this->assertSame($before, $resident->fresh()->getAttributes());
        $this->assertSame('Active', $other->fresh()->status);
        $this->assertDatabaseCount('residents', 2);
    }

    public function test_missing_update_and_deactivation_return_not_found_without_creating_records(): void
    {
        $this->put('/residents/999999', $this->validInput())->assertNotFound();
        $this->post('/residents/999999/deactivate')->assertNotFound();
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_oversized_ids_return_controlled_not_found_without_changing_records(): void
    {
        $resident = $this->persistResident();
        $before = $resident->fresh()->getAttributes();
        $id = '999999999999999999999999999';

        $this->get('/residents/'.$id.'/edit')->assertNotFound()->assertSee('Resident not found.');
        $this->put('/residents/'.$id, $this->validInput())->assertNotFound();
        $this->post('/residents/'.$id.'/deactivate')->assertNotFound();

        $this->assertSame($before, $resident->fresh()->getAttributes());
        $this->assertDatabaseCount('residents', 1);
    }

    public function test_registration_ignores_client_supplied_identity_and_status(): void
    {
        $this->post('/residents', $this->validInput(['id' => 98765, 'status' => 'Inactive']))
            ->assertRedirect('/residents');

        $resident = Resident::first();
        $this->assertNotSame(98765, $resident->id);
        $this->assertSame('Active', $resident->status);
    }

    public function test_update_ignores_client_supplied_identity_and_status(): void
    {
        $resident = $this->persistResident(['status' => 'Inactive']);

        $this->put('/residents/'.$resident->id, $this->validInput([
            'id' => 98765, 'status' => 'Active', 'first_name' => 'Ana',
        ]))->assertRedirect('/residents');

        $this->assertSame('Ana', $resident->fresh()->first_name);
        $this->assertSame('Inactive', $resident->fresh()->status);
        $this->assertDatabaseMissing('residents', ['id' => 98765]);
    }

    public function test_resident_content_is_html_escaped(): void
    {
        $this->persistResident(['first_name' => '<script>alert(1)</script>']);

        $this->get('/residents')->assertOk()->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_malformed_array_input_shows_validation_feedback_without_a_server_error(): void
    {
        $this->post('/residents', $this->validInput(['first_name' => ['bad']]))
            ->assertRedirect('/residents/create')->assertSessionHasErrors('first_name');
        $this->get('/residents/create')->assertOk();
        $this->get('/residents?q[]=bad')->assertOk();
        $this->assertDatabaseCount('residents', 0);
    }

    public function test_home_page_provides_a_path_to_resident_management(): void
    {
        $this->get('/')->assertOk()->assertSee('href="http://localhost/residents"', false)
            ->assertSee('Resident Management');
    }
}
