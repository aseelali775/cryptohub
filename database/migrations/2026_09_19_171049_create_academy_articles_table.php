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
        Schema::create('academy_articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('topic_id')
                ->constrained('academy_topics')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug');

            $table->text('excerpt')->nullable();

            $table->longText('content');

            $table->string('image')->nullable();

            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->enum('status', ['draft', 'published'])
                ->default('draft');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['topic_id', 'slug']);

            $table->index(['status', 'published_at']);
            $table->index(['topic_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academy_articles');
    }
};