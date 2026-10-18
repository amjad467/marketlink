<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that unauthenticated guest is redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * Test that authenticated user can access the dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => 'retailer',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }
}
