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
    }

    public function test_document_analyses_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('document_analyses'));
        $this->assertTrue(Schema::hasColumns('document_analyses', [
            'id', 'document_id', 'summary', 'key_points',
            'risk_score', 'ai_model', 'analyzed_at', 'created_at', 'updated_at',
        ]));
    }
}
