<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Organizations\Models\InvitationCode;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private InvitationCode $invitation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->org        = Organization::factory()->create(['name' => 'Acme Corp']);
        $this->invitation = InvitationCode::factory()->for($this->org)->create([
            'code' => 'TESTCODE',
            'role' => 'admin',
        ]);
    }

    public function test_user_can_register_with_valid_invitation_code(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'invitation_code'       => 'TESTCODE',
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

        $user = User::where('email', 'jane@acme.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->org->id, $user->organization_id);
        $this->assertTrue($user->hasRole('admin'));

        $this->assertDatabaseHas('invitation_codes', [
            'code'    => 'TESTCODE',
            'used_by' => $user->id,
        ]);
        $this->assertNotNull($this->invitation->fresh()->used_at);
    }

    public function test_register_creates_audit_log(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'invitation_code'       => 'TESTCODE',
            'name'                  => 'John Smith',
            'email'                 => 'john@acme.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.registered']);
    }

    public function test_register_requires_invitation_code(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@acme.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['invitation_code']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->for($this->org)->create(['email' => 'taken@acme.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'invitation_code'       => 'TESTCODE',
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
            'invitation_code'       => 'TESTCODE',
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@acme.com',
            'password'              => 'secret123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
}
