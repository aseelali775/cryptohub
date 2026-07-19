<template>
  <HomeLayout>
    <Head>
      <title>{{ t('seoTitle') }}</title>
      <meta name="description" :content="t('seoDesc')" />
    </Head>

    <div class="w-full pb-20 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      
      <!-- ========================================== -->
      <!-- 1. قسم الترحيب الرئيسي (Hero Section) -->
      <!-- ========================================== -->
      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16 pb-10 relative overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <div class="text-center lg:text-start z-10">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 dark:text-white leading-[1.2] tracking-tight">
              {{ t('heroTitle1') }} <br>
              <span class="text-emerald-500">{{ t('heroTitleHighlight') }}</span>
            </h1>
            <p class="mt-6 text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
              {{ t('heroSub') }}
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
              <Link href="/dashboard" class="w-full sm:w-auto h-12 px-8 rounded-xl bg-emerald-500 text-white text-sm font-bold hover:bg-emerald-600 transition-all shadow-lg flex items-center justify-center">
                {{ t('btnAnalytics') }}
              </Link>
              <Link href="/news" class="w-full sm:w-auto h-12 px-8 rounded-xl bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-sm font-bold hover:bg-slate-50 transition-all flex items-center justify-center">
                {{ t('btnNews') }}
              </Link>
            </div>
          </div>
          <div class="relative hidden lg:flex justify-center items-center h-full">
            <div class="absolute w-72 h-72 bg-emerald-500/20 blur-[100px] rounded-full"></div>
            <div class="relative w-80 h-80 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-full flex items-center justify-center shadow-2xl p-8">
              <div class="w-full h-full rounded-full border border-dashed border-emerald-500/30 animate-[spin_20s_linear_infinite] absolute"></div>
              <div class="text-[120px] font-black text-emerald-500 opacity-90 drop-shadow-2xl">₿</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ========================================== -->
      <!-- 2. شريط الأسعار الحي (Live Ticker) -->
      <!-- ========================================== -->
      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 mb-10">
        <div class="flex items-center justify-between mb-4">
          <div class="flex gap-4 overflow-x-auto scrollbar-none pb-2 w-full">
            
            <!-- التكرار يعتمد على البيانات الحقيقية من الباك إند -->
            <div v-for="coin in props.tickerCryptos" :key="coin.id" class="flex items-center gap-3 bg-white dark:bg-[#151e32] px-4 py-2.5 rounded-full border border-slate-200 dark:border-slate-800 shadow-sm flex-shrink-0 hover:border-emerald-500/50 transition-colors">
              
              <!-- صورة العملة الحقيقية أو حرف بديل -->
              <img v-if="coin.image_url" :src="coin.image_url" :alt="coin.name" class="w-6 h-6 rounded-full object-contain" />
              <div v-else class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                {{ coin.symbol.charAt(0).toUpperCase() }}
              </div>
              
              <!-- الرمز والسعر ونسبة التغير -->
              <span class="text-slate-900 dark:text-white font-bold text-sm uppercase">{{ coin.symbol }}</span>
              <span class="text-slate-600 dark:text-slate-400 font-mono text-sm">
                ${{ Number(coin.current_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}
              </span>
              <span :class="Number(coin.change_24h) >= 0 ? 'text-emerald-500' : 'text-rose-500'" class="font-bold text-xs flex items-center gap-0.5">
                {{ Number(coin.change_24h) >= 0 ? '▲' : '▼' }} {{ Math.abs(coin.change_24h) }}%
              </span>
            </div>

            <!-- رسالة تظهر إذا كانت قاعدة البيانات فارغة -->
            <div v-if="!props.tickerCryptos || props.tickerCryptos.length === 0" class="text-sm text-slate-500 dark:text-slate-400 italic px-4 py-2">
              {{ locale === 'ar' ? 'جاري مزامنة أسعار السوق...' : 'Syncing live market data...' }}
            </div>

          </div>
          
          <Link href="/prices" class="hidden md:flex items-center gap-1 flex-shrink-0 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-emerald-500 ms-4">
            {{ t('viewAllCoins') }} <span class="rtl:rotate-180">›</span>
          </Link>
        </div>
      </section>

      <!-- ========================================== -->
      <!-- 3. شبكة المحتوى الرئيسية (Main Content Grid) -->
      <!-- ========================================== -->
      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          
          <!-- نظرة على السوق -->
          <div class="lg:col-span-3 flex flex-col gap-6">
            <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
              <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 text-center">{{ t('marketOverview') }}</h3>
              <div class="space-y-5">
                <div v-for="(stat, index) in marketStats" :key="index" class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/60 pb-3 last:border-0 last:pb-0">
                  <span class="text-sm text-slate-600 dark:text-slate-400">{{ stat.label }}</span>
                  <div class="text-end">
                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ stat.value }}</div>
                    <div :class="stat.isUp ? 'text-emerald-500' : 'text-rose-500'" class="text-[11px] font-bold mt-0.5">
                      {{ stat.isUp ? '+' : '' }}{{ stat.change }}%
                    </div>
                  </div>
                </div>
              </div>
              <button class="w-full mt-6 h-10 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all">
                {{ t('viewMoreIndicators') }}
              </button>
            </div>
          </div>

          <!-- أبرز الأخبار -->
          <div class="lg:col-span-6 flex flex-col">
            <div class="flex items-center justify-between mb-4 px-2">
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ t('topNews') }}</h3>
              <div class="flex items-center gap-2 text-slate-400">
                <button class="w-7 h-7 rounded border border-slate-200 dark:border-slate-800 flex items-center justify-center hover:text-emerald-500">‹</button>
                <button class="w-7 h-7 rounded border border-slate-200 dark:border-slate-800 flex items-center justify-center hover:text-emerald-500">›</button>
              </div>
            </div>
          <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm flex flex-col gap-4">
  
  <div v-if="mainNews" class="relative w-full h-56 sm:h-64 rounded-xl overflow-hidden bg-slate-900 group">
    <img :src="mainNews.image_url" class="absolute inset-0 w-full h-full object-cover" />
    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 to-transparent z-10"></div>
    <div class="absolute bottom-0 inset-x-0 p-5 z-20">
      <h4 class="text-xl sm:text-2xl font-bold text-white mb-2 leading-snug">{{ locale === 'ar' ? mainNews.title_ar : mainNews.title_en }}</h4>
      <p class="text-xs text-slate-300 line-clamp-2 max-w-xl">{{ locale === 'ar' ? mainNews.content_ar : mainNews.content_en }}</p>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div v-for="news in subNews" :key="news.id" class="bg-slate-50 dark:bg-[#0f172a] p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 flex flex-col h-full">
      <h5 class="text-sm font-bold text-slate-900 dark:text-white leading-tight mb-3 flex-1">
        {{ locale === 'ar' ? news.title_ar : news.title_en }}
      </h5>
    </div>
  </div>

  <Link href="/news" class="w-full mt-2 h-10 rounded-xl border border-slate-200 dark:border-slate-800 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all flex items-center justify-center">
    {{ t('viewAllNews') }}
  </Link>
