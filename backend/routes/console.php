<?php

use App\Console\Commands\ResetMonthlyAIBudgets;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ResetMonthlyAIBudgets::class)->monthlyOn(1, '00:00');
