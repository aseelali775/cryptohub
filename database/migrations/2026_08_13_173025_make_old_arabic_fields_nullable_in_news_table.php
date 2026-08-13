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
            $table->string('title_ar')->nullable()->change();
            $table->longText('content_ar')->nullable()->change();
            $table->text('summary_ar')->nullable()->change();
            $table->text('why_it_matters_ar')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('title_ar')->nullable(false)->change();
            $table->longText('content_ar')->nullable(false)->change();
            $table->text('summary_ar')->nullable(false)->change();
            $table->text('why_it_matters_ar')->nullable(false)->change();
        });
    }
};