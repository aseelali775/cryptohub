<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_articles', function (Blueprint $table) {
            $table->longText('content')->nullable()->change();
            $table->text('excerpt')->nullable()->change();
            $table->string('seo_title')->nullable()->change();
            $table->text('meta_description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('academy_articles', function (Blueprint $table) {
            $table->longText('content')->nullable(false)->change();
            $table->text('excerpt')->nullable(false)->change();
            $table->string('seo_title')->nullable(false)->change();
            $table->text('meta_description')->nullable(false)->change();
        });
    }
};