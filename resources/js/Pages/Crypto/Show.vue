<template>
  <HomeLayout>
    
    <Head>
      <title>
        {{ locale === 'ar' 
          ? `تحليل عملة ${crypto.name} (${crypto.symbol.toUpperCase()}) بالذكاء الاصطناعي | CryptoHub` 
          : `${crypto.name} (${crypto.symbol.toUpperCase()}) AI Analysis & Live Price | CryptoHub` 
        }}
      </title>
      <meta 
        name="description" 
        :content="locale === 'ar' 
          ? `تابع سعر ${crypto.name} المباشر والتحليلات الفنية المتقدمة وتقارير المشاعر المدعومة بالذكاء الاصطناعي فوراً.` 
          : `Monitor live ${crypto.name} price, advanced technical metrics, and real-time AI-backed sentiment reports.`" 
      />
      <meta property="og:title" :content="locale === 'ar' ? `تحليل عملة ${crypto.name} | CryptoHub` : `${crypto.name} Analysis | CryptoHub`" />
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16 space-y-6" :class="locale === 'ar' ? 'text-right' : 'text-left'">
        
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-white dark:bg-[#1e293b] p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
          
          <div class="flex items-center gap-5">
            <img :src="crypto.image_url" class="w-14 h-14 sm:w-16 sm:h-16 rounded-full shadow-md object-contain bg-slate-100 dark:bg-slate-800 p-2" :alt="crypto.name" />
            <div>
              <div class="flex items-center gap-3 mb-1">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ crypto.name }}
                </h1>
                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold rounded-lg uppercase tracking-wider">
                  {{ crypto.symbol }}
                </span>
              </div>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium">{{ t('sub_title') }}</p>
            </div>
          </div>
          
          <div class="flex flex-row lg:flex-col items-center lg:items-end justify-between gap-2 border-t lg:border-t-0 border-slate-100 dark:border-slate-800 pt-4 lg:pt-0">
            <div class="flex items-baseline gap-3">
              <span class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white font-mono">
                ${{ Number(crypto.current_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 6 }) }}
              </span>
              <span 
                class="text-sm sm:text-base font-bold flex items-center gap-1"
                :class="crypto.change_24h >= 0 ? 'text-emerald-500' : 'text-rose-500'"
              >
                {{ crypto.change_24h >= 0 ? '▲' : '▼' }} {{ Math.abs(crypto.change_24h) }}%
              </span>
            </div>
            
            <Link 
              href="/" 
              class="text-xs font-bold text-slate-500 hover:text-emerald-500 dark:text-slate-400 dark:hover:text-emerald-400 transition-colors flex items-center gap-1.5"
            >
              <span>{{ locale === 'ar' ? '🡪' : '🡨' }}</span>
              <span>{{ t('back_btn') }}</span>
            </Link>
          </div>
        </div>

        <div class="p-4 sm:p-5 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-2xl flex flex-col sm:flex-row items-start gap-4 shadow-sm">
          <div class="w-10 h-10 bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-full flex items-center justify-center text-lg flex-shrink-0">
            ⚠️
          </div>
          <div class="space-y-1.5 pt-1">
            <h4 class="text-sm font-bold text-amber-900 dark:text-amber-400">{{ t('page_notice_title') }}</h4>
            <p class="text-xs sm:text-sm text-amber-700/80 dark:text-amber-200/70 leading-relaxed font-medium">
              {{ t('page_notice_body') }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          
          <div class="lg:col-span-2 xl:col-span-3 space-y-6">
            
            <div class="p-5 sm:p-6 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl h-[400px] flex flex-col justify-between shadow-sm">
              <div class="flex justify-between items-center gap-2 mb-4">
                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">{{ t('chart_title') }}</h3>
                <span class="text-[10px] sm:text-xs text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1 rounded-full font-mono font-bold">{{ t('chart_runtime') }}</span>
              </div>
              
              <div class="flex-1 flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50 dark:bg-[#0f172a]/30 p-6 text-center group transition-colors hover:border-emerald-500/30">
                <span class="text-4xl sm:text-5xl mb-3 grayscale group-hover:grayscale-0 transition-all duration-500">📈</span>
                <p class="text-xs sm:text-sm max-w-md leading-relaxed font-medium">{{ t('chart_placeholder') }}</p>
              </div>
              
              <div class="flex gap-2 text-xs font-mono font-bold mt-4" :class="locale === 'ar' ? 'justify-start' : 'justify-end'">
                <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white rounded-lg transition-colors">1H</button>
                <button class="px-3 py-1.5 bg-emerald-500 text-white rounded-lg shadow-sm shadow-emerald-500/20">24H</button>
                <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white rounded-lg transition-colors">1W</button>
                <button class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white rounded-lg transition-colors">1M</button>
              </div>
            </div>
            
            <div class="p-5 sm:p-6 bg-gradient-to-br from-indigo-50 to-white dark:from-[#151e32] dark:to-[#1e293b] border border-indigo-100 dark:border-indigo-500/20 rounded-3xl shadow-sm relative overflow-hidden">
              <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/5 dark:bg-indigo-500/10 blur-3xl rounded-full translate-x-1/2 -translate-y-1/2"></div>
              
              <h3 class="text-sm sm:text-base font-bold text-indigo-900 dark:text-indigo-300 mb-4 flex items-center gap-2 relative z-10">
                <span class="text-xl">🤖</span> {{ t('ai_title') }}
              </h3>
              
              <div class="relative z-10 p-5 bg-white/60 dark:bg-[#0f172a]/60 backdrop-blur-sm rounded-2xl border border-indigo-100 dark:border-indigo-500/10 text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                <p class="mb-4">{{ t('ai_placeholder') }}</p>
                
                <div class="pt-3 border-t border-indigo-100 dark:border-indigo-500/20 text-xs text-indigo-600/70 dark:text-indigo-400/70 font-mono">
                  {{ t('ai_footer_note') }}
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-6">
            
            <div class="p-5 sm:p-6 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
                {{ t('market_data') }}
              </h3>
              
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-xs font-bold text-slate-500">{{ t('current_price') }}</span>
                  <span class="font-mono text-slate-900 dark:text-white font-black text-sm">${{ Number(crypto.current_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                  <span class="text-xs font-bold text-slate-500">{{ t('change_24h') }}</span>
                  <span class="font-mono text-sm font-black" :class="crypto.change_24h >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                    {{ crypto.change_24h >= 0 ? '▲ +' : '▼ ' }} {{ Math.abs(crypto.change_24h) }}%
                  </span>
                </div>

                <div class="flex justify-between items-center">
                  <span class="text-xs font-bold text-slate-500">{{ t('volume_24h') }}</span>
                  <span class="font-mono text-sm font-bold text-slate-700 dark:text-slate-300 truncate max-w-[150px]">
                    ${{ Number(crypto.volume_24h).toLocaleString('en-US') }}
                  </span>
                </div>
              </div>
            </div>

            <div class="p-5 sm:p-6 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">{{ t('technical_indicators') }}</h3>
              <div class="space-y-3 text-xs">
                <div class="p-3 bg-slate-50 dark:bg-[#0f172a]/60 rounded-xl border border-slate-100 dark:border-slate-800 font-mono flex justify-between items-center">
                  <span class="text-slate-500 font-bold">RSI (14)</span>
                  <span class="text-emerald-500 font-bold">58.4 (Neutral)</span>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-[#0f172a]/60 rounded-xl border border-slate-100 dark:border-slate-800 font-mono flex justify-between items-center">
                  <span class="text-slate-500 font-bold">MA (50)</span>
                  <span class="text-slate-700 dark:text-slate-200 font-bold">${{ Number(crypto.current_price * 0.98).toLocaleString('en-US', { maximumFractionDigits: 2 }) }}</span>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
// التأكد من استدعاء HomeLayout بحرف صغير لتجنب أخطاء لينكس
import HomeLayout from '@/layouts/HomeLayout.vue';
import { computed } from 'vue';
import { Link, usePage, Head } from '@inertiajs/vue3';

// استقبال بيانات العملة المحددة من الـ Controller
defineProps({
  crypto: {
    type: Object,
    required: true
  }
});

// جلب وتتبع حالة اللغة الحالية النشطة
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// قاموس الترجمة المركزي والشامل لشاشة تفاصيل التحليل
const translations = {
  ar: {
    sub_title: 'شاشة معالجة البيانات المتقدمة والمحاكاة الفنية للأسواق',
    back_btn: 'العودة للرئيسية',
    chart_title: 'حركة وحجم الأسعار التاريخية',
    chart_runtime: 'الرسوم البيانية (Simulation Mode)',
    chart_placeholder: 'المخططات البيانية المتقدمة للتحليل (Charts) سيتم دمجها هنا قريباً لقراءة النبض الإحصائي بدقة.',
    ai_title: 'تحليل المشاعر والذكاء الاصطناعي (AI Sentiment)',
    ai_placeholder: 'هذه المساحة مخصصة في المرحلة الثانية لقراءة وتلخيص التقارير الإخبارية فوراً عبر الـ AI، واستخراج نبض السوق العام للعملة (إيجابي / سلبي / محايد) بناءً على المعطيات الحالية.',
    market_data: 'بيانات السوق المباشرة',
    current_price: 'السعر الحالي',
    change_24h: 'معدل التغير (24س)',
    volume_24h: 'حجم التداول (24س)',
    technical_indicators: 'المؤشرات الفنية (محاكاة المرحلة 3)',
    page_notice_title: 'تنبيه تحليل ومحاكاة الأسعار',
    page_notice_body: 'المعطيات التحليلية المعروضة يتم توليدها بناءً على نماذج البيانات التاريخية والمؤشرات الفنية الثابتة. هذه محاكاة بيانية للمؤشرات وليست توقعات مالية أو فوركس. استخدم هذه البيانات كلياً كمورد تعليمي.',
    ai_footer_note: 'ملاحظة قانونية: هذا التقرير يُنشأ آلياً لأغراض التقييم والتحليل الإحصائي فقط، ولا يعتبر نصيحة استثمارية للتداول بأي أصل رقمي.'
  },
  en: {
    sub_title: 'Advanced Analytics Screen & Market Technical Simulation',
    back_btn: 'Back to Home',
    chart_title: 'Historical Price Movement',
    chart_runtime: 'Interactive Charts (Simulation)',
    chart_placeholder: 'Advanced analytical charts will be injected here in the upcoming stage to track statistical momentum.',
    ai_title: 'AI Sentiment Analysis Hub',
    ai_placeholder: 'This space is reserved in Phase 2 for real-time news aggregation and AI summarization, extracting overall market sentiment (Bullish / Bearish / Neutral) based on current statistical inputs.',
    market_data: 'Live Market Data',
    current_price: 'Current Price',
    change_24h: 'Change (24H)',
    volume_24h: 'Volume (24H)',
    technical_indicators: 'Technical Indicators (Phase 3)',
    page_notice_title: 'Market Analysis Notice',
    page_notice_body: 'The following metrics are generated based on historical data models and static technical indicators. These are informational simulations, not financial forecasts or recommendations. Use this data strictly as an educational learning resource.',
    ai_footer_note: 'Legal Note: This insight is automatically generated for analytical evaluation. It is not financial advice to trade any asset.'
  }
};

// دالة جلب المصطلحات المترجمة
const t = (key) => {
  return translations[locale.value][key] || key;
};
</script>