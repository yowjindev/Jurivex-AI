<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Organizations\Models\InvitationCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(RegisterDTO $dto): User
    {
        $invitation = InvitationCode::where('code', $dto->invitationCode)->first();

        if (! $invitation || ! $invitation->isValid()) {
            throw ValidationException::withMessages([
                'invitation_code' => ['Invalid or expired invitation code.'],
            ]);
        }

        return DB::transaction(function () use ($dto, $invitation) {
            $user = User::create([
                'organization_id' => $invitation->organization_id,
                'name'            => $dto->name,
                'email'           => $dto->email,
                'password'        => Hash::make($dto->password),
            ]);

            $user->assignRole($invitation->role);

            $invitation->update([
                'used_by' => $user->id,
                'used_at' => now(),
            ]);

            AuditLog::create([
                'organization_id' => $invitation->organization_id,
                'user_id'         => $user->id,
                'action'          => 'user.registered',
                'new_values'      => ['email' => $user->email, 'role' => $invitation->role],
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
