<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_type', 50);
            $table->string('model', 100);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->string('status', 20)->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at');

            $table->index(['organization_id', 'created_at']);
            $table->index('document_id');
            $table->index(['job_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
