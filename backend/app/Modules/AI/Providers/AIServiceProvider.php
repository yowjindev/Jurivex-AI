<?php

namespace App\Modules\AI\Providers;

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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PromptLoaderContract::class, PromptLoader::class);
        $this->app->singleton(DocumentAnalysisPipeline::class);

        $this->app->singleton(IDocumentExtractionRepository::class, DocumentExtractionRepository::class);

        $this->app->singleton(OcrService::class, function () {
            return new OcrService([
                new PdfTextExtractor(),
                new ImageTextExtractor(),
            ]);
        });
    }

    public function boot(): void
    {
        Event::listen(OCRCompleted::class, [LogOCRActivity::class, 'handleCompleted']);
        Event::listen(OCRFailed::class, [LogOCRActivity::class, 'handleFailed']);
    }
}
