<?php

namespace App\Modules\AI\Providers;

use App\Modules\AI\Pipelines\DocumentAnalysisPipeline;
use App\Modules\AI\Prompts\Contracts\PromptLoaderContract;
use App\Modules\AI\Prompts\PromptLoader;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PromptLoaderContract::class, PromptLoader::class);
        $this->app->singleton(DocumentAnalysisPipeline::class);
    }
}