</div>
          </div>

          <!-- مؤشر الخوف والارتفاعات -->
          <div class="lg:col-span-3 flex flex-col gap-6">
            <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col items-center">
              <h3 class="text-base font-bold text-slate-900 dark:text-white mb-6">{{ t('fearGreed') }}</h3>
              <div class="relative w-48 h-24 mb-4 overflow-hidden flex justify-center">
                <svg viewBox="0 0 100 50" class="w-full h-full overflow-visible">
                  <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" class="stroke-slate-200 dark:stroke-slate-800" stroke-width="12" stroke-linecap="round"/>
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
                  <div class="text-xs font-bold text-emerald-500">{{ t('greed') }}</div>
                </div>
              </div>
              <p class="text-xs text-center text-slate-500 dark:text-slate-400 mt-2" v-html="t('fearGreedDesc')"></p>
              <button class="text-xs font-bold text-emerald-500 mt-4 flex items-center gap-1 hover:underline">
                <span class="w-4 h-4 rounded-full border border-emerald-500 flex items-center justify-center text-[10px]">i</span> {{ t('understandIndex') }}
              </button>
            </div>

           <div class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
  <h3 class="text-base font-bold text-slate-900 dark:text-white mb-5">{{ t('topGainers') }}</h3>
  
  <div class="space-y-4">
    <!-- 🟢 التكرار هنا الآن يقرأ من قاعدة البيانات الحقيقية -->
    <div v-for="coin in props.topGainers" :key="coin.id" class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <img :src="coin.image_url" :alt="coin.name" class="w-5 h-5 rounded-full object-contain" />
        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
          {{ coin.name }} <span class="text-[10px] text-slate-400">({{ coin.symbol.toUpperCase() }})</span>
        </span>
      </div>
      <span class="text-xs font-bold text-emerald-500">+{{ coin.change_24h }}% ▲</span>
    </div>
  </div>

  <button class="w-full mt-6 text-xs font-bold text-emerald-500 hover:underline">
    {{ t('viewAllGainers') }}
  </button>
