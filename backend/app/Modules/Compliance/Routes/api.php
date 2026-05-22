<?php
use App\Modules\Compliance\Http\Controllers\ComplianceFlagsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/compliance')->middleware('auth:sanctum')->group(function () {
    Route::get('/flags',                [ComplianceFlagsController::class, 'index'])->name('compliance.flags.index');
    Route::patch('/flags/{id}/resolve', [ComplianceFlagsController::class, 'resolve'])->name('compliance.flags.resolve');
});
