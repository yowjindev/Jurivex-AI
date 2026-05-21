<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Organizations\Http\Requests\InviteRequest;
use App\Modules\Organizations\Http\Resources\OrganizationResource;
use App\Modules\Organizations\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(private readonly OrganizationService $organizationService) {}

    public function show(Request $request): JsonResponse
    {
        $org = $this->organizationService->getOrganization(
            $request->user()->organization_id
        );

        return response()->json([
            'success' => true,
            'data'    => new OrganizationResource($org),
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function members(Request $request): JsonResponse
    {
        $members = $this->organizationService->getMembers(
            $request->user()->organization_id
        );

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($members),
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function invite(InviteRequest $request): JsonResponse
    {
        $user = $this->organizationService->inviteMember(
            organizationId: $request->user()->organization_id,
            name:           $request->string('name'),
            email:          $request->string('email'),
            role:           $request->string('role'),
        );

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
            'message' => 'Member invited successfully.',
            'meta'    => [],
        ], 201);
    }
}
