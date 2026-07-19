<template>
  <HomeLayout>
    <Head>
      <title>{{ newsItem.title }} | CryptoHub</title>
      <meta name="description" :content="newsItem.content" />
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
          <div class="flex items-center gap-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-mono">
            <span class="px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold tracking-wider">
              {{ newsItem.source }}
            </span>
            <span>•</span>
            <span>{{ newsItem.date }}</span>
          </div>
          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 dark:text-white leading-[1.4] tracking-tight">
            {{ newsItem.title }}
          </h1>
        </header>

        <figure class="w-full aspect-video sm:h-[450px] rounded-[2rem] overflow-hidden bg-slate-900 shadow-2xl mb-12 relative border border-slate-200 dark:border-slate-800">
          <img :src="newsItem.image_url" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" :alt="newsItem.title" />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
        </figure>

        <article class="max-w-none">
          <p class="text-lg sm:text-xl text-slate-700 dark:text-slate-300 leading-relaxed sm:leading-loose whitespace-pre-line font-medium">
            {{ newsItem.content }}
          </p>
        </article>

        <div class="mt-16 p-5 sm:p-6 bg-indigo-50 dark:bg-[#151e32] border border-indigo-100 dark:border-indigo-500/20 rounded-2xl flex flex-col sm:flex-row gap-5 items-start shadow-sm">
          <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl flex-shrink-0">
            🤖
          </div>
          <div>
            <h4 class="text-sm font-bold text-indigo-900 dark:text-indigo-300 mb-2">
              {{ locale === 'ar' ? 'تلخيص ذكي (AI Generated)' : 'Smart Summary (AI Generated)' }}
            </h4>
            <p class="text-xs sm:text-sm text-indigo-700/80 dark:text-indigo-400/80 leading-relaxed font-mono">
              {{ locale === 'ar' 
                ? 'تنويه: تم سحب هذا المحتوى وتلخيصه آلياً عبر محرك الأخبار الذكي الخاص بالمنصة لأغراض إخبارية وتعليمية فقط. هذا النص لا يمثل أي توجيه مالي أو نصيحة استثمارية.' 
                : 'Disclaimer: This content has been automatically aggregated and summarized by the platform AI news engine for informational purposes only, and does not constitute financial advice.' 
              }}
            </p>
          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
// تأكد من صحة مسار الـ Layout (بحرف صغير أو كبير حسب ما تستخدمه في جهازك)
import HomeLayout from '@/Layouts/HomeLayout.vue'; 
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