<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_extraction_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->integer('page_start');
            $table->integer('page_end');
            $table->string('status')->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->integer('word_count')->nullable();
            $table->integer('char_count')->nullable();
            $table->string('extractor_type')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
            $table->index(['document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_extraction_chunks');
    }
};
