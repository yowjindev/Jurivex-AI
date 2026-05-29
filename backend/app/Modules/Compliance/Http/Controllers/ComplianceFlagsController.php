<?php
namespace App\Modules\Compliance\Http\Controllers;

use App\Modules\Compliance\Http\Requests\ResolveComplianceFlagRequest;
use App\Modules\Compliance\Http\Resources\ComplianceFlagResource;
use App\Modules\Compliance\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ComplianceFlagsController extends Controller
{
    public function __construct(private readonly ComplianceService $complianceService) {}

    public function index(Request $request): JsonResponse
    {
        $documentId = $request->query('document_id') ?: null;
        $paginated  = $this->complianceService->list($request->user(), $documentId);

        return response()->json([
            'success' => true,
            'data'    => ComplianceFlagResource::collection($paginated->items()),
            'message' => 'OK',
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    public function resolve(ResolveComplianceFlagRequest $request, string $id): JsonResponse
    {
        $flag = $this->complianceService->resolve($id, $request->user());

        return response()->json([
            'success' => true,
            'data'    => new ComplianceFlagResource($flag),
            'message' => 'Compliance flag resolved.',
            'meta'    => [],
        ]);
    }
}
