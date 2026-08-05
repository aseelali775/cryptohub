<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('crypto_ai_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cryptocurrency_id')->constrained('cryptocurrencies')->cascadeOnDelete();
            
            $table->string('trend')->default('Neutral'); // Bullish, Bearish, Neutral
            $table->integer('confidence')->default(0); // 0 إلى 100
            $table->integer('strength_score')->default(5); // 1 إلى 10
            
            $table->text('summary')->nullable();
            $table->json('bullish_factors')->nullable();
            $table->json('risk_factors')->nullable();
            
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['cryptocurrency_id', 'generated_at']);
        });
    }

    public function down() {
        Schema::dropIfExists('crypto_ai_reports');
    }
};