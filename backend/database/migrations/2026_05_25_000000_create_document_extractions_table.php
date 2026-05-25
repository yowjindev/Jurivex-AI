<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_extractions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete()->unique();
            $table->longText('extracted_text')->nullable();
            $table->integer('page_count')->nullable();
            $table->integer('word_count')->nullable();
            $table->integer('char_count')->nullable();
            $table->string('ocr_engine')->nullable();
            $table->string('extractor_type')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_extractions');
    }
};
