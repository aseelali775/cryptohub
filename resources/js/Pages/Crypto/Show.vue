<template>
  <DashboardLayout>
    
    <!-- 🌐 وسم الـ SEO الديناميكي الذكي لتفاصيل العملة -->
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

    <!-- ضبط محاذاة النص ديناميكياً بناءً على اللغة الحالية -->
    <div class="space-y-6" :class="locale === 'ar' ? 'text-right' : 'text-left'">
      
      <!-- شريط علوي متجاوب للعودة وتفاصيل العملة -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-[#1e293b] p-4 sm:p-6 rounded-2xl border border-slate-800 shadow-xl">
        <div class="flex items-center gap-4 min-w-0">
          <img :src="crypto.image_url" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full shadow-md object-contain flex-shrink-0" :alt="crypto.name" />
          <div class="min-w-0">
            <h1 class="text-lg sm:text-2xl font-bold text-white flex items-center gap-2 truncate">
              {{ crypto.name }}
              <span class="text-[10px] sm:text-xs uppercase px-2 py-0.5 bg-slate-800 text-slate-400 rounded font-mono font-normal tracking-wider">
                {{ crypto.symbol }}
              </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-0.5 truncate">{{ t('sub_title') }}</p>
          </div>
        </div>
        
        <!-- زر العودة للخلف ممتد ومتناسق مع الجوال -->
        <button 
          @click="router.get('/dashboard')" 
          class="w-full sm:w-auto px-4 py-2.5 sm:py-2 text-xs font-semibold rounded-xl bg-slate-800 text-slate-300 hover:bg-slate-700 transition-all cursor-pointer active:scale-95 flex items-center justify-center gap-1"
        >
          <span>{{ locale === 'ar' ? '🡪' : '🡨' }}</span>
          <span>{{ t('back_btn') }}</span>
        </button>
      </div>

      <!-- 🛡️ بانر التنبيه القانوني لصفحة التحليلات والمخططات (Market Analysis Notice) -->
      <div class="p-4 bg-amber-500/5 border border-amber-500/15 rounded-2xl flex flex-col sm:flex-row items-start gap-3 shadow-md">
        <div class="p-2 bg-amber-500/10 text-amber-400 rounded-xl text-sm flex-shrink-0 mt-0.5">⚠️</div>
        <div class="space-y-1">
          <h4 class="text-xs sm:text-sm font-bold text-amber-400">{{ t('page_notice_title') }}</h4>
          <p class="text-[11px] sm:text-xs text-slate-300 leading-relaxed font-normal">
            {{ t('page_notice_body') }}
          </p>
        </div>
      </div>

      <!-- شبكة العرض الأساسية المتجاوبة للكمبيوتر والجوال -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- العمود الأكبر (2/3 من المساحة): للمخطط البياني والمشاعر -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- مساحة المخطط البياني (مجهزة للمرحلة الثالثة) -->
          <div class="p-4 sm:p-6 bg-[#1e293b] border border-slate-800 rounded-2xl h-[360px] sm:h-[400px] flex flex-col justify-between shadow-lg">
            <div class="flex justify-between items-center gap-2">
              <h3 class="text-xs sm:text-sm font-bold text-slate-300">{{ t('chart_title') }}</h3>
              <span class="text-[10px] sm:text-xs text-slate-500 font-mono text-center">{{ t('chart_runtime') }}</span>
            </div>
            
            <!-- مكان الرسم البياني المؤقت المحمي الأبعاد -->
            <div class="flex-1 flex flex-col items-center justify-center text-slate-400 border border-dashed border-slate-800 rounded-xl my-4 bg-[#0f172a]/20 p-4 text-center">
              <span class="text-3xl sm:text-4xl mb-2">📈</span>
              <p class="text-[11px] sm:text-xs max-w-xs leading-relaxed">{{ t('chart_placeholder') }}</p>
            </div>
            
            <div class="flex gap-2 text-[11px] sm:text-xs text-slate-400 font-mono" :class="locale === 'ar' ? 'justify-start' : 'justify-end'">
              <span class="px-2 py-1 bg-slate-800 rounded">1H</span>
              <span class="px-2 py-1 bg-emerald-500 text-slate-900 font-bold rounded">24H</span>
              <span class="px-2 py-1 bg-slate-800 rounded">1W</span>
              <span class="px-2 py-1 bg-slate-800 rounded">1M</span>
            </div>
          </div>
          
          <!-- مساحة تحليل الذكاء الاصطناعي للمشاعر (مجهزة للمرحلة الثانية) -->
          <div class="p-4 sm:p-6 bg-[#1e293b] border border-slate-800 rounded-2xl shadow-lg space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-slate-300 mb-1 flex items-center gap-2">
              <span>🧠</span> {{ t('ai_title') }}
            </h3>
            <div class="p-4 bg-purple-500/5 rounded-xl border border-purple-500/10 text-[11px] sm:text-xs text-slate-300 leading-relaxed space-y-3">
              <p>{{ t('ai_placeholder') }}</p>
              
              <!-- تذييل الفحص القانوني المسبق لتقارير الذكاء الاصطناعي للمرحلة الثانية -->
              <div class="pt-2.5 border-t border-purple-500/10 text-[10px] sm:text-[11px] text-purple-400/80 italic font-mono">
                {{ t('ai_footer_note') }}
              </div>
            </div>
          </div>
        </div>

        <!-- العمود الأصغر (1/3 من المساحة): الإحصائيات الفورية والمؤشرات -->
        <div class="space-y-6">
          <div class="p-4 sm:p-6 bg-[#1e293b] border border-slate-800 rounded-2xl shadow-lg space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-slate-300 border-b border-slate-800 pb-3">
              {{ t('market_data') }}
            </h3>
            
            <!-- السعر الحالي -->
            <div class="flex justify-between items-center gap-4">
              <span class="text-xs text-slate-400">{{ t('current_price') }}</span>
              <span class="font-mono text-white font-bold text-sm sm:text-lg">${{ Number(crypto.current_price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
            </div>

            <!-- التغير اليومي -->
            <div class="flex justify-between items-center gap-4">
              <span class="text-xs text-slate-400">{{ t('change_24h') }}</span>
              <span class="font-mono text-xs sm:text-sm font-bold text-emerald-400" :class="crypto.change_24h >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                {{ crypto.change_24h >= 0 ? '▲ +' : '▼ ' }} {{ Math.abs(crypto.change_24h) }}%
              </span>
            </div>

            <!-- حجم التداول -->
            <div class="flex justify-between items-center gap-4">
              <span class="text-xs text-slate-400">{{ t('volume_24h') }}</span>
              <span class="font-mono text-xs sm:text-sm text-slate-200 font-medium truncate max-w-[150px] sm:max-w-none">${{ Number(crypto.volume_24h).toLocaleString('en-US') }}</span>
            </div>
          </div>

          <!-- بطاقة مؤشرات الدعم والمقاومة المؤقتة -->
          <div class="p-4 sm:p-6 bg-[#1e293b] border border-slate-800 rounded-2xl shadow-lg">
            <h3 class="text-xs sm:text-sm font-bold text-slate-300 mb-3">{{ t('technical_indicators') }}</h3>
            <div class="space-y-2 text-[11px] sm:text-xs">
              <div class="p-2 bg-[#0f172a]/40 rounded text-slate-400 font-mono truncate">
                <span class="text-emerald-400">RSI (14): 58.4 (Neutral)</span>
              </div>
              <div class="p-2 bg-[#0f172a]/40 rounded text-slate-400 font-mono truncate">
                <span class="text-slate-200">MA (50): ${{ Number(crypto.current_price * 0.98).toLocaleString('en-US', { maximumFractionDigits: 2 }) }}</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { computed } from 'vue';
import { router, usePage, Head } from '@inertiajs/vue3';

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

// قاموس الترجمة المركزي والشامل لشاشة تفاصيل التحليل المحدث قانونياً وبصرياً
const translations = {
  ar: {
    sub_title: 'شاشة معالجة البيانات المتقدمة والمحاكاة الفنية للأسواق',
    back_btn: 'العودة للوحة التحكم',
    chart_title: 'حركة وحجم الأسعار التاريخية والإحصائية',
    chart_runtime: 'الرسوم البيانية (Simulation Mode)',
    chart_placeholder: 'المخططات البيانية المتقدمة للتحليل (Charts) سيتم حقنها هنا في البند القادم لقراءة النبض الإحصائي.',
    ai_title: 'تحليل المشاعر والذكاء الاصطناعي (AI Sentiment Hub)',
    ai_placeholder: 'هذه المساحة مخصصة في المرحلة الثانية لقراءة وتلخيص التقارير الإخبارية فوراً عبر الـ AI، واستخراج نبض السوق العام للعملة (إيجابي / سلبي / محايد) بناءً على المعطيات الإحصائية الحالية.',
    market_data: 'بيانات المحاكاة الحالية',
    current_price: 'السعر الحالي المباشر',
    change_24h: 'معدل التغير (24 ساعة)',
    volume_24h: 'حجم التداول اليومي الإحصائي',
    technical_indicators: 'المؤشرات الفنية المقترحة للمحاكاة (Phase 3)',
    page_notice_title: 'تنبيه تحليل ومحاكاة الأسعار',
    page_notice_body: 'المعطيات التحليلية المعروضة يتم توليدها بناءً على نماذج البيانات التاريخية والمؤشرات الفنية الثابتة. هذه محاكاة بيانية للمؤشرات وليست توقعات مالية أو فوركس. استخدم هذه البيانات كلياً كمورد تعليمي لفهم اتجاهات حركات الأسواق العالمية.',
    ai_footer_note: 'ملاحظة قانونية: هذا التقرير يُنشأ آلياً لأغراض التقييم والتحليل الإحصائي فقط، ولا يعتبر تأييداً أو نصيحة استثمارية للتداول بأي أصل رقمي.'
  },
  en: {
    sub_title: 'Advanced Analytics Screen & Market Technical Simulation',
    back_btn: 'Back to Dashboard',
    chart_title: 'Historical & Statistical Price Movement',
    chart_runtime: 'Interactive Charts (Simulation Mode)',
    chart_placeholder: 'Advanced analytical charts will be injected here in the upcoming stage to track statistical momentum.',
    ai_title: 'AI Sentiment Analysis Hub',
    ai_placeholder: 'This space is reserved in Phase 2 for real-time news aggregation and AI summarization, extracting overall market sentiment (Bullish / Bearish / Neutral) based on current statistical inputs.',
    market_data: 'Current Simulation Data',
    current_price: 'Live Current Price',
    change_24h: 'Change Rate (24H)',
    volume_24h: 'Statistical Daily Trading Volume',
    technical_indicators: 'Suggested Technical Indicators for Simulation (Phase 3)',
    page_notice_title: 'Market Analysis Notice',
    page_notice_body: 'The following metrics are generated based on historical data models and static technical indicators. These are informational simulations, not financial forecasts or recommendations. Use this data strictly as an educational learning resource to understand market trends.',
    ai_footer_note: 'Note: This insight is automatically generated for analytical evaluation. It is not an endorsement or financial advice to trade any asset.'
  }
};

// دالة جلب المصطلحات المترجمة
const t = (key) => {
  return translations[locale.value][key] || key;
};
</script>