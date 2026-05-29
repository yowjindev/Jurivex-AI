<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_token_budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('monthly_token_limit')->default(10_000_000);
            $table->unsignedBigInteger('current_month_tokens')->default(0);
            $table->unsignedTinyInteger('alert_threshold_pct')->default(80);
            $table->date('budget_period_start');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_token_budgets');
    }
};
