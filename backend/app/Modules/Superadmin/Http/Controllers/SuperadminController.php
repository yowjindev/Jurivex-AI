<?php

namespace App\Modules\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Organizations\Models\InvitationCode;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Repositories\Contracts\IOrganizationRepository;
use App\Modules\Superadmin\Http\Requests\CreateOrganizationRequest;
use App\Modules\Superadmin\Http\Requests\GenerateInvitationCodeRequest;
use Illuminate\Http\JsonResponse;

class SuperadminController extends Controller
{
    public function __construct(
        private readonly IOrganizationRepository $organizationRepository,
    ) {}

    public function index(): JsonResponse
    {
        $orgs = Organization::withCount(['users', 'documents', 'complianceFlags'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $orgs->map(fn ($org) => [
                'id'             => $org->id,
                'name'           => $org->name,
                'slug'           => $org->slug,
                'member_count'   => $org->users_count,
                'document_count' => $org->documents_count,
                'flag_count'     => $org->compliance_flags_count,
                'created_at'     => $org->created_at,
            ]),
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $org = $this->organizationRepository->createWithSlug($request->string('name'));

        return response()->json([
            'success' => true,
            'data'    => ['id' => $org->id, 'name' => $org->name, 'slug' => $org->slug],
            'message' => 'Organization created.',
            'meta'    => [],
        ], 201);
    }

    public function listInvitationCodes(string $org): JsonResponse
    {
        $organization = Organization::findOrFail($org);
        $codes        = InvitationCode::where('organization_id', $organization->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $codes->map(fn ($code) => [
                'id'         => $code->id,
                'code'       => $code->code,
                'role'       => $code->role,
                'is_used'    => $code->isUsed(),
                'used_at'    => $code->used_at,
                'expires_at' => $code->expires_at,
                'created_at' => $code->created_at,
            ]),
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function generateInvitationCode(GenerateInvitationCodeRequest $request, string $org): JsonResponse
    {
        $organization = Organization::findOrFail($org);

        $code = InvitationCode::create([
            'organization_id' => $organization->id,
            'code'            => strtoupper(str()->random(8)),
            'role'            => $request->string('role'),
            'expires_at'      => $request->input('expires_at'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $code->id,
                'code'       => $code->code,
                'role'       => $code->role,
                'is_used'    => false,
                'used_at'    => null,
                'expires_at' => $code->expires_at,
                'created_at' => $code->created_at,
            ],
            'message' => 'Invitation code generated.',
            'meta'    => [],
        ], 201);
    }
}
