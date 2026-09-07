<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('pending')
                ->after('ai_processed')
                ->index();

            $table->string('rejection_reason')
                ->nullable()
                ->after('status');
        });

        /*
         * الأخبار التي تمت معالجتها بواسطة AI سابقًا
         * نعتبرها منشورة حاليًا.
         *
         * الأخبار غير المعالجة تبقى pending.
         */
        DB::table('news')
            ->where('ai_processed', true)
            ->update([
                'status' => 'published',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'rejection_reason',
            ]);
        });
    }
};