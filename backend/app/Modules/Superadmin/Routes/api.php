<?php

use App\Modules\Superadmin\Http\Controllers\AIUsageController;
use App\Modules\Superadmin\Http\Controllers\SuperadminController;
use App\Modules\Superadmin\Http\Middleware\SuperadminOnly;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/superadmin')
    ->middleware(['auth:sanctum', SuperadminOnly::class])
    ->group(function () {
        Route::get('/organizations',                               [SuperadminController::class, 'index']);
        Route::post('/organizations',                              [SuperadminController::class, 'store']);
        Route::get('/organizations/{org}/invitation-codes',        [SuperadminController::class, 'listInvitationCodes']);
        Route::post('/organizations/{org}/invitation-codes',       [SuperadminController::class, 'generateInvitationCode']);

        Route::get('/ai-usage',                                    [AIUsageController::class, 'index']);
        Route::get('/ai-usage/{orgId}',                            [AIUsageController::class, 'show']);
        Route::put('/organizations/{orgId}/ai-budget',             [AIUsageController::class, 'updateBudget']);
    });
