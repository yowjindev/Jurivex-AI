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
        if (class_exists(\App\Modules\AI\Prompts\PromptLoader::class)
            && interface_exists(\App\Modules\AI\Prompts\Contracts\PromptLoaderContract::class)) {
            $this->app->bind(PromptLoaderContract::class, PromptLoader::class);
        }
        $this->app->singleton(DocumentAnalysisPipeline::class);
    }
}
