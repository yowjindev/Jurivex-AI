<?php
namespace App\Modules\Documents\Repositories;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Repositories\Contracts\IDocumentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentRepository implements IDocumentRepository
{
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::where('organization_id', $user->organization_id);

        if ($user->hasRole('staff')) {
            $query->where('uploaded_by', $user->id);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id, string $organizationId): ?Document
    {
        return Document::where('id', $id)
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
