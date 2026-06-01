<?php

use App\Modules\Documents\Http\Controllers\ConversationController;
use App\Modules\Documents\Http\Controllers\DocumentController;
use App\Modules\Documents\Http\Controllers\DocumentSearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/documents')->middleware('auth:sanctum')->group(function () {
    Route::get('/',       [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/',      [DocumentController::class, 'store'])->name('documents.store');

    Route::get('/search', DocumentSearchController::class)->name('documents.search');

    Route::get('/{id}',        [DocumentController::class, 'show'])->name('documents.show');
    Route::patch('/{id}',      [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/{id}',     [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/{id}/retry', [DocumentController::class, 'retry'])->name('documents.retry');

    // Conversations (RAG chat)
    Route::get('/{documentId}/conversations',                            [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/{documentId}/conversations',                           [ConversationController::class, 'store'])->name('conversations.store');
    Route::post('/{documentId}/conversations/{conversationId}/messages', [ConversationController::class, 'reply'])->name('conversations.reply');
    Route::get('/{documentId}/conversations/{conversationId}/messages',  [ConversationController::class, 'messages'])->name('conversations.messages');
});
