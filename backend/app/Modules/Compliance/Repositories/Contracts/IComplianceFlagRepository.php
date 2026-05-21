<?php

namespace App\Modules\Compliance\Repositories\Contracts;

use App\Modules\Compliance\Models\ComplianceFlag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IComplianceFlagRepository
{
    public function listByOrganization(string $organizationId, int $perPage = 15): LengthAwarePaginator;
    public function findById(string $id, string $organizationId): ?ComplianceFlag;
    public function resolve(ComplianceFlag $flag): ComplianceFlag;
}
