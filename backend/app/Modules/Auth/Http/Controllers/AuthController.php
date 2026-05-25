<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Organizations\Models\InvitationCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register(new RegisterDTO(
            invitationCode: $request->string('invitation_code'),
            name:           $request->string('name'),
            email:          $request->string('email'),
            password:       $request->string('password'),
        ));

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
            'message' => 'Registered successfully.',
            'meta'    => [],
        ], 201);
    }

    public function lookupInvitation(string $code): JsonResponse
    {
        $invitation = InvitationCode::with('organization')
            ->where('code', strtoupper($code))
            ->first();

        if (! $invitation || ! $invitation->isValid()) {
            throw new NotFoundException('Invalid or expired invitation code.');
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'organization_name' => $invitation->organization->name,
                'role'              => $invitation->role,
            ],
            'message' => 'OK',
            'meta'    => [],
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login(new LoginDTO(
            email:    $request->string('email'),
            password: $request->string('password'),
        ));

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
            'message' => 'Logged in successfully.',
            'meta'    => [],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'Logged out successfully.',
            'meta'    => [],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($request->user()),
            'message' => 'OK',
            'meta'    => [],
        ]);
    }
}
