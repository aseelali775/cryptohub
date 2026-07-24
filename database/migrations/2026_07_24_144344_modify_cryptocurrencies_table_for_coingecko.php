<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            // 1. إزالة القيد الفريد (unique) من حقل symbol
            $table->dropUnique(['symbol']);

            // 2. إضافة الحقول الجديدة
            if (!Schema::hasColumn('cryptocurrencies', 'coingecko_id')) {
                $table->string('coingecko_id')->unique()->after('id')->nullable();
            }
            if (!Schema::hasColumn('cryptocurrencies', 'market_cap')) {
                $table->decimal('market_cap', 24, 2)->default(0)->after('volume_24h');
            }

            // 3. توسيع دقة الحقول لتستوعب الأرقام الضخمة والصغيرة جداً
            $table->decimal('current_price', 24, 12)->default(0)->change();
            $table->decimal('change_24h', 10, 2)->default(0)->change();
            $table->decimal('volume_24h', 24, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            $table->dropColumn(['coingecko_id', 'market_cap']);
        });
    }
};