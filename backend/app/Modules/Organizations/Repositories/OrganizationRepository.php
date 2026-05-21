<?php

namespace App\Modules\Organizations\Repositories;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Repositories\Contracts\IOrganizationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class OrganizationRepository implements IOrganizationRepository
{
    public function findById(string $id): ?Organization
    {
        return Organization::find($id);
    }

    public function getMembersByOrganizationId(string $organizationId): Collection
    {
        return User::where('organization_id', $organizationId)->get();
    }

    public function createWithSlug(string $name): Organization
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i    = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return Organization::create(['name' => $name, 'slug' => $slug]);
    }
}
