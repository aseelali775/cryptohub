<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up() {
    Schema::create('crypto_aliases', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cryptocurrency_id')->constrained('cryptocurrencies')->cascadeOnDelete();
        $table->string('alias'); 
        $table->timestamps();
        $table->index(['cryptocurrency_id', 'alias']); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_aliases');
    }
};