</div>
          </div>
        </div>
      </section>

      <!-- ========================================== -->
      <!-- 4. الميزات السفلية (Bottom Features) -->
      <!-- ========================================== -->
      <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 mt-16 pt-8 border-t border-slate-200 dark:border-slate-800/80">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl mb-3">📈</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ t('feat1Title') }}</h4>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('feat1Desc') }}</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl mb-3">⚡</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ t('feat2Title') }}</h4>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('feat2Desc') }}</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl mb-3">🛠️</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ t('feat3Title') }}</h4>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('feat3Desc') }}</p>
          </div>
          <div class="flex flex-col items-center">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl mb-3">👥</div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">{{ t('feat4Title') }}</h4>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ t('feat4Desc') }}</p>
          </div>
        </div>
      </section>

    </div>
  </HomeLayout>
</template>

<script setup>
// forced update to trigger git
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  tickerCryptos: { type: Array, default: () => [] },
  topGainers: { type: Array, default: () => [] },
  news: { type: Array, default: () => [] } // استقبال الأخبار الحقيقية
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// الخبر الرئيسي هو أول عنصر، والفرعية هي العناصر الـ 3 التالية
const mainNews = computed(() => props.news[0] || null);
const subNews = computed(() => props.news.slice(1, 4));

const dummyGainers = [
  { icon: 'R', name: 'Render', symbol: 'RNDR', change: '18.43' },
  { icon: 'A', name: 'Arweave', symbol: 'AR', change: '14.21' },
  { icon: 'I', name: 'Immutable', symbol: 'IMX', change: '12.67' },
];

// ==========================================
// قاموس الترجمة
// ==========================================
const translations = {
  ar: {
    seoTitle: "CryptoHub | الواجهة الرئيسية",
    seoDesc: "مصدرك الموثوق لأحدث أخبار وتحليلات العملات الرقمية لحظياً.",
    heroTitle1: "مصدر موثوق لأخبار وتحليلات",
    heroTitleHighlight: "العملات الرقمية",
    heroSub: "نقدم لك أحدث الأخبار والتحليلات العميقة والأسعار لحظياً. اتخذ قراراتك بثقة وابق دائماً في المقدمة.",
    btnAnalytics: "دخول المنصة",
    btnNews: "تصفح الأخبار",
    viewAllCoins: "عرض جميع العملات",
    marketOverview: "نظرة على السوق",
    viewMoreIndicators: "عرض المزيد من المؤشرات",
    topNews: "أبرز الأخبار",
    mainNewsTag: "خبر رئيسي",
    readMore: "اقرأ المزيد",
    viewAllNews: "عرض جميع الأخبار",
    fearGreed: "مؤشر الخوف والطمع",
    greed: "طمع",
    fearGreedDesc: "يشير المؤشر إلى أن السوق في حالة طمع.<br>قد يكون الوقت مناسباً لتوخي الحذر.",
    understandIndex: "فهم المؤشر",
    topGainers: "المؤشرات الأكثر ارتفاعاً",
    viewAllGainers: "عرض جميع المؤشرات",
    feat1Title: "تحليلات احترافية",
    feat1Desc: "تحليلات فنية وأساسية من خبراء السوق",
    feat2Title: "بيانات لحظية",
    feat2Desc: "أسعار وبيانات السوق محدثة لحظياً",
    feat3Title: "أدوات ذكية",
    feat3Desc: "مجموعة من الأدوات لمساعدتك بقرارات أفضل",
    feat4Title: "مجتمع نشط",
    feat4Desc: "انضم إلى مجتمعنا وتفاعل مع المستثمرين",
  },
  en: {
    seoTitle: "CryptoHub | Home",
    seoDesc: "Your trusted source for crypto news and real-time analytics.",
    heroTitle1: "A Trusted Source for Crypto News &",
    heroTitleHighlight: "Analytics",
    heroSub: "We provide you with the latest news, deep analytics, and real-time prices. Make confident decisions and stay ahead.",
    btnAnalytics: "Launch App",
    btnNews: "Browse News",
    viewAllCoins: "View All Coins",
    marketOverview: "Market Overview",
    viewMoreIndicators: "View More Indicators",
    topNews: "Top News",
    mainNewsTag: "Top Story",
    readMore: "Read More",
    viewAllNews: "View All News",
    fearGreed: "Fear & Greed Index",
    greed: "Greed",
    fearGreedDesc: "The index indicates the market is in extreme greed.<br>It might be a good time to exercise caution.",
    understandIndex: "Understand Index",
    topGainers: "Top Gainers",
    viewAllGainers: "View All Gainers",
    feat1Title: "Pro Analytics",
    feat1Desc: "Technical & fundamental insights by experts",
    feat2Title: "Real-time Data",
    feat2Desc: "Live market prices & data without delay",
    feat3Title: "Smart Tools",
    feat3Desc: "A suite of tools for better trading decisions",
    feat4Title: "Active Community",
    feat4Desc: "Join and interact with thousands of investors",
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