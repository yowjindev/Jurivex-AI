<?php

namespace App\Modules\Compliance\Services;

use App\Models\User;
use App\Modules\Auth\Models\AuditLog;
use App\Modules\Compliance\Models\ComplianceFlag;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplianceService
{
    public function __construct(private readonly IComplianceFlagRepository $repository) {}

    public function list(User $user): LengthAwarePaginator
    {
        return $this->repository->listByOrganization($user->organization_id);
    }

    public function resolve(string $id, User $user): ComplianceFlag
    {
        $flag = $this->repository->findById($id, $user->organization_id);

        abort_if($flag === null, 404, 'Compliance flag not found.');

        $resolved = $this->repository->resolve($flag);

        AuditLog::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'action'          => 'flag.resolved',
            'auditable_type'  => 'compliance_flag',
            'auditable_id'    => $flag->id,
            'new_values'      => ['title' => $flag->title],
            'metadata'        => ['severity' => $flag->severity, 'type' => $flag->type],
        ]);

        return $resolved;
    }
}
