<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutMeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'id'    => $user->id,
                         'email' => $user->email,
                     ],
                 ]);
    }

    public function test_unauthenticated_request_to_me_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $response = $this->actingAs($user)->deleteJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logged out successfully.',
                 ]);
    }

    public function test_unauthenticated_request_to_logout_returns_401(): void
    {
        $response = $this->deleteJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_creates_audit_log(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $this->actingAs($user)->deleteJson('/api/v1/auth/logout');

        $this->assertDatabaseHas('audit_logs', [
            'action'  => 'user.logged_out',
            'user_id' => $user->id,
        ]);
    }
}
