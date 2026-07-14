<template>
  <!-- التحكم الديناميكي باتجاه المنصة بالكامل بناءً على اللغة الحالية -->
  <div :dir="locale === 'ar' ? 'rtl' : 'ltr'" class="min-h-screen bg-[#0f172a] text-slate-100 flex antialiased transition-all duration-300">
    
    <!-- ========================================== -->
    <!-- 1️⃣ الشريط الجانبي للأجهزة الكبيرة (Desktop Sidebar) -->
    <!-- ========================================== -->
    <aside 
      :class="[
        'w-64 bg-[#1e293b] flex flex-col justify-between p-4 hidden md:flex flex-shrink-0 transition-all',
        locale === 'ar' ? 'border-l border-slate-800' : 'border-r border-slate-800'
      ]"
    >
      <div>
        <!-- شعار المنصة -->
        <div class="flex items-center gap-3 px-2 py-4 mb-6 border-b border-slate-800">
          <span class="h-3 w-3 rounded-full bg-emerald-400 animate-pulse"></span>
          <h2 class="text-xl font-bold text-white tracking-wide font-mono">CryptoHub</h2>
        </div>

        <!-- قوائم التنقل المترجمة ديناميكياً والمربوطة بـ Inertia Link -->
        <nav class="space-y-1">
          <!-- لوحة التحكم -->
          <Link 
            href="/dashboard" 
            :class="[
              'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all',
              page.url === '/dashboard' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'
            ]"
          >
            <span>📊</span> 
            <span>{{ locale === 'ar' ? 'لوحة التحكم' : 'Dashboard' }}</span>
          </Link>

          <!-- أسعار العملات -->
          <Link 
            href="/prices" 
            :class="[
              'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all',
              page.url === '/prices' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'
            ]"
          >
            <span>🪙</span> {{ t('crypto_prices') }}
          </Link>

          <!-- أخبار الكريبتو -->
          <Link 
            href="/news" 
            :class="[
              'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all',
              page.url === '/news' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'
            ]"
          >
            <span>📰</span> {{ t('crypto_news') }}
          </Link>
        </nav>
      </div>

      <!-- معلومات المستخدم أسفل الشريط الجانبي مع تعزيز تباين الألوان -->
      <div class="p-2.5 bg-[#0f172a]/60 border border-slate-800 rounded-xl flex items-center gap-3">
        <div class="h-9 w-9 rounded-full bg-emerald-500 flex items-center justify-center font-bold text-slate-900 text-sm flex-shrink-0 shadow-inner">
          DK
        </div>
        <div class="min-w-0" :class="locale === 'ar' ? 'text-right' : 'text-left'">
          <p class="text-xs font-bold text-white truncate">{{ t('developer') }}</p>
          <span class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping"></span> {{ t('connected') }}
          </span>
        </div>
      </div>
    </aside>

    <!-- ========================================== -->
    <!-- 2️⃣ القائمة الجانبية الطائرة للهواتف (Mobile Drawer) -->
    <!-- ========================================== -->
    <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 md:hidden flex">
      <!-- الخلفية المظلمة الشفافة عند فتح القائمة -->
      <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="isMobileMenuOpen = false"></div>
      
      <!-- جسم القائمة المستجيب للاتجاهات -->
      <aside 
        :class="[
          'relative w-64 bg-[#1e293b] h-full flex flex-col justify-between p-4 z-10 shadow-2xl transition-transform duration-300',
          locale === 'ar' ? 'mr-auto border-l border-slate-800' : 'ml-auto border-r border-slate-800'
        ]"
      >
        <div>
          <!-- رأس القائمة الطائرة وزر الإغلاق -->
          <div class="flex items-center justify-between px-2 py-4 mb-6 border-b border-slate-800">
            <div class="flex items-center gap-3">
              <span class="h-3 w-3 rounded-full bg-emerald-400 animate-pulse"></span>
              <h2 class="text-xl font-bold text-white tracking-wide font-mono">CryptoHub</h2>
            </div>
            <button @click="isMobileMenuOpen = false" class="text-slate-400 hover:text-white p-1 text-lg cursor-pointer">
              ✕
            </button>
          </div>

          <!-- الروابط الداخلية للجوال -->
          <nav class="space-y-1">
            <Link 
              href="/dashboard" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all',
                page.url === '/dashboard' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'
              ]"
            >
              <span>📊</span> <span>{{ locale === 'ar' ? 'لوحة التحكم' : 'Dashboard' }}</span>
            </Link>

            <Link 
              href="/prices" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all',
                page.url === '/prices' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'
              ]"
            >
              <span>🪙</span> {{ t('crypto_prices') }}
            </Link>

            <Link 
              href="/news" 
              @click="isMobileMenuOpen = false"
              :class="[
                'flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all',
                page.url === '/news' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white border border-transparent'
              ]"
            >
              <span>📰</span> {{ t('crypto_news') }}
            </Link>
          </nav>
        </div>

        <!-- المطور أسفل قائمة الجوال -->
        <div class="p-2.5 bg-[#0f172a]/60 border border-slate-800 rounded-xl flex items-center gap-3">
          <div class="h-9 w-9 rounded-full bg-emerald-500 flex items-center justify-center font-bold text-slate-900 text-sm flex-shrink-0">
            DK
          </div>
          <div class="min-w-0" :class="locale === 'ar' ? 'text-right' : 'text-left'">
            <p class="text-xs font-bold text-white truncate">{{ t('developer') }}</p>
            <span class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1 mt-0.5">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> {{ t('connected') }}
            </span>
          </div>
        </div>
      </aside>
    </div>

    <!-- ========================================== -->
    <!-- 3️⃣ منطقة المحتوى الرئيسية والشريط العلوي -->
    <!-- ========================================== -->
    <div class="flex-1 flex flex-col min-w-0">
      
      <!-- الشريط العلوي (Navbar) بمحاذاة عمودية صارمة وموسطة لكافة العناصر -->
      <header class="h-16 bg-[#1e293b] border-b border-slate-800 flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
        
        <!-- الجهة اليسرى/اليمنى حسب الاتجاه: زر الهامبرغر + العنوان التعليمي المحدث -->
        <div class="flex items-center gap-3 min-w-0">
          <!-- زر الجوال (Hamburger) يظهر فقط على الهواتف md:hidden -->
          <button 
            @click="isMobileMenuOpen = true" 
            class="md:hidden p-2 -mx-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors cursor-pointer flex-shrink-0 flex items-center justify-center"
          >
            ☰
          </button>
          <h1 class="text-xs sm:text-sm font-bold text-white truncate">{{ t('panel_title') }}</h1>
        </div>
        
        <!-- أدوات الـ Navbar: زر تبديل اللغة + التوقيت موسطة بالملّي عمودياً -->
        <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
          
          <!-- زر التبديل العالمي الفخم للغات -->
          <a 
            :href="`/lang/${locale === 'ar' ? 'en' : 'ar'}`" 
            class="h-9 px-2.5 rounded-lg border border-slate-800 text-[11px] sm:text-xs font-semibold bg-[#0f172a] text-slate-300 hover:text-white hover:border-slate-700 transition-colors flex items-center gap-1 active:scale-95 shadow-md justify-center"
          >
            <span>🌐</span> {{ locale === 'ar' ? 'En' : 'العربية' }}
          </a>

          <!-- التوقيت المباشر -->
          <div class="h-9 bg-[#0f172a] px-2.5 rounded-lg border border-slate-800 text-[11px] sm:text-xs font-mono text-slate-400 flex items-center justify-center">
            UTC: 2026
          </div>
        </div>
      </header>

      <!-- مساحة عرض محتوى الصفحات الديناميكي (Slot) متجاوبة الحواف -->
      <main class="flex-1 overflow-y-auto p-4 sm:p-6">
        <slot />
      </main>

    </div>
  </div>
