<?php
namespace App\Modules\Documents\Providers;

use App\Modules\Documents\Repositories\Contracts\IDocumentRepository;
use App\Modules\Documents\Repositories\DocumentRepository;
use Illuminate\Support\ServiceProvider;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IDocumentRepository::class, DocumentRepository::class);
    }
}
