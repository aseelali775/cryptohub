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
    Schema::create('news', function (Blueprint $table) {
        $table->id();
        
        // حقول العنوان للغتين
        $table->string('title_ar');
        $table->string('title_en');
        
        // حقول المحتوى والتفاصيل للغتين
        $table->text('content_ar');
        $table->text('content_en');
        
        // حقول إضافية عامة للأخبار
        $table->string('image_url')->nullable(); // رابط صورة الخبر
        $table->string('source')->nullable();    // مصدر الخبر (مثل Reuters, CoinDesk)
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
