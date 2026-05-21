<?php

namespace App\Modules\Organizations\Providers;

use App\Modules\Organizations\Repositories\Contracts\IOrganizationRepository;
use App\Modules\Organizations\Repositories\OrganizationRepository;
use Illuminate\Support\ServiceProvider;

class OrganizationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IOrganizationRepository::class, OrganizationRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
