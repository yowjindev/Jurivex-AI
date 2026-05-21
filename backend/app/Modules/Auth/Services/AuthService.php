<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Organizations\Repositories\Contracts\IOrganizationRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly IOrganizationRepository $organizationRepository,
    ) {}

    public function register(RegisterDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $org = $this->organizationRepository->createWithSlug($dto->organizationName);

            $user = User::create([
                'organization_id' => $org->id,
                'name'            => $dto->name,
                'email'           => $dto->email,
                'password'        => $dto->password,
            ]);

            $user->assignRole('admin');

            AuditLog::create([
                'organization_id' => $org->id,
                'user_id'         => $user->id,
                'action'          => 'user.registered',
                'new_values'      => ['email' => $user->email, 'role' => 'admin'],
            ]);

            return $user;
        });
    }

    public function login(LoginDTO $dto): User
    {
        if (! Auth::attempt(['email' => $dto->email, 'password' => $dto->password])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'user.logged_in',
        ]);

        return $user;
    }

    public function logout(): void
    {
        /** @var User $user */
        $user = Auth::user();

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'user.logged_out',
        ]);

        Auth::guard('web')->logout();
    }
}
