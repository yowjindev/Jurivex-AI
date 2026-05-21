<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_register_and_creates_organization(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'organization_name'     => 'Acme Corp',
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@acme.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success', 'message', 'meta',
                     'data' => ['id', 'name', 'email', 'organization_id', 'roles', 'created_at'],
                 ])
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'name'  => 'Jane Doe',
                         'email' => 'jane@acme.com',
                         'roles' => ['admin'],
                     ],
                 ]);

        $this->assertDatabaseHas('organizations', ['name' => 'Acme Corp']);
        $this->assertDatabaseHas('users', ['email' => 'jane@acme.com']);

        $user = User::where('email', 'jane@acme.com')->first();
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_register_creates_audit_log(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'organization_name'     => 'Beta LLC',
            'name'                  => 'John Smith',
            'email'                 => 'john@beta.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.registered']);
    }

    public function test_register_requires_organization_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@acme.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['organization_name']);
    }

    public function test_register_requires_unique_email(): void
    {
        $org = Organization::factory()->create();
        User::factory()->for($org)->create(['email' => 'taken@acme.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'organization_name'     => 'New Corp',
            'name'                  => 'Jane Doe',
            'email'                 => 'taken@acme.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'organization_name'     => 'Acme Corp',
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@acme.com',
            'password'              => 'secret123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
