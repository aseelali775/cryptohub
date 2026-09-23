<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_articles', function (Blueprint $table) {
            $table->json('faq_ar')->nullable()->after('meta_description_ar');
            $table->json('faq_en')->nullable()->after('faq_ar');
        });
    }

    public function down(): void
    {
        Schema::table('academy_articles', function (Blueprint $table) {
            $table->dropColumn([
                'faq_ar',
                'faq_en',
            ]);
        });
    }
};