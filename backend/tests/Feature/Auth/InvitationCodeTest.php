<?php

namespace Tests\Feature\Auth;

use App\Modules\Organizations\Models\InvitationCode;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationCodeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->org = Organization::factory()->create(['name' => 'Test Firm']);
    }

    public function test_lookup_returns_org_name_for_valid_code(): void
    {
        InvitationCode::factory()->for($this->org)->create([
            'code' => 'VALIDCOD',
            'role' => 'manager',
        ]);

        $this->getJson('/api/v1/auth/invitation/VALIDCOD')
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data'    => [
                     'organization_name' => 'Test Firm',
                     'role'              => 'manager',
                 ],
             ]);
    }

    public function test_lookup_returns_404_for_nonexistent_code(): void
    {
        $this->getJson('/api/v1/auth/invitation/BADCODE1')
             ->assertStatus(404)
             ->assertJson([
                 'success' => false,
                 'message' => 'Invalid or expired invitation code.',
             ]);
    }

    public function test_lookup_returns_404_for_used_code(): void
    {
        InvitationCode::factory()->for($this->org)->used()->create(['code' => 'USEDCODE']);

        $this->getJson('/api/v1/auth/invitation/USEDCODE')
             ->assertStatus(404)
             ->assertJson(['success' => false, 'message' => 'Invalid or expired invitation code.']);
    }

    public function test_lookup_returns_404_for_expired_code(): void
    {
        InvitationCode::factory()->for($this->org)->expired()->create(['code' => 'EXPRCODE']);

        $this->getJson('/api/v1/auth/invitation/EXPRCODE')
             ->assertStatus(404)
             ->assertJson(['success' => false, 'message' => 'Invalid or expired invitation code.']);
    }

    public function test_register_fails_with_invalid_invitation_code(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'invitation_code'       => 'BADCODE1',
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@firm.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['invitation_code']);
    }

    public function test_register_fails_with_used_invitation_code(): void
    {
        InvitationCode::factory()->for($this->org)->used()->create(['code' => 'USEDCOD2']);

        $this->postJson('/api/v1/auth/register', [
            'invitation_code'       => 'USEDCOD2',
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@firm.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['invitation_code']);
    }

    public function test_register_assigns_role_from_invitation_code(): void
    {
        InvitationCode::factory()->for($this->org)->create([
            'code' => 'STAFFCOD',
            'role' => 'staff',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'invitation_code'       => 'STAFFCOD',
            'name'                  => 'Bob Staff',
            'email'                 => 'bob@firm.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'data' => ['roles' => ['staff']],
                 ]);
    }
}
