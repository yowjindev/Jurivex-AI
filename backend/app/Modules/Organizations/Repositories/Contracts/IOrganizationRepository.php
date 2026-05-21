<?php

namespace App\Modules\Organizations\Repositories\Contracts;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

interface IOrganizationRepository
{
    public function findById(string $id): ?Organization;

    public function getMembersByOrganizationId(string $organizationId): Collection;

    public function createWithSlug(string $name): Organization;
}
