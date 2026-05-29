<?php
namespace App\Modules\Documents\Http\Controllers;

use App\Modules\Documents\DTOs\UpdateDocumentDTO;
use App\Modules\Documents\Http\Requests\UpdateDocumentRequest;
use App\Modules\Documents\Http\Requests\UploadDocumentRequest;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Documents\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    public function index(Request $request): JsonResponse
    {
        $paginated = $this->documentService->list($request->user());

        return response()->json([
            'success' => true,
            'data'    => DocumentResource::collection($paginated->items()),
            'message' => 'OK',
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    public function store(UploadDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->upload(
            $request->file('file'),
            $request->user(),
            $request->input('category'),
        );

        return response()->json([
            'success' => true,
            'data'    => new DocumentResource($document),
            'message' => 'Document uploaded successfully.',
            'meta'    => [],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $document    = $this->documentService->show($id, $request->user());
        $downloadUrl = $this->documentService->downloadUrl($document);

        return response()->json([
            'success' => true,
            'data'    => array_merge(
                (new DocumentResource($document))->toArray($request),
                [
                    'download_url'   => $downloadUrl,
                    'failure_reason' => $this->documentService->latestFailureReason($document),
                ]
            ),
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function update(UpdateDocumentRequest $request, string $id): JsonResponse
    {
        $document = $this->documentService->show($id, $request->user());
        $updated  = $this->documentService->update($document, new UpdateDocumentDTO(
            title:    $request->input('title'),
            category: $request->input('category'),
            tags:     $request->input('tags'),
        ));

        return response()->json([
            'success' => true,
            'data'    => new DocumentResource($updated),
            'message' => 'Document updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $document = $this->documentService->show($id, $request->user());
        $this->documentService->delete($document, $request->user());

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'Document deleted successfully.',
            'meta'    => [],
        ]);
    }
}
