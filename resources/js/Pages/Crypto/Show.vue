<template>
  <HomeLayout>
    <Head>
      <title>{{ locale === 'ar' ? `تحليل ${crypto.name} | CryptoHub` : `${crypto.name} Analysis | CryptoHub` }}</title>
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 space-y-6" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
        
        <div class="flex flex-col lg:flex-row justify-between gap-6 bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <div class="flex items-center gap-5">
            <img :src="crypto.image_url" class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 p-2 object-contain" />
            <div>
              <div class="flex items-center gap-3">
                <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ crypto.name }}</h1>
                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold rounded-lg uppercase">{{ crypto.symbol }}</span>
              </div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ locale === 'ar' ? 'مركز معلومات العملة (Coin Hub)' : 'Coin Intelligence Hub' }}</p>
            </div>
          </div>
          <div class="flex flex-col items-end justify-center">
            <div class="flex items-baseline gap-3">
              <span class="text-3xl font-black text-slate-900 dark:text-white font-mono">${{ Number(crypto.current_price).toLocaleString() }}</span>
              <span class="text-base font-bold px-2 py-1 rounded-lg" :class="crypto.change_24h >= 0 ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10' : 'text-rose-500 bg-rose-50 dark:bg-rose-500/10'">
                {{ crypto.change_24h >= 0 ? '▲' : '▼' }} {{ Math.abs(crypto.change_24h) }}%
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-6 border-b border-slate-200 dark:border-slate-800 overflow-x-auto no-scrollbar">
          <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="whitespace-nowrap pb-4 px-2 border-b-2 font-bold transition-colors flex items-center gap-2">
            📊 {{ locale === 'ar' ? 'نظرة عامة' : 'Overview' }}
          </button>
          <button @click="activeTab = 'news'" :class="activeTab === 'news' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="whitespace-nowrap pb-4 px-2 border-b-2 font-bold transition-colors flex items-center gap-2">
            📰 {{ locale === 'ar' ? 'الأخبار' : 'News' }}
            <span class="bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 px-1.5 py-0.5 rounded text-[10px]">{{ coinNews.length }}</span>
          </button>
          <button @click="activeTab = 'ai'" :class="activeTab === 'ai' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="whitespace-nowrap pb-4 px-2 border-b-2 font-bold transition-colors flex items-center gap-2">
            🤖 {{ locale === 'ar' ? 'تحليل الذكاء الاصطناعي' : 'AI Analysis' }}
          </button>
        </div>

        <div v-show="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in">
          <div class="lg:col-span-2 bg-white dark:bg-[#151e32] p-6 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
            <h3 class="font-bold text-slate-900 dark:text-white mb-4">{{ locale === 'ar' ? 'الرسم البياني' : 'Price Chart' }}</h3>
            <div class="h-[350px]"><VueApexCharts v-if="chartSeries[0].data.length" type="area" height="100%" :options="chartOptions" :series="chartSeries" /></div>
          </div>
          <div class="space-y-6">
            <div class="bg-white dark:bg-[#151e32] p-6 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
              <h3 class="font-bold text-slate-900 dark:text-white mb-4 pb-4 border-b border-slate-100 dark:border-slate-800">{{ locale === 'ar' ? 'بيانات السوق' : 'Market Data' }}</h3>
              <div class="space-y-3">
                <div class="flex justify-between text-sm"><span class="text-slate-500 font-bold">{{ locale === 'ar' ? 'القيمة السوقية' : 'Market Cap' }}</span><span class="font-mono font-bold dark:text-white">${{ Number(crypto.market_cap).toLocaleString() }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-slate-500 font-bold">{{ locale === 'ar' ? 'حجم التداول (24س)' : 'Volume (24h)' }}</span><span class="font-mono font-bold dark:text-white">${{ Number(crypto.volume_24h).toLocaleString() }}</span></div>
              </div>
            </div>
            <div class="bg-white dark:bg-[#151e32] p-6 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
              <h3 class="font-bold text-slate-900 dark:text-white mb-4 pb-4 border-b border-slate-100 dark:border-slate-800">{{ locale === 'ar' ? 'النطاق التاريخي' : 'Historical Range' }}</h3>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-500 font-bold">ATH</span><span class="font-mono font-black text-emerald-500">${{ Number(chartData?.ath || 0).toLocaleString() }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500 font-bold">ATL</span><span class="font-mono font-black text-rose-500">${{ Number(chartData?.atl || 0).toLocaleString() }}</span></div>
              </div>
            </div>
          </div>
        </div>

        <div v-show="activeTab === 'news'" class="animate-fade-in">
          <div v-if="coinNews && coinNews.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Link :href="`/news/${news.id}-${news.slug}`" v-for="news in coinNews" :key="news.id" class="bg-white dark:bg-[#151e32] rounded-2xl border border-slate-200 dark:border-slate-800 p-5 hover:border-emerald-500 transition-colors shadow-sm">
              <div class="flex justify-between mb-3">
                <span class="text-[10px] font-mono bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded dark:text-slate-300">{{ news.source }}</span>
                <span class="text-[10px] px-2 py-1 rounded font-bold" :class="news.sentiment === 'Bullish' ? 'bg-emerald-100 text-emerald-700' : (news.sentiment === 'Bearish' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700')">
                  {{ news.sentiment }}
                </span>
              </div>
              <h4 class="font-bold dark:text-white mb-2 line-clamp-2">{{ news.translations[locale === 'ar' ? 'ar' : 'en']?.title }}</h4>
              <p class="text-xs text-slate-500 line-clamp-3">{{ locale === 'ar' ? news.translations.ar.summary : news.translations.en.content }}</p>
            </Link>
          </div>
          <div v-else class="text-center py-20 text-slate-500 font-bold bg-white dark:bg-[#151e32] rounded-3xl border border-slate-200 dark:border-slate-800">
            📰 {{ locale === 'ar' ? 'لا توجد أخبار حديثة مسجلة لهذه العملة حالياً.' : 'No recent news tracked for this coin.' }}
          </div>
        </div>

        <div v-show="activeTab === 'ai'" class="animate-fade-in">
          <div class="text-center py-24 bg-gradient-to-br from-indigo-50 to-white dark:from-[#151e32] dark:to-[#0f172a] border border-indigo-100 dark:border-indigo-500/20 rounded-3xl shadow-sm relative overflow-hidden">
             <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 blur-3xl rounded-full translate-x-1/2 -translate-y-1/2"></div>
             <span class="text-6xl block mb-6 animate-pulse">🤖</span>
             <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">{{ locale === 'ar' ? 'تقرير الذكاء الاصطناعي قادم قريباً' : 'AI Report Coming Soon' }}</h3>
             <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
               {{ locale === 'ar' ? 'يتم حالياً تجهيز نموذج الذكاء الاصطناعي الخاص بـ CryptoHub لتوليد تقارير شاملة بشكل آلي لهذه العملة.' : 'CryptoHub AI model is currently being integrated to generate automated market intelligence reports.' }}
             </p>
          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/layouts/HomeLayout.vue';
import { ref, computed } from 'vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import VueApexCharts from "vue3-apexcharts"; 

const props = defineProps({
  crypto: { type: Object, required: true },
  chartData: { type: Object, required: true },
  coinNews: { type: Array, default: () => [] }
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const activeTab = ref('overview');

const isPositive = computed(() => props.crypto.change_24h >= 0);
const chartSeries = computed(() => [{ name: 'Price', data: props.chartData?.sparkline || [] }]);
const chartOptions = computed(() => ({
  chart: { type: 'area', toolbar: { show: false }, background: 'transparent' },
  colors: [isPositive.value ? '#10b981' : '#f43f5e'], 
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
  dataLabels: { enabled: false }, stroke: { curve: 'smooth', width: 2.5 },
  xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
  yaxis: { show: false }, grid: { show: false, padding: { left: 0, right: 0, top: 0, bottom: 0 } },
  theme: { mode: 'dark' }
}));
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>