<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register(new RegisterDTO(
            organizationName: $request->string('organization_name'),
            name:             $request->string('name'),
            email:            $request->string('email'),
            password:         $request->string('password'),
        ));

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
            'message' => 'Registered successfully.',
            'meta'    => [],
        ], 201);
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
