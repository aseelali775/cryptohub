<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\News;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        News::create([
            'title_ar' => 'البيتكوين يكسر حاجزاً تاريخياً جديداً وسط إقبال مؤسسي كبير',
            'title_en' => 'Bitcoin Breaks New Historic Milestone Amid Heavy Institutional Inflow',
            'content_ar' => 'شهدت أسواق العملات الرقمية صعوداً قوياً اليوم حيث تجاوز سعر البيتكوين مستويات قياسية نتيجة لدخول صناديق استثمارية ضخمة إلى السوق.',
            'content_en' => 'Cryptocurrency markets witnessed a powerful rally today as Bitcoin price surged past record levels driven by massive institutional funds entering the market.',
            'image_url' => 'https://images.unsplash.com/photo-1621416894569-0f39ed31d247?w=500',
            'source' => 'CryptoHub Analytics'
        ]);
    }
}
