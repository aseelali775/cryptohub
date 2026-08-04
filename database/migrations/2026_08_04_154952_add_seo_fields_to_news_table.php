<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('news', function (Blueprint $table) {
            $table->string('slug')->nullable()->index()->after('id');$table->json('keywords')->nullable()->after('category');
        });
    }
    public function down() {
        Schema::table('news', function (Blueprint $table) {$table->dropColumn(['slug', 'keywords']);
        });
    }
};