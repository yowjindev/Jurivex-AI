<?php
use App\Modules\Documents\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/documents')->middleware('auth:sanctum')->group(function () {
    Route::get('/',        [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/',       [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/{id}',    [DocumentController::class, 'show'])->name('documents.show');
    Route::patch('/{id}',  [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});
