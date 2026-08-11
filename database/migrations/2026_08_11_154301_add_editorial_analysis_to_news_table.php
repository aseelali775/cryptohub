<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->longText('analysis_ar')->nullable()->after('why_it_matters_ar');$table->longText('context_ar')->nullable()->after('analysis_ar');
            $table->text('what_to_watch_ar')->nullable()->after('context_ar');$table->text('limitations_ar')->nullable()->after('what_to_watch_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {$table->dropColumn([
                'analysis_ar',
                'context_ar',
                'what_to_watch_ar',
                'limitations_ar'
            ]);
        });
    }
};