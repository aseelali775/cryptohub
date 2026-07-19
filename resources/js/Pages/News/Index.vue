<template>
  <HomeLayout>
    
    <Head>
      <title>
        {{ locale === 'ar' ? 'آخر أخبار الكريبتو وتلخيصات الذكاء الاصطناعي | CryptoHub' : 'Latest Crypto News & AI Summaries | CryptoHub' }}
      </title>
      <meta 
        name="description" 
        :content="locale === 'ar' ? 'تغطية شاملة ومستمرة لأحداث سوق العملات الرقمية العالمية مع تلخيص ذكي فوري لأهم الأنباء.' : 'Comprehensive global coverage of crypto events with instant smart AI summarization.'" />
      <meta property="og:title" :content="locale === 'ar' ? 'أخبار الكريبتو الذكية | CryptoHub' : 'Crypto News Hub | CryptoHub'" />
    </Head>

    <div class="w-full min-h-screen pb-20 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16 space-y-8" :class="locale === 'ar' ? 'text-right' : 'text-left'">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-[#1e293b] p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
              {{ locale === 'ar' ? 'آخر أخبار الكريبتو' : 'Latest Crypto News' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
              {{ locale === 'ar' ? 'تغطية شاملة ومستمرة لأحداث السوق العالمية' : 'Comprehensive global coverage of crypto events' }}
            </p>
          </div>
          <span class="px-3.5 py-1.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] sm:text-xs rounded-xl font-bold animate-pulse self-start sm:self-auto flex-shrink-0">
            {{ locale === 'ar' ? 'تحديث تلقائي (Live Feed)' : 'Live Feed' }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div 
            v-for="item in newsFeed" 
            :key="item.id" 
            class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-emerald-500/50 transition-all flex flex-col group"
          >
            <div class="h-48 sm:h-52 overflow-hidden relative bg-slate-900 flex-shrink-0">
              <img 
                :src="item.image_url" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                alt="news image" 
              />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-80"></div>
              <span class="absolute bottom-3 right-3 bg-[#0f172a]/80 text-slate-200 text-[10px] px-2.5 py-1.5 rounded font-bold backdrop-blur-sm border border-white/10">
                {{ item.source }}
              </span>
            </div>

            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
              <div class="space-y-3 min-w-0">
                <span class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-mono font-semibold block">{{ item.date }}</span>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors leading-snug break-words line-clamp-2">
                  {{ item.title }}
                </h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-3 break-words font-medium">
                  {{ item.content }}
                </p>
              </div>

              <div class="pt-4 border-t border-slate-100 dark:border-slate-800/60 flex justify-between items-center gap-2">
                <span class="text-[10px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-mono font-bold">#Market</span>
                
                <Link 
                  :href="`/news/${item.id}`" 
                  class="text-xs font-bold text-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors flex items-center gap-1 cursor-pointer flex-shrink-0"
                >
                  <span>{{ locale === 'ar' ? 'اقرأ المزيد ←' : 'Read More →' }}</span>
                </Link>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
// 🟢 تأكدنا من استدعاء Layout بحرف صغير (layouts) لضمان التوافق مع Railway
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Link, usePage, Head } from '@inertiajs/vue3'; // 🟢 تم استيراد Link
import { computed } from 'vue';

defineProps({
  newsFeed: {
    type: Array,
    required: true
  }
});

// استخراج مفتاح اللغة النشطة من لارافل لتوحيد أداء السيو والتوافق البصري
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
</script>