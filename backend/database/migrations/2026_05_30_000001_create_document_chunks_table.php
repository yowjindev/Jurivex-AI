<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure pgvector extension is enabled
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('text');
            $table->unsignedInteger('token_count')->default(0);
            $table->string('embedding_model', 100)->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
            $table->index('document_id');
            $table->index('organization_id');
        });

        // vector(3072) — 3072 dimensions for Gemini gemini-embedding-001
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(3072)');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
