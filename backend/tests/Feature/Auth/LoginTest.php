<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success', 'message', 'meta',
                     'data' => ['id', 'name', 'email', 'organization_id', 'roles', 'created_at'],
                 ])
                 ->assertJson([
                     'success' => true,
                     'data'    => ['email' => 'user@example.com'],
                 ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@nowhere.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_field(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password_field(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_login_creates_audit_log(): void
    {
        $org  = Organization::factory()->create();
        User::factory()->for($org)->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.logged_in']);
    }
}
