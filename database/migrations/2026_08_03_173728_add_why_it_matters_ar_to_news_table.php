<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('news', function (Blueprint $table) {$table->text('why_it_matters_ar')->nullable()->after('impact_score');
        });
    }

    public function down()
    {
        Schema::table('news', function (Blueprint $table) {$table->dropColumn('why_it_matters_ar');
        });
    }
};