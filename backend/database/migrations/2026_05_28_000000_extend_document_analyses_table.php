<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_analyses', function (Blueprint $table) {
            $table->decimal('confidence', 5, 4)->nullable()->after('risk_score');
            $table->json('parties')->nullable()->after('key_points');
            $table->string('governing_law')->nullable()->after('parties');
            $table->longText('raw_response')->nullable()->after('analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_analyses', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'parties', 'governing_law', 'raw_response']);
        });
    }
};
