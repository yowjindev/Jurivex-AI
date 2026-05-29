<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_extraction_chunks', function (Blueprint $table): void {
            $table->string('analysis_status')->default('pending')->after('processed_at');
            $table->text('analysis_summary')->nullable()->after('analysis_status');
            $table->json('analysis_key_points')->nullable()->after('analysis_summary');
            $table->json('analysis_parties')->nullable()->after('analysis_key_points');
            $table->string('analysis_governing_law')->nullable()->after('analysis_parties');
            $table->string('analysis_effective_date')->nullable()->after('analysis_governing_law');
            $table->decimal('analysis_risk_score', 8, 4)->nullable()->after('analysis_effective_date');
            $table->decimal('analysis_confidence', 8, 4)->nullable()->after('analysis_risk_score');
            $table->string('analysis_model')->nullable()->after('analysis_confidence');
            $table->longText('analysis_raw_response')->nullable()->after('analysis_model');
            $table->text('analysis_error_message')->nullable()->after('analysis_raw_response');
            $table->timestamp('analyzed_at')->nullable()->after('analysis_error_message');
        });
    }

    public function down(): void
    {
        Schema::table('document_extraction_chunks', function (Blueprint $table): void {
            $table->dropColumn([
                'analysis_status',
                'analysis_summary',
                'analysis_key_points',
                'analysis_parties',
                'analysis_governing_law',
                'analysis_effective_date',
                'analysis_risk_score',
                'analysis_confidence',
                'analysis_model',
                'analysis_raw_response',
                'analysis_error_message',
                'analyzed_at',
            ]);
        });
    }
};
