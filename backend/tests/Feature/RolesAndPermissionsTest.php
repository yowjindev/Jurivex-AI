<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_role_exists(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'admin', 'guard_name' => 'web']);
    }

    public function test_manager_role_exists(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'manager', 'guard_name' => 'web']);
    }

    public function test_staff_role_exists(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'staff', 'guard_name' => 'web']);
    }

    public function test_exactly_three_roles_exist(): void
    {
        $this->assertEquals(3, Role::count());
    }

    public function test_seeder_is_idempotent(): void
    {
        // Run a second time — should not throw or duplicate
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->assertEquals(3, Role::count());
    }
}
