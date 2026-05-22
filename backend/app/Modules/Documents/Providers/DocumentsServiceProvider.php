<?php

namespace App\Modules\Documents\Providers;

use App\Modules\Documents\Events\DocumentAnalysisCompleted;
use App\Modules\Documents\Events\DocumentProcessingStarted;
use App\Modules\Documents\Events\DocumentUploaded;
use App\Modules\Documents\Listeners\LogDocumentAnalysisActivity;
use App\Modules\Documents\Listeners\LogDocumentProcessingActivity;
use App\Modules\Documents\Listeners\LogDocumentUploadedActivity;
use App\Modules\Documents\Repositories\Contracts\IDocumentRepository;
use App\Modules\Documents\Repositories\DocumentRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IDocumentRepository::class, DocumentRepository::class);
    }

    public function boot(): void
    {
        Event::listen(DocumentUploaded::class, LogDocumentUploadedActivity::class);
        Event::listen(DocumentProcessingStarted::class, LogDocumentProcessingActivity::class);
        Event::listen(DocumentAnalysisCompleted::class, LogDocumentAnalysisActivity::class);
    }
}
