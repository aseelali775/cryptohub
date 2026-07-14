<template>
  <DashboardLayout>
    
    <!-- 🌐 وسم الـ SEO الديناميكي للوحة التحكم -->
    <Head>
      <title>{{ t('seo_title') }}</title>
      <meta name="description" :content="t('seo_desc')" />
      <meta property="og:title" :content="t('seo_title')" />
      <meta property="og:description" :content="t('seo_desc')" />
      <meta property="og:type" content="website" />
    </Head>

    <!-- تغيير محاذاة النص ديناميكياً بناءً على اللغة المحددة -->
    <div class="space-y-6" :class="locale === 'ar' ? 'text-right' : 'text-left'">
      
      <!-- أولاً: بطاقات المؤشرات الإحصائية السريعة (Stats Grid) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <!-- بطاقة القيمة السوقية -->
        <div class="p-5 bg-[#1e293b] border border-slate-800 rounded-2xl flex items-center justify-between shadow-lg gap-4">
          <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-400 text-2xl flex-shrink-0">💰</div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-slate-400 font-medium mb-1 truncate">{{ t('market_cap') }}</p>
            <h3 class="text-lg sm:text-xl font-bold text-white font-mono">$2.45T</h3>
            <span class="text-[11px] sm:text-xs text-emerald-400 font-bold flex items-center gap-1 mt-1 whitespace-nowrap">
              ▲ 3.2% {{ t('since_yesterday') }}
            </span>
          </div>
        </div>

        <!-- بطاقة حجم التداول -->
        <div class="p-5 bg-[#1e293b] border border-slate-800 rounded-2xl flex items-center justify-between shadow-lg gap-4">
          <div class="p-3 bg-blue-500/10 rounded-xl text-blue-400 text-2xl flex-shrink-0">📊</div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-slate-400 font-medium mb-1 truncate">{{ t('volume_24h') }}</p>
            <h3 class="text-lg sm:text-xl font-bold text-white font-mono">$84.1B</h3>
            <span class="text-[11px] sm:text-xs text-slate-400 font-medium flex items-center gap-1 mt-1 whitespace-nowrap">
              {{ t('normal_volume') }}
            </span>
          </div>
        </div>

        <!-- بطاقة هيمنة البيتكوين -->
        <div class="p-5 bg-[#1e293b] border border-slate-800 rounded-2xl flex items-center justify-between shadow-lg sm:col-span-2 lg:col-span-1 gap-4">
          <div class="p-3 bg-amber-500/10 rounded-xl text-amber-400 text-2xl flex-shrink-0">🪙</div>
          <div class="min-w-0 flex-1">
            <p class="text-xs text-slate-400 font-medium mb-1 truncate">{{ t('btc_dominance') }}</p>
            <h3 class="text-lg sm:text-xl font-bold text-white font-mono">54.2%</h3>
            <span class="text-[11px] sm:text-xs text-amber-400 font-bold flex items-center gap-1 mt-1 whitespace-nowrap">
              ▲ 0.5% {{ t('this_week') }}
            </span>
          </div>
        </div>
      </div>

      <!-- 🛡️ ثانياً: بانر التنبيه القانوني الصريح لحماية المنصة (Market Analysis Notice) -->
      <div class="p-4 bg-amber-500/5 border border-amber-500/15 rounded-2xl flex flex-col sm:flex-row items-start gap-3 shadow-md">
        <div class="p-2 bg-amber-500/10 text-amber-400 rounded-xl text-sm flex-shrink-0 mt-0.5">⚠️</div>
        <div class="space-y-1">
          <h4 class="text-xs sm:text-sm font-bold text-amber-400">{{ t('notice_title') }}</h4>
          <p class="text-[11px] sm:text-xs text-slate-300 leading-relaxed font-normal">
            {{ t('notice_body') }}
          </p>
        </div>
      </div>

      <!-- ثالثاً: قسم جدول مراقبة الأسعار الديناميكي -->
      <div class="bg-[#1e293b] border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        
        <!-- ترويسة متجاوبة: تترتب عمودياً على الهواتف لمنع ضغط العناصر وأفقياً على الشاشات الأكبر -->
        <div class="p-5 border-b border-slate-800 flex flex-col sm:flex-row-reverse justify-between items-stretch sm:items-center bg-[#1e293b]/50 gap-4">
          
          <!-- زر التحديث الفوري المطور -->
          <button 
            @click="refreshPrices" 
            :disabled="isRefreshing"
            class="px-4 py-2.5 sm:py-2 text-xs font-semibold rounded-xl bg-emerald-500 text-slate-900 hover:bg-emerald-400 disabled:bg-slate-700 disabled:text-slate-400 flex items-center justify-center gap-2 transition-all shadow-lg active:scale-95 cursor-pointer flex-shrink-0"
          >
            <span :class="{ 'animate-spin': isRefreshing }">🔄</span>
            {{ isRefreshing ? t('refreshing_btn') : t('refresh_btn') }}
          </button>

          <h2 class="text-base sm:text-lg font-bold text-white" :class="locale === 'ar' ? 'text-right' : 'text-left'">
            {{ t('table_title') }}
          </h2>
        </div>

        <!-- جدول البيانات المخصص المترجم المتجاوب المحمي -->
        <div class="overflow-x-auto touch-pan-x">
          <table class="w-full border-collapse min-w-[600px]" :class="locale === 'ar' ? 'text-right' : 'text-left'">
            <thead>
              <tr class="border-b border-slate-800 text-slate-400 text-xs font-semibold bg-[#0f172a]/30 whitespace-nowrap">
                <th class="p-4">{{ t('th_currency') }}</th>
                <th class="p-4">{{ t('th_price') }}</th>
                <th class="p-4">{{ t('th_change') }}</th>
                <th class="p-4">{{ t('th_volume') }}</th>
                <th class="p-4" :class="locale === 'ar' ? 'text-left' : 'text-right'">{{ t('th_action') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-sm whitespace-nowrap">
              
              <template v-if="cryptocurrencies.length > 0">
                <tr v-for="crypto in cryptocurrencies" :key="crypto.id" class="hover:bg-slate-800/30 transition-colors">
                  <td class="p-4 flex items-center gap-3">
                    <img v-if="crypto.image_url" :src="crypto.image_url" class="w-6 h-6 rounded-full object-contain flex-shrink-0" alt="icon" />
                    <span v-else class="text-xl flex-shrink-0">🪙</span>
                    <div class="min-w-0">
                      <span class="font-bold text-white block truncate">{{ crypto.name }}</span>
                      <span class="text-xs text-slate-500 font-mono uppercase block tracking-wider">{{ crypto.symbol }}</span>
                    </div>
                  </td>
                  <td class="p-4 font-mono font-medium text-white">
                    ${{ Number(crypto.current_price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                  </td>
                  <td class="p-4 font-mono font-bold" :class="crypto.change_24h >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ crypto.change_24h >= 0 ? '▲ +' : '▼ ' }}{{ Math.abs(crypto.change_24h) }}%
                  </td>
                  <td class="p-4 font-mono text-slate-400">
                    ${{ Number(crypto.volume_24h).toLocaleString('en-US') }}
                  </td>
                  <td class="p-4" :class="locale === 'ar' ? 'text-left' : 'text-right'">
                    <button 
                      @click="router.get(`/crypto/${crypto.symbol.toLowerCase()}`)" 
                      class="px-3 py-1.5 text-xs font-medium rounded-lg bg-[#0f172a] text-slate-300 border border-slate-800 hover:bg-emerald-500 hover:text-slate-900 hover:border-emerald-500 transition-all cursor-pointer shadow-sm"
                    >
                      {{ t('btn_analyze') }}
                    </button>
                  </td>
                </tr>
              </template>

              <!-- واجهة حالة قاعدة البيانات الفارغة المترجمة -->
              <template v-else>
                <tr>
                  <td colspan="5" class="p-12 text-center text-slate-400 bg-[#1e293b]/20 whitespace-normal">
                    <div class="flex flex-col items-center justify-center gap-3 max-w-sm mx-auto">
                      <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-xl shadow-inner">📥</div>
                      <p class="text-sm font-medium text-slate-300">{{ t('empty_db') }}</p>
                      <p class="text-xs text-slate-500 leading-relaxed">{{ t('empty_db_desc') }}</p>
                    </div>
                  </td>
                </tr>
              </template>

            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '../layouts/DashboardLayout.vue';
import { ref, computed } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';

// استقبال البيانات
defineProps({
  cryptocurrencies: {
    type: Array,
    required: true
  }
});

// إدارة اللغة الحالية النشطة قادمة من لارافل
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// متغير حالة تحميل زر التحديث
const isRefreshing = ref(false);

// دالة إرسال طلب التحديث الفوري
const refreshPrices = () => {
    isRefreshing.value = true;
    router.post('/refresh-prices', {}, {
        preserveScroll: true,
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

// قاموس الترجمة المركزي ومفاتيح التحذيرات القانونية والسيو المحدثة
const translations = {
  ar: {
    seo_title: 'لوحة التحكم - معالجة ومحاكاة الأسواق الحية | CryptoHub',
    seo_desc: 'تابع تفاصيل ومؤشرات إجمالي القيمة السوقية للعملات الرقمية وحجم التداول على مدار الساعة مع وصول فوري لجداول البيانات والتحليلات.',
    market_cap: 'إجمالي القيمة السوقية',
    since_yesterday: 'منذ أمس',
    volume_24h: 'حجم التداول (24 ساعة)',
    normal_volume: 'معدل تداول طبيعي',
    btc_dominance: 'هيمنة البيتكوين (BTC.D)',
    this_week: 'هذا الأسبوع',
    table_title: 'مؤشرات أسعار العملات الرقمية الكبرى',
    refresh_btn: 'تحديث الأسعار الآن',
    refreshing_btn: 'جاري تحديث الأسعار الحية...',
    th_currency: 'العملة',
    th_price: 'السعر الحالي',
    th_change: 'تغير (24 ساعة)',
    th_volume: 'حجم التداول',
    th_action: 'الإجراء',
    btn_analyze: 'تحليل المؤشرات',
    empty_db: 'قاعدة البيانات جاهزة ومستقرة',
    empty_db_desc: 'اضغط على زر التحديث بالأعلى لضخ الأسعار فوراً.',
    notice_title: 'تنبيه تحليل ومحاكاة السوق',
    notice_body: 'البيانات والمؤشرات المعروضة يتم إنشاؤها بناءً على نماذج البيانات التاريخية والمؤشرات الإحصائية المفتوحة. هذه محاكاة تحليلية بحتة وليست توقعات مالية أو نصائح استثمارية. استخدم هذه البيانات كلياً كمورد تعليمي لفهم حركة واتجاهات السوق.'
  },
  en: {
    seo_title: 'Dashboard - Market Metrics & Simulations | CryptoHub',
    seo_desc: 'Track total cryptocurrency market cap, 24h trading volume, and real-time data feeds with advanced analytics tools.',
    market_cap: 'Total Market Cap',
    since_yesterday: 'since yesterday',
    volume_24h: 'Volume (24H)',
    normal_volume: 'Normal volume rate',
    btc_dominance: 'Bitcoin Dominance (BTC.D)',
    this_week: 'this week',
    table_title: 'Major Cryptocurrency Market Metrics',
    refresh_btn: 'Refresh Prices Now',
    refreshing_btn: 'Updating Live Prices...',
    th_currency: 'Currency',
    th_price: 'Current Price',
    th_change: 'Change (24H)',
    th_volume: 'Trading Volume',
    th_action: 'Action',
    btn_analyze: 'Analyze Metrics',
    empty_db: 'Database is Ready & Stable',
    empty_db_desc: 'Click the refresh button above to pump live prices instantly.',
    notice_title: 'Market Analysis Notice',
    notice_body: 'The analytics and metrics displayed below are generated based on historical data models and static technical indicators. These are informational simulations, not financial forecasts or trading advice. Use this data strictly as an educational learning resource to understand market trends.'
  }
};

// دالة جلب النص المترجم بناءً على مفتاح اللغة
const t = (key) => {
  return translations[locale.value][key] || key;
};
</script>