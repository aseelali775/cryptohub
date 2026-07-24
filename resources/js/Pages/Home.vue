<template>
  <HomeLayout>
    <Head>
      <title>{{ t('seoTitle') }}</title>
      <meta name="description" :content="t('seoDesc')" />
    </Head>

    <div class="w-full pb-20 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      
      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16 pb-10 relative overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <div class="text-center lg:text-start z-10" :class="locale === 'ar' ? 'lg:text-right' : 'lg:text-left'">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white leading-[1.2] tracking-tight">
              {{ t('heroTitle1') }} <br>
              <span class="text-emerald-500 bg-emerald-500/10 px-2 rounded-lg">{{ t('heroTitleHighlight') }}</span>
            </h1>
            <p class="mt-6 text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-medium">
              {{ t('heroSub') }}
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
              <Link href="/prices" class="w-full sm:w-auto h-12 px-8 rounded-xl bg-emerald-500 text-white text-sm font-bold hover:bg-emerald-600 hover:shadow-emerald-500/25 hover:shadow-lg transition-all flex items-center justify-center active:scale-95">
                {{ t('btnAnalytics') }}
              </Link>
              <Link href="/news" class="w-full sm:w-auto h-12 px-8 rounded-xl bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-sm font-bold hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center justify-center active:scale-95">
                {{ t('btnNews') }}
              </Link>
            </div>
          </div>
          
          <div class="relative hidden lg:flex justify-center items-center h-full">
            <div class="absolute w-72 h-72 bg-emerald-500/20 blur-[100px] rounded-full"></div>
            <div class="relative w-80 h-80 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-700/50 rounded-full flex items-center justify-center shadow-2xl p-8 transform hover:scale-105 transition-transform duration-700">
              <div class="w-full h-full rounded-full border border-dashed border-emerald-500/40 animate-[spin_20s_linear_infinite] absolute"></div>
              <div class="text-[120px] font-black text-emerald-500 opacity-90 drop-shadow-2xl">₿</div>
            </div>
          </div>
        </div>
      </section>

      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 mb-12">
        <div class="flex items-center justify-between mb-4">
          <div class="flex gap-4 overflow-x-auto scrollbar-none pb-2 w-full snap-x">
            
            <Link 
              :href="`/crypto/${coin.symbol.toLowerCase()}`" 
              v-for="coin in props.tickerCryptos" 
              :key="coin.id" 
              class="flex items-center gap-3 bg-white dark:bg-[#151e32] px-4 py-2.5 rounded-full border border-slate-200 dark:border-slate-800 shadow-sm flex-shrink-0 hover:border-emerald-500/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/5 transition-all cursor-pointer snap-start"
            >
              <img v-if="coin.image_url" :src="coin.image_url" :alt="coin.name" class="w-6 h-6 rounded-full object-contain bg-slate-100 dark:bg-slate-800 p-0.5" />
              <div v-else class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                {{ coin.symbol.charAt(0).toUpperCase() }}
              </div>
              
              <span class="text-slate-900 dark:text-white font-bold text-sm uppercase">{{ coin.symbol }}</span>
              <span class="text-slate-600 dark:text-slate-400 font-mono text-sm font-semibold">
                ${{ Number(coin.current_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
              </span>
              <span :class="Number(coin.change_24h) >= 0 ? 'text-emerald-500' : 'text-rose-500'" class="font-bold text-xs flex items-center gap-0.5 bg-slate-50 dark:bg-slate-800 px-1.5 py-0.5 rounded-md">
                {{ Number(coin.change_24h) >= 0 ? '▲' : '▼' }} {{ Math.abs(coin.change_24h) }}%
              </span>
            </Link>

            <div v-if="!props.tickerCryptos || props.tickerCryptos.length === 0" class="text-sm font-medium text-slate-500 dark:text-slate-400 italic px-4 py-2 animate-pulse">
              {{ locale === 'ar' ? 'جاري مزامنة أسعار السوق...' : 'Syncing live market data...' }}
            </div>
          </div>
          
          <Link href="/prices" class="hidden md:flex items-center gap-1 flex-shrink-0 text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-emerald-500 ms-4 transition-colors">
            {{ t('viewAllCoins') }} <span :class="locale === 'ar' ? 'rotate-180' : ''">›</span>
          </Link>
        </div>
      </section>

      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          
          <div class="lg:col-span-3 flex flex-col gap-6">
            <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-md transition-shadow">
              <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 border-b border-slate-100 dark:border-slate-800 pb-3">{{ t('marketOverview') }}</h3>
              <div class="space-y-4">
                <div v-for="(stat, index) in marketStats" :key="index" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-[#0f172a] transition-colors">
                  <span class="text-xs sm:text-sm font-bold text-slate-500 dark:text-slate-400">{{ stat.label }}</span>
                  <div class="text-end">
                    <div class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ stat.value }}</div>
                    <div :class="stat.isUp ? 'text-emerald-500' : 'text-rose-500'" class="text-[10px] font-bold mt-0.5">
                      {{ stat.isUp ? '▲ +' : '▼ ' }}{{ stat.change }}%
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-6 flex flex-col">
            <div class="flex items-center justify-between mb-4 px-2">
              <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ t('topNews') }}
              </h3>
            </div>
            
            <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm flex flex-col gap-4">
              
              <Link :href="mainNews ? `/news/${mainNews.id}` : '#'" v-if="mainNews" class="relative w-full h-64 rounded-2xl overflow-hidden bg-slate-900 group cursor-pointer block shadow-inner">
                <img :src="mainNews.image_url" class="absolute inset-0 w-full h-full object-cover z-0 group-hover:scale-105 transition-transform duration-700 opacity-80" :alt="locale === 'ar' ? mainNews.title_ar : mainNews.title_en" />
                <div class="absolute inset-0 bg-gradient-to-t from-[#0b1121] via-[#0b1121]/50 to-transparent z-10"></div>
                
                <div class="absolute bottom-0 inset-x-0 p-5 z-20">
                  <span class="inline-block px-2.5 py-1 rounded bg-emerald-500 text-white text-[10px] font-bold mb-3 uppercase tracking-wider">{{ t('mainNewsTag') }}</span>
                  <h4 class="text-lg sm:text-xl font-bold text-white mb-2 leading-snug group-hover:text-emerald-400 transition-colors">
                    {{ locale === 'ar' ? mainNews.title_ar : mainNews.title_en }}
                  </h4>
                  <div class="flex items-center gap-4 mt-3 text-xs font-bold text-slate-400">
                    <span class="text-emerald-500">{{ t('readMore') }}</span>
                    <span>• {{ mainNews.source }}</span>
                  </div>
                </div>
              </Link>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-2" v-if="subNews && subNews.length">
                <Link :href="`/news/${news.id}`" v-for="news in subNews" :key="news.id" class="bg-slate-50 dark:bg-[#0f172a] p-4 rounded-2xl border border-slate-100 dark:border-slate-800 hover:border-emerald-500/30 transition-all cursor-pointer flex flex-col h-full group">
                  <span class="inline-block px-2 py-1 rounded bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-bold mb-3 self-start border border-slate-200 dark:border-slate-700 shadow-sm">{{ news.source }}</span>
                  <h5 class="text-xs font-bold text-slate-900 dark:text-white leading-relaxed mb-2 flex-1 group-hover:text-emerald-500 transition-colors">
                    {{ locale === 'ar' ? news.title_ar : news.title_en }}
                  </h5>
                </Link>
              </div>

              <div v-if="!mainNews" class="h-64 flex flex-col items-center justify-center text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                <span>📰</span>
                <p class="text-sm mt-2 font-medium">{{ locale === 'ar' ? 'لا توجد أخبار حالياً' : 'No news available' }}</p>
              </div>
            </div>
          </div>

          <div class="lg:col-span-3 flex flex-col gap-6">
            
            <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col items-center hover:shadow-md transition-shadow relative overflow-hidden">
              <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 blur-2xl rounded-full"></div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6 relative z-10">{{ t('fearGreed') }}</h3>
              <div class="relative w-48 h-24 mb-4 overflow-hidden flex justify-center z-10">
                <svg viewBox="0 0 100 50" class="w-full h-full overflow-visible">
                  <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" class="stroke-slate-100 dark:stroke-slate-800" stroke-width="12" stroke-linecap="round"/>
                  <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="url(#gradient)" stroke-width="12" stroke-dasharray="125.6" stroke-dashoffset="35" stroke-linecap="round" class="transition-all duration-1000 ease-out"/>
                  <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                      <stop offset="0%" stop-color="#ef4444" />
                      <stop offset="50%" stop-color="#eab308" />
                      <stop offset="100%" stop-color="#10b981" />
                    </linearGradient>
                  </defs>
                </svg>
                <div class="absolute bottom-0 text-center">
                  <div class="text-4xl font-black text-slate-900 dark:text-emerald-400">72</div>
                  <div class="text-[10px] font-black uppercase tracking-widest text-emerald-500">{{ t('greed') }}</div>
                </div>
              </div>
              <p class="text-[11px] text-center text-slate-500 dark:text-slate-400 mt-2 relative z-10 font-medium" v-html="t('fearGreedDesc')"></p>
            </div>

            <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between mb-5 border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                  <span class="text-emerald-500">🔥</span> {{ t('topGainers') }}
                </h3>
              </div>
              
              <div class="space-y-3">
                <Link 
                  :href="`/crypto/${coin.symbol.toLowerCase()}`" 
                  v-for="coin in props.topGainers" 
                  :key="coin.id" 
                  class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-[#0f172a] border border-transparent hover:border-slate-100 dark:hover:border-slate-800 transition-all cursor-pointer group"
                >
                  <div class="flex items-center gap-3">
                    <img v-if="coin.image_url" :src="coin.image_url" :alt="coin.name" class="w-7 h-7 rounded-full object-contain bg-slate-100 dark:bg-slate-800 p-1 shadow-sm" />
                    <div class="flex flex-col">
                      <span class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors">{{ coin.name }}</span>
                      <span class="text-[10px] font-mono text-slate-400">{{ coin.symbol.toUpperCase() }}</span>
                    </div>
                  </div>
                  <div class="flex flex-col items-end">
                    <span class="text-[11px] font-mono font-bold text-slate-700 dark:text-slate-300">
                      ${{ Number(coin.current_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
                    </span>
                    <span class="text-[10px] font-black text-emerald-500 mt-0.5 bg-emerald-50 dark:bg-emerald-500/10 px-1.5 rounded">
                      +{{ coin.change_24h }}%
                    </span>
                  </div>
                </Link>

                <div v-if="!props.topGainers || props.topGainers.length === 0" class="text-xs text-center text-slate-400 py-4 italic">
                  {{ locale === 'ar' ? 'جاري الحساب...' : 'Calculating...' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 mt-16 pt-10 border-t border-slate-200 dark:border-slate-800/80">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl mb-4 shadow-sm border border-emerald-100 dark:border-emerald-500/20">📈</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1.5">{{ t('feat1Title') }}</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ t('feat1Desc') }}</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-500 flex items-center justify-center text-xl mb-4 shadow-sm border border-indigo-100 dark:border-indigo-500/20">⚡</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1.5">{{ t('feat2Title') }}</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ t('feat2Desc') }}</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center text-xl mb-4 shadow-sm border border-amber-100 dark:border-amber-500/20">🤖</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1.5">{{ t('feat3Title') }}</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ t('feat3Desc') }}</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 text-blue-500 flex items-center justify-center text-xl mb-4 shadow-sm border border-blue-100 dark:border-blue-500/20">🛡️</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1.5">{{ t('feat4Title') }}</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ t('feat4Desc') }}</p>
          </div>
        </div>
      </section>

    </div>
  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

// 1. استقبال البيانات الحقيقية من الكنترولر (HomeController)
const props = defineProps({
  tickerCryptos: { type: Array, default: () => [] },
  topGainers: { type: Array, default: () => [] },
  news: { type: Array, default: () => [] } 
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// ==========================================
// 🟢 تقسيم الأخبار الحقيقية بأمان
// ==========================================
const mainNews = computed(() => props.news && props.news.length > 0 ? props.news[0] : null);
const subNews = computed(() => props.news && props.news.length > 1 ? props.news.slice(1, 4) : []);

// ==========================================
// الإحصائيات العامة للمنصة 
// ==========================================
const marketStats = computed(() => [
  { label: locale.value === 'ar' ? 'القيمة السوقية' : 'Market Cap', value: locale.value === 'ar' ? '$2.45 تريليون' : '$2.45T', change: '1.85', isUp: true },
  { label: locale.value === 'ar' ? 'حجم التداول (24س)' : 'Volume (24h)', value: locale.value === 'ar' ? '$86.24 مليار' : '$86.24B', change: '6.24', isUp: true },
  { label: locale.value === 'ar' ? 'هيمنة البيتكوين' : 'BTC Dominance', value: '53.63%', change: '0.35', isUp: true },
  { label: locale.value === 'ar' ? 'عدد العملات المدرجة' : 'Listed Coins', value: '20', change: '0.00', isUp: true },
]);

// ==========================================
// قاموس الترجمة المحدث
// ==========================================
const translations = {
  ar: {
    seoTitle: "CryptoHub | المنصة الرائدة لبيانات العملات الرقمية",
    seoDesc: "مصدرك الموثوق لأحدث أخبار وتحليلات العملات الرقمية لحظياً.",
    heroTitle1: "نبض السوق المشفر",
    heroTitleHighlight: "بين يديك",
    heroSub: "شاشة واحدة تجمع لك الأسعار الحية، الأخبار المؤتمتة، وأدق تفاصيل السوق. اتخذ قراراتك بثقة وابق دائماً في المقدمة.",
    btnAnalytics: "استكشاف الأسواق",
    btnNews: "تصفح الأخبار",
    viewAllCoins: "عرض جميع العملات",
    marketOverview: "نظرة عامة على السوق",
    topNews: "أبرز المستجدات",
    mainNewsTag: "تغطية خاصة",
    readMore: "اقرأ التفاصيل",
    fearGreed: "مؤشر الخوف والطمع",
    greed: "طمع",
    fearGreedDesc: "المؤشر الحالي يعكس حالة <span class='text-emerald-500 font-bold'>طمع</span> في الأسواق.<br>يرجى إدارة مخاطرك بحكمة.",
    topGainers: "أعلى الارتفاعات (24س)",
    feat1Title: "بيانات حية وموثوقة",
    feat1Desc: "أسعار محدثة آلياً من قلب السوق",
    feat2Title: "سرعة في التنفيذ",
    feat2Desc: "منصة مصممة للتجاوب الفوري بدون تأخير",
    feat3Title: "أخبار بالذكاء الاصطناعي",
    feat3Desc: "محرك آلي يجلب ويصنف لك أهم الأخبار",
    feat4Title: "شفافية وحياد",
    feat4Desc: "نعرض البيانات دون أي توجيه استثماري",
  },
  en: {
    seoTitle: "CryptoHub | The Leading Crypto Data Platform",
    seoDesc: "Your trusted source for crypto news and real-time analytics.",
    heroTitle1: "The Crypto Market Pulse",
    heroTitleHighlight: "In Your Hands",
    heroSub: "One screen bringing you live prices, automated news, and deep market details. Make confident decisions and stay ahead.",
    btnAnalytics: "Explore Markets",
    btnNews: "Browse News",
    viewAllCoins: "View All Assets",
    marketOverview: "Global Market Overview",
    topNews: "Top Stories",
    mainNewsTag: "Featured",
    readMore: "Read Details",
    fearGreed: "Fear & Greed Index",
    greed: "Greed",
    fearGreedDesc: "The current index reflects <span class='text-emerald-500 font-bold'>greed</span> in the market.<br>Please manage your risks wisely.",
    topGainers: "Top Gainers (24H)",
    feat1Title: "Live Reliable Data",
    feat1Desc: "Automated real-time prices directly from the market",
    feat2Title: "Lightning Fast",
    feat2Desc: "Built for instant response and zero lag",
    feat3Title: "AI-Powered News",
    feat3Desc: "Automated engine fetching top industry news",
    feat4Title: "Transparent & Neutral",
    feat4Desc: "Providing data without investment bias",
  }
};

const t = (key) => translations[locale.value][key] || key;
</script>

<style scoped>
/* إخفاء شريط التمرير لشريط الأسعار المتحرك */
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>