</template>

<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// متغير للتحكم بحالة فتح وإغلاق قائمة الهواتف الذكية
const isMobileMenuOpen = ref(false);

// جلب بيانات الصفحة الحالية للتعرف على اللغة النشطة والمسار الحالي لـ Active State لتوحيدها هندسياً
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// القاموس المركزي الموحد المحدث بمصطلحات تعليمية تحليلية آمنة لحمايتك بالكامل
const translations = {
  ar: {
    dashboard: 'لوحة التحكم',
    crypto_news: 'أخبار الكريبتو',
    crypto_prices: 'أسعار العملات',
    my_wallet: 'محفظتي الماليّة',
    settings: 'الإعدادات',
    developer: 'المطور الذكي',
    connected: 'متصل',
    panel_title: 'لوحة التحليلات والبيانات التعليمية للأسواق',
  },
  en: {
    dashboard: 'Dashboard',
    crypto_news: 'Crypto News',
    crypto_prices: 'Crypto Prices',
    my_wallet: 'My Wallet',
    settings: 'Settings',
    developer: 'Smart Developer',
    connected: 'Connected',
    panel_title: 'Educational Analytics & Market Data Panel',
  }
};

// دالة الترجمة الذكية الفورية
const t = (key) => {
  return translations[locale.value][key] || key;
};
</script>