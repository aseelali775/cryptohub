<template>
  <HomeLayout>
    
    <Head>
      <title>
        {{ locale === 'ar' ? 'سوق العملات الرقمية بالكامل | CryptoHub' : 'All Crypto Markets | CryptoHub' }}
      </title>
      <meta 
        name="description" 
        :content="locale === 'ar' ? 'استعراض شامل وقائمة حية لأفضل 20 عملة مشفرة في الأسواق العالمية مع تحديث فوري للأسعار ونسب التغير.' : 'Comprehensive list and live feed tracking the top 20 crypto assets globally with instant price updates.'" 
      />
      <meta property="og:title" :content="locale === 'ar' ? 'سوق العملات الرقمية | CryptoHub' : 'Crypto Markets | CryptoHub'" />
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16 space-y-8" :class="locale === 'ar' ? 'text-right' : 'text-left'">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-[#1e293b] p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
              {{ t('title') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
              {{ t('desc') }}
            </p>
          </div>
          <span class="px-3.5 py-1.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] sm:text-xs rounded-xl font-bold animate-pulse flex-shrink-0">
            {{ t('live_update') }}
          </span>
        </div>

        <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
          <div class="overflow-x-auto touch-pan-x">
            <table class="w-full border-collapse min-w-[700px]" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
              
              <thead class="bg-slate-50 dark:bg-[#0f172a]/60 border-b border-slate-200 dark:border-slate-800">
                <tr class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider whitespace-nowrap">
                  <th class="p-5" :class="locale === 'ar' ? 'text-right' : 'text-left'">{{ t('asset') }}</th>
                  <th class="p-5" :class="locale === 'ar' ? 'text-left' : 'text-right'">{{ t('price') }}</th>
                  <th class="p-5" :class="locale === 'ar' ? 'text-left' : 'text-right'">{{ t('change') }}</th>
                  <th class="p-5 text-center">{{ t('actions') }}</th>
                </tr>
              </thead>
              
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-sm whitespace-nowrap">
                <tr 
                  v-for="crypto in cryptos" 
                  :key="crypto.id" 
                  class="hover:bg-slate-50 dark:hover:bg-[#0f172a]/40 transition-colors group"
                >
                  
                  <td class="p-5 flex items-center gap-4">
                    <img :src="crypto.image_url" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-slate-100 dark:bg-slate-800 p-1 object-contain flex-shrink-0 shadow-sm" :alt="crypto.name" />
                    <div class="min-w-0">
                      <span class="font-bold text-slate-900 dark:text-white block text-sm sm:text-base mb-0.5 truncate">{{ crypto.name }}</span>
                      <span class="text-[11px] text-slate-500 font-bold uppercase font-mono tracking-wider block">{{ crypto.symbol }}</span>
                    </div>
                  </td>
                  
                  <td class="p-5 font-mono font-bold text-slate-700 dark:text-slate-200 text-sm sm:text-base" :class="locale === 'ar' ? 'text-left' : 'text-right'">
                    ${{ Number(crypto.current_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
                  </td>
                  
                  <td class="p-5 font-mono font-bold text-sm sm:text-base" :class="[crypto.change_24h >= 0 ? 'text-emerald-500' : 'text-rose-500', locale === 'ar' ? 'text-left' : 'text-right']">
                    {{ crypto.change_24h >= 0 ? '▲ +' : '▼ ' }}{{ Math.abs(crypto.change_24h) }}%
                  </td>
                  
                  <td class="p-5 text-center">
                    <Link 
                      :href="`/crypto/${crypto.symbol.toLowerCase()}`" 
                      class="inline-flex px-5 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-600 hover:bg-emerald-500 hover:text-white dark:bg-[#1e293b] dark:text-slate-300 dark:hover:bg-emerald-500 dark:hover:text-white transition-all cursor-pointer shadow-sm active:scale-95"
                    >
                      {{ t('analyze') }}
                    </Link>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
// الانتباه لحساسية الأحرف في مسار المجلد (layouts)
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Link, usePage, Head } from '@inertiajs/vue3'; // 🟢 تم استيراد Link بدلاً من Router
import { computed } from 'vue';

defineProps({
  cryptos: Array
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// قاموس الترجمة لسهولة الإدارة ونظافة الكود في الأعلى
const translations = {
  ar: {
    title: 'سوق العملات الرقمية بالكامل',
    desc: 'قائمة شاملة تستعرض أفضل العملات المشفرة في السوق حالياً',
    live_update: 'تحديث مباشر (Live)',
    asset: 'العملة (Asset)',
    price: 'السعر الحالي',
    change: 'تغير (24س)',
    actions: 'الإجراءات',
    analyze: 'تحليل فني'
  },
  en: {
    title: 'All Crypto Markets',
    desc: 'Comprehensive list tracking top crypto assets globally',
    live_update: 'Live Update',
    asset: 'Asset',
    price: 'Price',
    change: 'Change (24h)',
    actions: 'Actions',
    analyze: 'Analyze'
  }
};

const t = (key) => translations[locale.value][key] || key;
</script>