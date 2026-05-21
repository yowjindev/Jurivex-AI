<?php

namespace App\Modules\Compliance\Providers;

use App\Modules\Compliance\Repositories\ComplianceFlagRepository;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use Illuminate\Support\ServiceProvider;

class ComplianceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IComplianceFlagRepository::class, ComplianceFlagRepository::class);
    }
}
