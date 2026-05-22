<?php

namespace App\Modules\Compliance\Providers;

use App\Modules\Compliance\Events\ComplianceFlagGenerated;
use App\Modules\Compliance\Listeners\LogComplianceFlagActivity;
use App\Modules\Compliance\Repositories\ComplianceFlagRepository;
use App\Modules\Compliance\Repositories\Contracts\IComplianceFlagRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ComplianceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IComplianceFlagRepository::class, ComplianceFlagRepository::class);
    }

    public function boot(): void
    {
        Event::listen(ComplianceFlagGenerated::class, LogComplianceFlagActivity::class);
    }
}
