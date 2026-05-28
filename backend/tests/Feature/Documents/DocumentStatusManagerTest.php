<?php

namespace Tests\Feature\Documents;

use App\Exceptions\Documents\InvalidDocumentTransitionException;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentStatusManagerTest extends TestCase
{
    use RefreshDatabase;

    private DocumentStatusManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->manager = new DocumentStatusManager();
    }

    // ── OCR flow ──────────────────────────────────────────────────────────────

    public function test_transitions_pending_to_ocr_processing(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->manager->transition($document, Document::STATUS_OCR_PROCESSING);

        $this->assertEquals(Document::STATUS_OCR_PROCESSING, $document->fresh()->status);
    }

    public function test_transitions_ocr_processing_to_ocr_completed(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_OCR_PROCESSING]);

        $this->manager->transition($document, Document::STATUS_OCR_COMPLETED);

        $this->assertEquals(Document::STATUS_OCR_COMPLETED, $document->fresh()->status);
    }

    public function test_transitions_ocr_processing_to_failed(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_OCR_PROCESSING]);

        $this->manager->transition($document, Document::STATUS_FAILED);

        $this->assertEquals(Document::STATUS_FAILED, $document->fresh()->status);
    }

    public function test_throws_on_invalid_transition_ocr_completed_to_analyzed(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_OCR_COMPLETED]);

        $this->expectException(InvalidDocumentTransitionException::class);

        $this->manager->transition($document, Document::STATUS_ANALYZED);
    }

    public function test_transitions_failed_to_ocr_processing_for_retry(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_FAILED]);

        $this->manager->transition($document, Document::STATUS_OCR_PROCESSING);

        $this->assertEquals(Document::STATUS_OCR_PROCESSING, $document->fresh()->status);
    }

    // ── Invalid transitions ───────────────────────────────────────────────────

    public function test_throws_on_invalid_transition_pending_to_analyzed(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->expectException(InvalidDocumentTransitionException::class);
        $this->expectExceptionMessage("Cannot transition document from 'pending' to 'analyzed'");

        $this->manager->transition($document, Document::STATUS_ANALYZED);
    }

    public function test_throws_on_invalid_transition_pending_to_ocr_completed(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->expectException(InvalidDocumentTransitionException::class);

        $this->manager->transition($document, Document::STATUS_OCR_COMPLETED);
    }

    public function test_throws_on_invalid_transition_analyzed_to_pending(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);

        $this->expectException(InvalidDocumentTransitionException::class);
        $this->expectExceptionMessage("Cannot transition document from 'analyzed' to 'pending'");

        $this->manager->transition($document, Document::STATUS_PENDING);
    }

    public function test_throws_on_invalid_transition_analyzed_to_ocr_processing(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_ANALYZED]);

        $this->expectException(InvalidDocumentTransitionException::class);

        $this->manager->transition($document, Document::STATUS_OCR_PROCESSING);
    }

    // ── AI analysis flow ──────────────────────────────────────────────────────────

    public function test_transitions_ocr_completed_to_ai_processing(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_OCR_COMPLETED]);

        $this->manager->transition($document, Document::STATUS_AI_PROCESSING);

        $this->assertEquals(Document::STATUS_AI_PROCESSING, $document->fresh()->status);
    }

    public function test_transitions_ai_processing_to_analyzed(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_AI_PROCESSING]);

        $this->manager->transition($document, Document::STATUS_ANALYZED);

        $this->assertEquals(Document::STATUS_ANALYZED, $document->fresh()->status);
    }

    public function test_transitions_ai_processing_to_failed(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_AI_PROCESSING]);

        $this->manager->transition($document, Document::STATUS_FAILED);

        $this->assertEquals(Document::STATUS_FAILED, $document->fresh()->status);
    }

    public function test_transitions_failed_to_ai_processing_for_retry(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_FAILED]);

        $this->manager->transition($document, Document::STATUS_AI_PROCESSING);

        $this->assertEquals(Document::STATUS_AI_PROCESSING, $document->fresh()->status);
    }

    public function test_throws_on_invalid_transition_pending_to_ai_processing(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->expectException(InvalidDocumentTransitionException::class);

        $this->manager->transition($document, Document::STATUS_AI_PROCESSING);
    }

    // ── canTransition ─────────────────────────────────────────────────────────

    public function test_can_transition_returns_true_for_valid_transition(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->assertTrue($this->manager->canTransition($document, Document::STATUS_OCR_PROCESSING));
    }

    public function test_can_transition_returns_false_for_invalid_transition(): void
    {
        $document = Document::factory()->create(['status' => Document::STATUS_PENDING]);

        $this->assertFalse($this->manager->canTransition($document, Document::STATUS_ANALYZED));
    }
}
