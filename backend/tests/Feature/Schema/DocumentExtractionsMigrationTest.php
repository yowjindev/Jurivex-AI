<?php

namespace Tests\Feature\Schema;

use App\Modules\AI\OCR\Models\DocumentExtraction;
use App\Modules\Documents\Models\Document;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentExtractionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_document_extractions_table_exists(): void
    {
        $this->assertTrue(
            \Schema::hasTable('document_extractions'),
            'document_extractions table should exist'
        );
    }

    public function test_document_extractions_table_has_expected_columns(): void
    {
        $columns = \Schema::getColumnListing('document_extractions');

        foreach (['id', 'document_id', 'extracted_text', 'page_count', 'word_count',
                  'char_count', 'ocr_engine', 'extractor_type', 'confidence',
                  'extracted_at', 'created_at', 'updated_at'] as $column) {
            $this->assertContains($column, $columns, "Column {$column} should exist");
        }
    }

    public function test_document_has_extraction_relationship(): void
    {
        $document = Document::factory()->create();

        $extraction = DocumentExtraction::create([
            'document_id'    => $document->id,
            'extracted_text' => 'Sample text',
            'page_count'     => 1,
            'word_count'     => 2,
            'char_count'     => 11,
            'ocr_engine'     => 'tesseract',
            'extractor_type' => 'pdf_text',
            'confidence'     => 1.0,
            'extracted_at'   => now(),
        ]);

        $this->assertInstanceOf(DocumentExtraction::class, $document->extraction);
        $this->assertEquals($extraction->id, $document->extraction->id);
    }

    public function test_document_extraction_factory_creates_valid_record(): void
    {
        $extraction = DocumentExtraction::factory()->create();

        $this->assertNotNull($extraction->extracted_text);
        $this->assertGreaterThan(0, $extraction->page_count);
        $this->assertContains($extraction->extractor_type, ['pdf_text', 'pdf_ocr', 'image_ocr']);
    }
}
