<?php

namespace Tests\Feature\Organizations;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // --- GET /api/v1/organization ---

    public function test_authenticated_user_can_view_their_organization(): void
    {
        $org  = Organization::factory()->create(['name' => 'Acme Corp', 'slug' => 'acme-corp']);
        $user = User::factory()->for($org)->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->getJson('/api/v1/organization');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [
                         'id'   => $org->id,
                         'name' => 'Acme Corp',
                         'slug' => 'acme-corp',
                     ],
                 ]);
    }

    public function test_unauthenticated_request_to_organization_returns_401(): void
    {
        $response = $this->getJson('/api/v1/organization');

        $response->assertStatus(401);
    }

    // --- GET /api/v1/organization/members ---

    public function test_authenticated_user_can_list_members(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        User::factory()->for($org)->count(2)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/organization/members');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(3, 'data');
    }

    public function test_members_endpoint_only_returns_own_organization_members(): void
    {
        $org1  = Organization::factory()->create();
        $org2  = Organization::factory()->create();
        $user1 = User::factory()->for($org1)->create();
        User::factory()->for($org2)->count(5)->create(); // different org, should not appear

        $response = $this->actingAs($user1)->getJson('/api/v1/organization/members');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_request_to_members_returns_401(): void
    {
        $response = $this->getJson('/api/v1/organization/members');

        $response->assertStatus(401);
    }

    // --- POST /api/v1/organization/invitations ---

    public function test_admin_can_invite_a_new_member(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/organization/invitations', [
            'name'  => 'New Member',
            'email' => 'newmember@example.com',
            'role'  => 'staff',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Member invited successfully.',
                     'data'    => [
                         'email' => 'newmember@example.com',
                         'roles' => ['staff'],
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'email'           => 'newmember@example.com',
            'organization_id' => $org->id,
        ]);
    }

    public function test_non_admin_cannot_invite_members(): void
    {
        $org     = Organization::factory()->create();
        $manager = User::factory()->for($org)->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->postJson('/api/v1/organization/invitations', [
            'name'  => 'New Member',
            'email' => 'newmember@example.com',
            'role'  => 'staff',
        ]);

        $response->assertStatus(403);
    }

    public function test_invite_requires_name_email_and_role(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/organization/invitations', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'role']);
    }

    public function test_invite_role_must_be_manager_or_staff(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/organization/invitations', [
            'name'  => 'New Member',
            'email' => 'new@example.com',
            'role'  => 'admin',  // cannot invite as admin
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['role']);
    }

    public function test_invite_requires_unique_email(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create(['email' => 'existing@example.com']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/organization/invitations', [
            'name'  => 'Duplicate',
            'email' => 'existing@example.com',
            'role'  => 'staff',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_request_to_invitations_returns_401(): void
    {
        $response = $this->postJson('/api/v1/organization/invitations', [
            'name'  => 'New Member',
            'email' => 'new@example.com',
            'role'  => 'staff',
        ]);

        $response->assertStatus(401);
    }
}
