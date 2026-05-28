<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compliance_flags', function (Blueprint $table) {
            $table->boolean('ai_generated')->default(false)->after('is_resolved');
            $table->decimal('confidence', 5, 4)->nullable()->after('ai_generated');
            $table->string('source', 50)->nullable()->after('confidence');
            $table->string('ai_model', 100)->nullable()->after('source');
            $table->text('explanation')->nullable()->after('ai_model');
        });
    }

    public function down(): void
    {
        Schema::table('compliance_flags', function (Blueprint $table) {
            $table->dropColumn(['ai_generated', 'confidence', 'source', 'ai_model', 'explanation']);
        });
    }
};
