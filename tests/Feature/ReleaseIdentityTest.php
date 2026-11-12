<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_release_version_is_visible_across_the_browser_application(): void
    {
        foreach (['/', '/dashboard', '/residents', '/residents/create', '/service-requests', '/service-requests/create'] as $path) {
            $this->get($path)->assertOk()->assertSee('v1.0.0');
        }
    }

    public function test_health_and_browser_views_obtain_their_version_from_the_same_configuration(): void
    {
        config(['csms.version' => '1.0.1']);

        $this->get('/health')->assertOk()->assertExactJson([
            'status' => 'ok',
            'application' => 'Community Services Management System',
            'version' => '1.0.1',
        ]);

        foreach (['/', '/dashboard', '/residents', '/service-requests'] as $path) {
            $this->get($path)->assertOk()->assertSee('v1.0.1')->assertDontSee('v1.0.0');
        }
    }
}
