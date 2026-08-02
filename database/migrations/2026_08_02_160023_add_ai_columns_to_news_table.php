<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('news', function (Blueprint $table) {$table->text('summary_ar')->nullable()->after('content_ar');
            $table->string('sentiment')->nullable()->after('summary_ar');$table->string('category')->nullable()->after('sentiment');
            $table->integer('impact_score')->nullable()->after('category');$table->boolean('ai_processed')->default(false)->after('impact_score');
        });
    }

    public function down()
    {
        Schema::table('news', function (Blueprint $table) {$table->dropColumn(['summary_ar', 'sentiment', 'category', 'impact_score', 'ai_processed']);
        });
    }
};