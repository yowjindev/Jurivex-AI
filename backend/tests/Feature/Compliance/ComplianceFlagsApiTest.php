<?php
namespace Tests\Feature\Compliance;

use App\Models\User;
use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceFlagsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_flags(): void
    {
        $this->getJson('/api/v1/compliance/flags')->assertStatus(401);
    }

    public function test_admin_can_list_org_compliance_flags(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        ComplianceFlag::factory()->count(4)->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->getJson('/api/v1/compliance/flags')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data');
    }

    public function test_staff_can_list_compliance_flags(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        ComplianceFlag::factory()->count(2)->create(['organization_id' => $org->id]);

        $this->actingAs($staff)
            ->getJson('/api/v1/compliance/flags')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_flags_are_scoped_to_own_organization(): void
    {
        $org1  = Organization::factory()->create();
        $admin = User::factory()->for($org1)->create();
        $admin->assignRole('admin');

        $org2 = Organization::factory()->create();
        ComplianceFlag::factory()->count(3)->create(['organization_id' => $org2->id]);

        $this->actingAs($admin)
            ->getJson('/api/v1/compliance/flags')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_list_response_includes_pagination_meta(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson('/api/v1/compliance/flags')
            ->assertStatus(200)
            ->assertJsonStructure(['meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    public function test_flag_resource_has_expected_fields(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        ComplianceFlag::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->getJson('/api/v1/compliance/flags')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'type', 'severity', 'title', 'description', 'is_resolved', 'due_date']]]);
    }

    // ─── RESOLVE ──────────────────────────────────────────────────────────────

    public function test_admin_can_resolve_compliance_flag(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $flag = ComplianceFlag::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/compliance/flags/{$flag->id}/resolve")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_resolved', true);

        $this->assertDatabaseHas('compliance_flags', [
            'id'          => $flag->id,
            'is_resolved' => true,
        ]);
    }

    public function test_manager_can_resolve_compliance_flag(): void
    {
        $org     = Organization::factory()->create();
        $manager = User::factory()->for($org)->create();
        $manager->assignRole('manager');

        $flag = ComplianceFlag::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($manager)
            ->patchJson("/api/v1/compliance/flags/{$flag->id}/resolve")
            ->assertStatus(200)
            ->assertJsonPath('data.is_resolved', true);
    }

    public function test_staff_cannot_resolve_compliance_flag(): void
    {
        $org   = Organization::factory()->create();
        $staff = User::factory()->for($org)->create();
        $staff->assignRole('staff');

        $flag = ComplianceFlag::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($staff)
            ->patchJson("/api/v1/compliance/flags/{$flag->id}/resolve")
            ->assertStatus(403);
    }

    public function test_resolve_creates_audit_log(): void
    {
        $org   = Organization::factory()->create();
        $admin = User::factory()->for($org)->create();
        $admin->assignRole('admin');

        $flag = ComplianceFlag::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/compliance/flags/{$flag->id}/resolve");

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $org->id,
            'user_id'         => $admin->id,
            'action'          => 'flag.resolved',
        ]);
    }

    public function test_cannot_resolve_flag_from_another_org(): void
    {
        $org1  = Organization::factory()->create();
        $admin = User::factory()->for($org1)->create();
        $admin->assignRole('admin');

        $org2 = Organization::factory()->create();
        $flag = ComplianceFlag::factory()->create(['organization_id' => $org2->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/compliance/flags/{$flag->id}/resolve")
            ->assertStatus(404);
    }

    public function test_unauthenticated_cannot_resolve_flag(): void
    {
        $flag = ComplianceFlag::factory()->create();

        $this->patchJson("/api/v1/compliance/flags/{$flag->id}/resolve")->assertStatus(401);
    }
}
