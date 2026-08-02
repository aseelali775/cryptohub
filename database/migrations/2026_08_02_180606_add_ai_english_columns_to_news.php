<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('news', function (Blueprint $table) {$table->string('ai_title')->nullable()->after('content_en');
        $table->longText('ai_content')->nullable()->after('ai_title');$table->text('ai_summary')->nullable()->after('ai_content');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            //
        });
    }
};
