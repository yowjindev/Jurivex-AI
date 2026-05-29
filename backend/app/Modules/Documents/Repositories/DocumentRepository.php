<?php
namespace App\Modules\Documents\Repositories;

use App\Models\User;
use App\Modules\AI\OCR\Models\DocumentExtractionChunk;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Repositories\Contracts\IDocumentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentRepository implements IDocumentRepository
{
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::with('analysis')
            ->withCount([
                'chunks as ocr_chunks_total',
                'chunks as ocr_chunks_completed' => fn ($query) => $query->where('status', DocumentExtractionChunk::STATUS_COMPLETED),
                'chunks as ocr_chunks_failed' => fn ($query) => $query->where('status', DocumentExtractionChunk::STATUS_FAILED),
                'chunks as ocr_chunks_processing' => fn ($query) => $query->where('status', DocumentExtractionChunk::STATUS_PROCESSING),
                'chunks as ocr_chunks_pending' => fn ($query) => $query->where('status', DocumentExtractionChunk::STATUS_PENDING),
            ])
            ->where('organization_id', $user->organization_id);

        if ($user->hasRole('staff')) {
            $query->where('uploaded_by', $user->id);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id, string $organizationId): ?Document
    {
        return Document::with('analysis')
            ->where('id', $id)
            ->where('organization_id', $organizationId)
            ->first();
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function update(Document $document, array $data): Document
    {
        $document->update($data);
        return $document->fresh();
    }

    public function delete(Document $document): void
    {
        $document->delete();
    }
}
