<?php

namespace Tests\Feature\Superadmin;

use App\Models\User;
use App\Modules\Organizations\Models\InvitationCode;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $regularAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $platformOrg        = Organization::factory()->create(['name' => 'Jurivex AI Platform']);
        $this->superadmin   = User::factory()->for($platformOrg)->create();
        $this->superadmin->assignRole('superadmin');

        $clientOrg          = Organization::factory()->create();
        $this->regularAdmin = User::factory()->for($clientOrg)->create();
        $this->regularAdmin->assignRole('admin');
    }

    public function test_non_superadmin_cannot_access_superadmin_routes(): void
    {
        $this->actingAs($this->regularAdmin)
             ->getJson('/api/v1/superadmin/organizations')
             ->assertStatus(403)
             ->assertJson(['success' => false, 'message' => 'Unauthorized.']);
    }

    public function test_unauthenticated_cannot_access_superadmin_routes(): void
    {
        $this->getJson('/api/v1/superadmin/organizations')
             ->assertStatus(401);
    }

    public function test_superadmin_can_list_organizations(): void
    {
        Organization::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)
             ->getJson('/api/v1/superadmin/organizations');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'name', 'slug', 'member_count', 'document_count', 'flag_count', 'created_at'],
                     ],
                 ]);

        $this->assertGreaterThanOrEqual(4, count($response->json('data')));
    }

    public function test_superadmin_can_create_organization(): void
    {
        $this->actingAs($this->superadmin)
             ->postJson('/api/v1/superadmin/organizations', ['name' => 'New Law Firm'])
             ->assertStatus(201)
             ->assertJson([
                 'success' => true,
                 'data'    => ['name' => 'New Law Firm'],
             ]);

        $this->assertDatabaseHas('organizations', ['name' => 'New Law Firm']);
    }

    public function test_superadmin_can_generate_invitation_code(): void
    {
        $org = Organization::factory()->create();

        $response = $this->actingAs($this->superadmin)
             ->postJson("/api/v1/superadmin/organizations/{$org->id}/invitation-codes", [
                 'role' => 'manager',
             ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['role' => 'manager', 'is_used' => false],
                 ])
                 ->assertJsonStructure(['data' => ['id', 'code', 'role', 'is_used', 'expires_at', 'created_at']]);

        $this->assertDatabaseHas('invitation_codes', [
            'organization_id' => $org->id,
            'role'            => 'manager',
        ]);
    }

    public function test_superadmin_can_list_invitation_codes(): void
    {
        $org = Organization::factory()->create();
        InvitationCode::factory()->for($org)->count(3)->create();

        $response = $this->actingAs($this->superadmin)
             ->getJson("/api/v1/superadmin/organizations/{$org->id}/invitation-codes");

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(3, 'data');
    }

    public function test_generate_code_validates_role(): void
    {
        $org = Organization::factory()->create();

        $this->actingAs($this->superadmin)
             ->postJson("/api/v1/superadmin/organizations/{$org->id}/invitation-codes", [
                 'role' => 'owner',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['role']);
    }
}
