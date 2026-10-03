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
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            $table->json('sparkline_in_7d')
                ->nullable()
                ->after('image_url');

            $table->decimal('ath', 30, 10)
                ->nullable()
                ->after('sparkline_in_7d');

            $table->decimal('atl', 30, 10)
                ->nullable()
                ->after('ath');

            $table->decimal('high_24h', 30, 10)
                ->nullable()
                ->after('atl');

            $table->decimal('low_24h', 30, 10)
                ->nullable()
                ->after('high_24h');

            $table->timestamp('ath_date')
                ->nullable()
                ->after('low_24h');

            $table->timestamp('atl_date')
                ->nullable()
                ->after('ath_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cryptocurrencies', function (Blueprint $table) {
            $table->dropColumn([
                'sparkline_in_7d',
                'ath',
                'atl',
                'high_24h',
                'low_24h',
                'ath_date',
                'atl_date',
            ]);
        });
    }
};