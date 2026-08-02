<template>
  <HomeLayout>
    <Head>
      <title>{{ newsItem.title }} | CryptoHub</title>
      <meta name="description" :content="newsItem.summary || newsItem.content" />
      <meta property="og:image" :content="newsItem.image_url" />
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16" :class="locale === 'ar' ? 'text-right' : 'text-left'">
        
        <Link href="/news" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-500 transition-colors mb-8 group">
          <span class="transition-transform duration-300" :class="locale === 'ar' ? 'group-hover:translate-x-1' : 'group-hover:-translate-x-1'">
            {{ locale === 'ar' ? '←' : '←' }}
          </span>
          <span>{{ locale === 'ar' ? 'العودة لغرفة الأخبار' : 'Back to Newsroom' }}</span>
        </Link>

        <header class="space-y-6 mb-10">
          
          <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-mono" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <span class="px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold tracking-wider">
              {{ newsItem.source }}
            </span>
            <span>•</span>
            <span>{{ newsItem.date }}</span>
            
            <span v-if="newsItem.sentiment === 'Bullish'" class="px-2 py-1.5 rounded-md bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20 font-bold">🟢 {{ locale === 'ar' ? 'صعودي' : 'Bullish' }}</span>
            <span v-else-if="newsItem.sentiment === 'Bearish'" class="px-2 py-1.5 rounded-md bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 font-bold">🔴 {{ locale === 'ar' ? 'هبوطي' : 'Bearish' }}</span>
            <span v-else-if="newsItem.sentiment === 'Neutral'" class="px-2 py-1.5 rounded-md bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 font-bold">⚪ {{ locale === 'ar' ? 'محايد' : 'Neutral' }}</span>

            <span class="px-2 py-1.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 font-bold">#{{ newsItem.category || 'Crypto' }}</span>
            <span v-if="newsItem.impact_score" class="px-2 py-1.5 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold" :title="locale === 'ar' ? 'تأثير الخبر على السوق' : 'Impact Score'">⚡ {{ newsItem.impact_score }}/10</span>
          </div>

          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 dark:text-white leading-[1.4] tracking-tight text-left" dir="ltr">
            {{ newsItem.title }}
          </h1>
        </header>

        <figure class="w-full aspect-video sm:h-[450px] rounded-[2rem] overflow-hidden bg-slate-900 shadow-2xl mb-12 relative border border-slate-200 dark:border-slate-800">
          <img :src="newsItem.image_url" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" :alt="newsItem.title" />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
        </figure>

        <div v-if="newsItem.ai_processed && newsItem.summary" class="mb-10 p-6 sm:p-8 bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-500/20 rounded-3xl shadow-sm relative overflow-hidden">
          <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 blur-2xl rounded-full pointer-events-none"></div>
          <h3 class="text-lg font-bold text-indigo-900 dark:text-indigo-300 mb-4 flex items-center gap-2 relative z-10" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <span class="text-xl">🤖</span>
            {{ locale === 'ar' ? 'الملخص الذكي' : 'AI Summary' }}
          </h3>
          <p class="text-base sm:text-lg text-slate-700 dark:text-slate-300 leading-relaxed font-medium relative z-10 text-left" dir="ltr">
            {{ newsItem.summary }}
          </p>
        </div>

        <div class="mt-8">
          <h3 v-if="newsItem.ai_processed" class="text-xs font-bold text-slate-400 mb-6 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <span>📰</span> {{ locale === 'ar' ? 'المقال الكامل (Full Article)' : 'Full Article' }}
          </h3>
          <article class="max-w-none">
            <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-400 leading-relaxed sm:leading-loose whitespace-pre-line font-medium text-left" dir="ltr">
              {{ newsItem.content }}
            </p>
          </article>
        </div>

        <div class="mt-16 p-5 sm:p-6 bg-slate-100 dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl flex flex-col sm:flex-row gap-5 items-start shadow-sm" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
          <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center text-xl flex-shrink-0">
            ⚖️
          </div>
          <div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-slate-200 mb-2">
              {{ locale === 'ar' ? 'تنويه قانوني (Disclaimer)' : 'Disclaimer' }}
            </h4>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-mono">
              {{ locale === 'ar' 
                ? 'تم إنشاء هذا المحتوى آلياً عبر محرك الذكاء الاصطناعي الخاص بالمنصة لأغراض إخبارية وتعليمية فقط. هذا النص لا يمثل أي توجيه مالي أو نصيحة استثمارية.' 
                : 'This content has been automatically generated by the platform AI engine for informational purposes only, and does not constitute financial advice.' 
              }}
            </p>
          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/layouts/HomeLayout.vue'; 
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
  newsItem: {
    type: Object,
    required: true
  }
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
</script>