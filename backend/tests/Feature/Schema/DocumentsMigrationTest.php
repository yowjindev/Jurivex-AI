<?php
namespace Tests\Feature\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('documents'));
        $this->assertTrue(Schema::hasColumns('documents', [
            'id', 'organization_id', 'uploaded_by', 'title',
            'original_filename', 'mime_type', 'file_size', 's3_path',
            'status', 'category', 'tags', 'created_at', 'updated_at', 'deleted_at',
        ]));
        $this->assertEquals('uuid', Schema::getColumnType('documents', 'id'));
        $this->assertEquals('uuid', Schema::getColumnType('documents', 'organization_id'));
        $this->assertEquals('uuid', Schema::getColumnType('documents', 'uploaded_by'));
    }

    public function test_document_analyses_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('document_analyses'));
        $this->assertTrue(Schema::hasColumns('document_analyses', [
            'id', 'document_id', 'summary', 'key_points',
            'risk_score', 'ai_model', 'analyzed_at', 'created_at', 'updated_at',
        ]));
        $this->assertEquals('uuid', Schema::getColumnType('document_analyses', 'id'));
        $this->assertEquals('uuid', Schema::getColumnType('document_analyses', 'document_id'));
    }

    public function test_compliance_flags_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('compliance_flags'));
        $this->assertTrue(Schema::hasColumns('compliance_flags', [
            'id', 'organization_id', 'document_id', 'type', 'severity',
            'title', 'description', 'due_date', 'is_resolved', 'created_at', 'updated_at',
        ]));
        $this->assertEquals('uuid', Schema::getColumnType('compliance_flags', 'id'));
        $this->assertEquals('uuid', Schema::getColumnType('compliance_flags', 'organization_id'));
    }
}
