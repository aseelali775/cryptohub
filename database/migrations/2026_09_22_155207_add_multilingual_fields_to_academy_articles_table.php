<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_articles', function (Blueprint $table) {

            $table->string('title_ar')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_ar');

            $table->text('excerpt_ar')->nullable()->after('excerpt');
            $table->text('excerpt_en')->nullable()->after('excerpt_ar');

            $table->longText('content_ar')->nullable()->after('content');
            $table->longText('content_en')->nullable()->after('content_ar');

            $table->string('seo_title_ar')->nullable();
            $table->string('seo_title_en')->nullable();

            $table->text('meta_description_ar')->nullable();
            $table->text('meta_description_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('academy_articles', function (Blueprint $table) {

            $table->dropColumn([
                'title_ar',
                'title_en',
                'excerpt_ar',
                'excerpt_en',
                'content_ar',
                'content_en',
                'seo_title_ar',
                'seo_title_en',
                'meta_description_ar',
                'meta_description_en',
            ]);
        });
    }
};