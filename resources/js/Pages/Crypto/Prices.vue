<template>
  <HomeLayout>
    <Head>
      <title>{{ t('title') }}</title>
      <meta name="description" :content="t('desc')" />
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-10 space-y-4" :class="locale === 'ar' ? 'text-right' : 'text-left'">
        
        <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20">
          
          <div class="flex items-center gap-3">
            <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ t('title') }}</h1>
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          </div>

          <div class="flex-1 w-full md:max-w-md relative">
            <input 
              v-model="searchQuery" 
              type="text" 
              :placeholder="t('search_placeholder')" 
              class="w-full bg-slate-50 dark:bg-[#0f172a] border border-slate-200 dark:border-slate-700 text-sm rounded-xl px-4 py-2.5 outline-none focus:border-emerald-500 transition-colors dark:text-white"
            >
            <div class="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1 hidden sm:flex" :class="locale === 'ar' ? 'left-2 right-auto' : 'right-2'">
              <button @click="searchQuery = 'BTC'" class="text-[10px] font-bold px-2 py-1 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded hover:bg-emerald-500 hover:text-white transition-colors">BTC</button>
              <button @click="searchQuery = 'ETH'" class="text-[10px] font-bold px-2 py-1 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded hover:bg-emerald-500 hover:text-white transition-colors">ETH</button>
              <button @click="searchQuery = 'SOL'" class="text-[10px] font-bold px-2 py-1 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded hover:bg-emerald-500 hover:text-white transition-colors">SOL</button>
            </div>
          </div>
        </div>

        <div class="flex overflow-x-auto gap-2 pb-2 scrollbar-none snap-x">
          <button @click="activeFilter = 'all'" :class="activeFilter === 'all' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'" class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start">
            {{ t('filter_all') }}
          </button>
          <button @click="activeFilter = 'gainers'" :class="activeFilter === 'gainers' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'" class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start">
            🔥 {{ t('filter_gainers') }}
          </button>
          <button @click="activeFilter = 'mega'" :class="activeFilter === 'mega' ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'" class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start">
            💎 {{ t('filter_mega') }}
          </button>
          <button @click="activeFilter = 'losers'" :class="activeFilter === 'losers' ? 'bg-rose-500 text-white border-rose-500' : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-rose-500/50'" class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start">
            📉 {{ t('filter_losers') }}
          </button>
        </div>

        <div class="flex flex-col gap-2">
          
          <div class="flex items-center justify-between px-4 py-2 text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">
            <div class="flex gap-4 items-center">
              <span class="w-4 text-center">#</span>
              <span>{{ t('asset') }}</span>
            </div>
            <div class="flex items-center gap-12 sm:gap-24">
              <span class="hidden sm:block text-center w-20">{{ t('trend') }}</span>
              <span class="text-right w-20">{{ t('price') }}</span>
            </div>
          </div>

          <Link 
            :href="`/crypto/${crypto.symbol.toLowerCase()}`" 
            v-for="(crypto, index) in filteredCryptos" 
            :key="crypto.id" 
            class="flex items-center justify-between p-3 sm:p-4 bg-white dark:bg-[#151e32] rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 dark:hover:bg-[#1e293b]/50 transition-all group"
          >
            <div class="flex items-center gap-3 sm:gap-4">
              <span class="text-[10px] sm:text-xs font-mono font-bold text-slate-400 w-4 text-center">{{ index + 1 }}</span>
              <img :src="crypto.image_url" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-50 dark:bg-slate-800 p-1 object-contain flex-shrink-0" :alt="crypto.name" />
              <div class="flex flex-col">
                <span class="font-bold text-slate-900 dark:text-white text-sm sm:text-base group-hover:text-emerald-500 transition-colors uppercase">{{ crypto.symbol }}</span>
                <span class="text-[10px] sm:text-xs text-slate-500 font-medium truncate max-w-[80px] sm:max-w-[150px]">{{ crypto.name }}</span>
              </div>
            </div>
            
            <div class="flex items-center gap-6 sm:gap-16">
              <div class="hidden sm:flex w-20 h-8 items-center justify-center opacity-70 group-hover:opacity-100 transition-opacity">
                <svg viewBox="0 0 100 30" class="w-full h-full" preserveAspectRatio="none">
                  <path 
                    d="M0,15 Q10,5 20,15 T40,15 T60,20 T80,10 T100,5" 
                    fill="none" 
                    :stroke="crypto.change_24h >= 0 ? '#10b981' : '#f43f5e'" 
                    stroke-width="2" 
                    stroke-linecap="round"
                    :class="crypto.change_24h >= 0 ? 'drop-shadow-[0_2px_4px_rgba(16,185,129,0.4)]' : 'drop-shadow-[0_2px_4px_rgba(244,63,94,0.4)]'"
                  />
                </svg>
              </div>

              <div class="flex flex-col items-end w-20 sm:w-24">
                <span class="font-mono font-bold text-slate-900 dark:text-white text-sm sm:text-base tracking-tight">
                  ${{ Number(crypto.current_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
                </span>
                <span 
                  class="text-[10px] sm:text-xs font-black px-1.5 py-0.5 rounded mt-1 font-mono"
                  :class="crypto.change_24h >= 0 ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-500'"
                >
                  {{ crypto.change_24h >= 0 ? '▲ +' : '▼ ' }}{{ Math.abs(crypto.change_24h) }}%
                </span>
              </div>
            </div>

          </Link>

          <div v-if="filteredCryptos.length === 0" class="text-center py-12 text-slate-500 dark:text-slate-400">
            <span class="text-4xl mb-3 block">🔍</span>
            <p class="text-sm font-bold">{{ t('no_results') }}</p>
          </div>

        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  cryptos: { type: Array, default: () => [] }
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// 🟢 أنظمة البحث والفلترة الفورية (Client-Side)
const searchQuery = ref('');
const activeFilter = ref('all');

const filteredCryptos = computed(() => {
  let result = props.cryptos;

  // 1. تطبيق الفلتر الأفقي
  if (activeFilter.value === 'gainers') {
    result = result.filter(c => c.change_24h > 0).sort((a, b) => b.change_24h - a.change_24h);
  } else if (activeFilter.value === 'losers') {
    result = result.filter(c => c.change_24h < 0).sort((a, b) => a.change_24h - b.change_24h);
  } else if (activeFilter.value === 'mega') {
    result = result.filter(c => ['BTC', 'ETH', 'SOL', 'BNB'].includes(c.symbol.toUpperCase()));
  }

  // 2. تطبيق البحث النصي
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(c => 
      c.name.toLowerCase().includes(q) || 
      c.symbol.toLowerCase().includes(q)
    );
  }

  return result;
});

// 🟢 الترجمات
const translations = {
  ar: {
    title: 'الأسواق',
    desc: 'متابعة لحظية لأفضل الأصول الرقمية',
    search_placeholder: 'ابحث عن عملة (مثال: BTC)',
    filter_all: 'جميع العملات',
    filter_gainers: 'الأكثر ارتفاعاً',
    filter_mega: 'القيادية',
    filter_losers: 'الأكثر انخفاضاً',
    asset: 'العملة',
    price: 'السعر',
    trend: 'الاتجاه (7أيام)',
    no_results: 'لا توجد نتائج مطابقة للبحث'
  },
  en: {
    title: 'Markets',
    desc: 'Real-time tracking of top digital assets',
    search_placeholder: 'Search assets (e.g., BTC)',
    filter_all: 'All Coins',
    filter_gainers: 'Top Gainers',
    filter_mega: 'Mega Caps',
    filter_losers: 'Top Losers',
    asset: 'Asset',
    price: 'Price',
    trend: 'Trend (7d)',
    no_results: 'No matching results found'
  }
};

const t = (key) => translations[locale.value][key] || key;
</script>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>