<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Organizations\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelBehaviorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_organization_uses_uuid_primary_key(): void
    {
        $org = Organization::factory()->create();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $org->id
        );
    }

    public function test_user_uses_uuid_primary_key(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $user->id
        );
    }

    public function test_user_belongs_to_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $this->assertEquals($org->id, $user->organization_id);
        $this->assertInstanceOf(Organization::class, $user->organization);
    }

    public function test_organization_has_many_users(): void
    {
        $org = Organization::factory()->create();
        User::factory()->for($org)->count(3)->create();
        $this->assertCount(3, $org->fresh()->users);
    }

    public function test_user_can_be_assigned_admin_role(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $user->assignRole('admin');
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_audit_log_is_created_with_uuid(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $log = AuditLog::create([
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'action'          => 'user.registered',
            'new_values'      => ['email' => $user->email],
        ]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $log->id
        );
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.registered']);
    }

    public function test_audit_log_has_no_updated_at(): void
    {
        $org = Organization::factory()->create();
        $log = AuditLog::create([
            'organization_id' => $org->id,
            'action'          => 'test.action',
        ]);
        $this->assertFalse($log->usesTimestamps() && array_key_exists('updated_at', $log->getDates()));
    }
}
