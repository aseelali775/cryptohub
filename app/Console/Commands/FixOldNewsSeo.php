<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\News;
use Illuminate\Support\Str;

class FixOldNewsSeo extends Command
{
    protected $signature = 'seo:fix-news-slugs';
    protected $description = 'Generate secure slugs for old news articles.';

    public function handle()
    {
        $this->info('Starting to fix old news slugs...');
        
        $newsItems = News::whereNull('slug')->get();
        $count = 0;

        foreach ($newsItems as $news) {
            // 🟢 استخدام البرمجة الدفاعية هنا أيضاً
            $safeSlug = Str::slug($news->title_en ?: 'news-'.$news->id);
            
            $news->update([
                'slug' => $safeSlug
            ]);
            $count++;
        }

        $this->info("✅ Successfully generated secure slugs for {$count} articles.");
    }
}