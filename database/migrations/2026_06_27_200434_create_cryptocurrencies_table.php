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
        Schema::create('cryptocurrencies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم العملة الكامل (مثل: Bitcoin)
            $table->string('symbol')->unique(); // رمز العملة الفريد (مثل: BTC)
            
            // استخدام decimal لضمان الدقة العالية لأسعار العملات الرقمية الصغيرة والكبيرة
            $table->decimal('current_price', 18, 4)->default(0); 
            
            // نسبة التغير خلال 24 ساعة (مثال: +2.45% أو -1.20%)
            $table->decimal('change_24h', 5, 2)->default(0); 
            
            // حجم التداول المالي الضخم خلال 24 ساعة
            $table->decimal('volume_24h', 22, 2)->default(0); 
            
            $table->string('image_url')->nullable(); // رابط صورة أو أيقونة العملة
            $table->timestamps(); // تاريخ الإنشاء والتحديث تلقائياً
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cryptocurrencies');
    }
};