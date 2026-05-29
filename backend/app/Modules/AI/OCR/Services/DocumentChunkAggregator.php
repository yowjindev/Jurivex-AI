<?php

namespace App\Modules\AI\OCR\Services;

use App\Modules\AI\OCR\DTOs\ExtractionResult;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Models\DocumentExtraction;
use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentStatusManager;
use Illuminate\Support\Facades\DB;

class DocumentChunkAggregator
{
    public function __construct(
        private readonly DocumentStatusManager $statusManager,
        private readonly IDocumentExtractionRepository $extractionRepository,
    ) {}

    public function recordChunkSuccess(Document $document, DocumentExtractionChunk $chunk, ExtractionResult $result): ?ExtractionResult
    {
        $dispatchResult = null;

        DB::transaction(function () use ($document, $chunk, $result, &$dispatchResult): void {
            $lockedDocument = Document::query()->lockForUpdate()->find($document->id);

            if ($lockedDocument === null || $lockedDocument->status === Document::STATUS_FAILED) {
                return;
            }

            $lockedChunk = DocumentExtractionChunk::query()->lockForUpdate()->find($chunk->id);

            if ($lockedChunk === null || $lockedChunk->status === DocumentExtractionChunk::STATUS_COMPLETED) {
                return;
            }

            $lockedChunk->update([
                'status'         => DocumentExtractionChunk::STATUS_COMPLETED,
                'extracted_text' => $result->text,
                'word_count'     => $result->wordCount,
                'char_count'     => $result->charCount,
                'extractor_type' => $result->extractorType,
                'confidence'     => $result->confidence,
                'processed_at'   => now(),
                'error_message'  => null,
            ]);

            if ($this->hasFailedChunk($lockedDocument->id)) {
                return;
            }

            if (! $this->allChunksCompleted($lockedDocument->id)) {
                return;
            }

            $dispatchResult = $this->finalizeDocumentExtraction($lockedDocument);
        });

        if ($dispatchResult !== null) {
            OCRCompleted::dispatch($document->fresh(['extraction']), $dispatchResult);
        }

        return $dispatchResult;
    }

    public function recordChunkFailure(Document $document, DocumentExtractionChunk $chunk, string $reason): void
    {
        DB::transaction(function () use ($document, $chunk, $reason): void {
            $lockedChunk = DocumentExtractionChunk::query()->lockForUpdate()->find($chunk->id);

            if ($lockedChunk !== null && $lockedChunk->status !== DocumentExtractionChunk::STATUS_FAILED) {
                $lockedChunk->update([
                    'status'        => DocumentExtractionChunk::STATUS_FAILED,
                    'error_message' => $reason,
                    'processed_at'  => now(),
                ]);
            }

            $lockedDocument = Document::query()->lockForUpdate()->find($document->id);

            if ($lockedDocument !== null && $lockedDocument->status !== Document::STATUS_FAILED) {
                $this->statusManager->transition($lockedDocument, Document::STATUS_FAILED);
            }
        });
    }

    public function progress(Document $document): array
    {
        $chunks = DocumentExtractionChunk::query()->where('document_id', $document->id)->get();

        $total = $chunks->count();
        $completed = $chunks->where('status', DocumentExtractionChunk::STATUS_COMPLETED)->count();
        $failed = $chunks->where('status', DocumentExtractionChunk::STATUS_FAILED)->count();
        $pending = $chunks->where('status', DocumentExtractionChunk::STATUS_PENDING)->count();
        $processing = $chunks->where('status', DocumentExtractionChunk::STATUS_PROCESSING)->count();

        return [
            'total_chunks'       => $total,
            'completed_chunks'   => $completed,
            'failed_chunks'      => $failed,
            'pending_chunks'     => $pending,
            'processing_chunks'   => $processing,
            'progress_percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    private function allChunksCompleted(string $documentId): bool
    {
        return DocumentExtractionChunk::query()
            ->where('document_id', $documentId)
            ->where('status', '!=', DocumentExtractionChunk::STATUS_COMPLETED)
            ->doesntExist();
    }

    private function hasFailedChunk(string $documentId): bool
    {
        return DocumentExtractionChunk::query()
            ->where('document_id', $documentId)
            ->where('status', DocumentExtractionChunk::STATUS_FAILED)
            ->exists();
    }

    private function finalizeDocumentExtraction(Document $document): ExtractionResult
    {
        $chunks = DocumentExtractionChunk::query()
            ->where('document_id', $document->id)
            ->orderBy('chunk_index')
            ->get();

        $text = $chunks->pluck('extracted_text')->filter()->implode("\n\n");
        $pageCount = $chunks->sum(fn (DocumentExtractionChunk $chunk) => max(1, $chunk->page_end - $chunk->page_start + 1));
        $wordCount = str_word_count($text);
        $charCount = strlen($text);
        $confidence = $chunks->avg('confidence') ?? 0.0;
        $extractorType = $chunks->contains(fn (DocumentExtractionChunk $chunk) => str_contains((string) $chunk->extractor_type, 'ocr'))
            ? 'pdf_ocr'
            : 'pdf_text';

        $result = new ExtractionResult(
            text: $text,
            pageCount: $pageCount,
            wordCount: $wordCount,
            charCount: $charCount,
            extractorType: $extractorType,
            confidence: (float) $confidence,
        );

        $this->extractionRepository->upsert($document->id, $result);
        $this->statusManager->transition($document, Document::STATUS_OCR_COMPLETED);

        return $result;
    }
}
