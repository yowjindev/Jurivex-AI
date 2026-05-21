<?php

use App\Modules\Organizations\Http\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/organization')->middleware('auth:sanctum')->group(function () {
    Route::get('/',             [OrganizationController::class, 'show'])->name('organization.show');
    Route::get('/members',      [OrganizationController::class, 'members'])->name('organization.members');
    Route::post('/invitations', [OrganizationController::class, 'invite'])->name('organization.invite');
});
