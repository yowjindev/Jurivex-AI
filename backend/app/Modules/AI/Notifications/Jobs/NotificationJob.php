<?php

namespace App\Modules\AI\Notifications\Jobs;

use App\Modules\Documents\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Document $document,
        public string   $event,
    ) {}

    public function handle(): void
    {
        // Phase 2: send email/push notifications when analysis completes
    }
}
