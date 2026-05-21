<?php
namespace App\Modules\Documents\Repositories\Contracts;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IDocumentRepository
{
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator;
    public function findById(string $id, string $organizationId): ?Document;
    public function create(array $data): Document;
    public function update(Document $document, array $data): Document;
    public function delete(Document $document): void;
}
