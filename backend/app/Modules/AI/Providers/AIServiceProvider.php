<?php

namespace App\Modules\AI\Providers;

use App\Modules\AI\Analysis\Repositories\Contracts\IDocumentAnalysisRepository;
use App\Modules\AI\Analysis\Repositories\DocumentAnalysisRepository;
use App\Modules\AI\OCR\Events\OCRCompleted;
use App\Modules\AI\OCR\Events\OCRFailed;
use App\Modules\AI\OCR\Listeners\LogOCRActivity;
use App\Modules\AI\OCR\Parsers\ImageTextExtractor;
use App\Modules\AI\OCR\Parsers\PdfTextExtractor;
use App\Modules\AI\OCR\Repositories\Contracts\IDocumentExtractionRepository;
use App\Modules\AI\OCR\Repositories\DocumentExtractionRepository;
use App\Modules\AI\OCR\Services\OcrService;
use App\Modules\AI\Pipelines\DocumentAnalysisPipeline;
use App\Modules\AI\Prompts\Contracts\PromptLoaderContract;
use App\Modules\AI\Prompts\PromptLoader;
use App\Modules\AI\Analysis\Listeners\DispatchAIAnalysis;
use App\Modules\AI\Services\ClaudeClient;
use App\Modules\AI\Utilities\TextTruncator;
use App\Modules\Documents\Events\DocumentAnalysisCompleted;
use App\Modules\Documents\Listeners\LogDocumentAnalysisActivity;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PromptLoaderContract::class, PromptLoader::class);
        $this->app->singleton(DocumentAnalysisPipeline::class);

        $this->app->singleton(IDocumentExtractionRepository::class, DocumentExtractionRepository::class);
        $this->app->singleton(IDocumentAnalysisRepository::class, DocumentAnalysisRepository::class);

        $this->app->singleton(OcrService::class, function () {
            return new OcrService([
                new PdfTextExtractor(),
                new ImageTextExtractor(),
            ]);
        });

        $this->app->singleton(ClaudeClient::class, function () {
            $config = config('ai.claude');
            return new ClaudeClient(
                apiKey:    $config['api_key'] ?? '',
                model:     $config['model'] ?? 'claude-sonnet-4-6',
                maxTokens: $config['max_tokens'] ?? 4096,
            );
        });
        $this->app->singleton(TextTruncator::class);
    }

    public function boot(): void
    {
        Event::listen(OCRCompleted::class, [LogOCRActivity::class, 'handleCompleted']);
        Event::listen(OCRFailed::class, [LogOCRActivity::class, 'handleFailed']);

        // Analysis dispatch
        Event::listen(OCRCompleted::class, [DispatchAIAnalysis::class, 'handle']);

        // Analysis completion logging
        Event::listen(DocumentAnalysisCompleted::class, [LogDocumentAnalysisActivity::class, 'handle']);
    }
}
