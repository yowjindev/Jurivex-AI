<?php
namespace App\Modules\Documents\Services;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Documents\DTOs\UpdateDocumentDTO;
use App\Modules\Documents\DTOs\UploadDocumentDTO;
use App\Modules\Documents\Jobs\ProcessDocumentJob;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Repositories\Contracts\IDocumentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function __construct(private readonly IDocumentRepository $documentRepository) {}

    public function list(User $user): LengthAwarePaginator
    {
        return $this->documentRepository->listForUser($user);
    }

    public function show(string $id, User $user): Document
    {
        $document = $this->documentRepository->findById($id, $user->organization_id);

        abort_if($document === null, 404, 'Document not found.');

        if ($user->hasRole('staff') && $document->uploaded_by !== $user->id) {
            abort(403, 'Access denied.');
        }

        return $document;
    }

    public function upload(UploadedFile $file, User $user, ?string $category = null): Document
    {
        $uuid     = Str::uuid()->toString();
        $filename = $file->getClientOriginalName();
        $path     = "org/{$user->organization_id}/documents/{$uuid}/{$filename}";

        Storage::disk('s3')->put($path, $file->get());

        $dto = new UploadDocumentDTO(
            title:            pathinfo($filename, PATHINFO_FILENAME),
            originalFilename: $filename,
            mimeType:         $file->getMimeType() ?? $file->getClientMimeType(),
            fileSize:         $file->getSize(),
            s3Path:           $path,
            organizationId:   $user->organization_id,
            uploadedBy:       $user->id,
            category:         $category,
        );

        $document = $this->documentRepository->create([
            'organization_id'   => $dto->organizationId,
            'uploaded_by'       => $dto->uploadedBy,
            'title'             => $dto->title,
            'original_filename' => $dto->originalFilename,
            'mime_type'         => $dto->mimeType,
            'file_size'         => $dto->fileSize,
            's3_path'           => $dto->s3Path,
            'status'            => Document::STATUS_PENDING,
            'category'          => $dto->category,
        ]);

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'document.uploaded',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
            'new_values'      => ['title' => $document->title, 'filename' => $document->original_filename],
        ]);

        ProcessDocumentJob::dispatch($document);

        return $document;
    }

    public function update(Document $document, UpdateDocumentDTO $dto): Document
    {
        $data = array_filter(
            ['title' => $dto->title, 'category' => $dto->category, 'tags' => $dto->tags],
            fn ($v) => $v !== null
        );

        return $this->documentRepository->update($document, $data);
    }

    public function delete(Document $document, User $user): void
    {
        abort_if(
            ! $user->hasAnyRole(['admin', 'manager']),
            403,
            'Only admins and managers can delete documents.'
        );

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'document.deleted',
            'auditable_type'  => 'document',
            'auditable_id'    => $document->id,
            'new_values'      => ['title' => $document->title],
        ]);

        $this->documentRepository->delete($document);
    }

    public function downloadUrl(Document $document): ?string
    {
        try {
            return Storage::disk('s3')->temporaryUrl($document->s3_path, now()->addMinutes(60));
        } catch (\RuntimeException) {
            return null;
        }
    }
}
