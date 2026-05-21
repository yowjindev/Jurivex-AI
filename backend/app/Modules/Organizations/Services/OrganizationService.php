<?php

namespace App\Modules\Organizations\Services;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Repositories\Contracts\IOrganizationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class OrganizationService
{
    public function __construct(
        private readonly IOrganizationRepository $organizationRepository,
    ) {}

    public function getOrganization(string $organizationId): ?Organization
    {
        return $this->organizationRepository->findById($organizationId);
    }

    public function getMembers(string $organizationId): Collection
    {
        return $this->organizationRepository->getMembersByOrganizationId($organizationId);
    }

    public function inviteMember(string $organizationId, string $name, string $email, string $role): User
    {
        $user = User::create([
            'organization_id' => $organizationId,
            'name'            => $name,
            'email'           => $email,
            'password'        => Hash::make(str()->random(24)),
        ]);

        $user->assignRole($role);

        return $user;
    }
}
