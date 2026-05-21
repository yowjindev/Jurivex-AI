<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizations_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('organizations'));
        $this->assertTrue(Schema::hasColumns('organizations', [
            'id', 'name', 'slug', 'created_at', 'updated_at',
        ]));
        // id is UUID
        $this->assertEquals('uuid', Schema::getColumnType('organizations', 'id'));
    }

    public function test_users_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasColumns('users', [
            'id', 'organization_id', 'name', 'email', 'password',
            'email_verified_at', 'remember_token', 'created_at', 'updated_at',
        ]));
        $this->assertEquals('uuid', Schema::getColumnType('users', 'id'));
        $this->assertEquals('uuid', Schema::getColumnType('users', 'organization_id'));
    }

    public function test_audit_logs_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumns('audit_logs', [
            'id', 'organization_id', 'user_id', 'action', 'auditable_type',
            'auditable_id', 'old_values', 'new_values', 'created_at',
        ]));
        $this->assertEquals('uuid', Schema::getColumnType('audit_logs', 'id'));
    }
}
