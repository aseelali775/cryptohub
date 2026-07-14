<template>
  <!-- 
    تغليف التطبيق بالكامل. 
    ملاحظة: كلاس الـ 'dark' يتم حقنه الآن في الـ <html> برمجياً ليعمل في كل المكونات
  -->
  <div :dir="locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="min-h-screen bg-slate-50 dark:bg-[#0b1121] text-slate-800 dark:text-slate-100 flex flex-col antialiased font-sans transition-colors duration-300 selection:bg-emerald-500 selection:text-white">
      
      <!-- ========================================== -->
      <!-- 1. شريط التنقل العلوي (Navbar) -->
      <!-- ========================================== -->
      <header class="w-full bg-white dark:bg-[#0b1121] border-b border-gray-200 dark:border-slate-800/80 sticky top-0 z-50 transition-colors duration-300">
        <div class="max-w-[1440px] mx-auto h-20 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
          
          <!-- القسم الأول: الشعار (Logo) -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <div class="w-8 h-8 rounded bg-emerald-500 flex items-center justify-center transform rotate-45">
              <div class="w-3 h-3 bg-white rounded-full"></div>
            </div>
            <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white font-mono ms-1">
              CryptoHub
            </span>
          </div>

          <!-- القسم الثاني: روابط التنقل الرئيسية -->
          <nav class="hidden lg:flex items-center gap-8 h-full">
            <Link href="/" class="h-full flex items-center text-sm font-bold text-emerald-600 dark:text-emerald-400 border-b-2 border-emerald-500">
              {{ t('navHome') }}
            </Link>
            <Link href="#" class="h-full flex items-center text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
              {{ t('navNews') }}
            </Link>
            <Link href="#" class="h-full flex items-center text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
              {{ t('navAnalytics') }}
            </Link>
            <Link href="/prices" class="h-full flex items-center text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
              {{ t('navPrices') }}
            </Link>
            <Link href="#" class="h-full flex items-center text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
              {{ t('navIndicators') }}
            </Link>
            <Link href="#" class="h-full flex items-center text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
              {{ t('navTools') }}
            </Link>
            <Link href="#" class="h-full flex items-center text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
              {{ t('navCalendar') }}
            </Link>
          </nav>

          <!-- القسم الثالث: الأدوات والأزرار -->
          <div class="flex items-center gap-3 sm:gap-5 flex-shrink-0">
            
            <!-- حقل البحث -->
            <div class="hidden md:flex relative group">
              <input 
                type="text" 
                :placeholder="t('searchPlaceholder')" 
                class="h-10 w-48 lg:w-64 bg-slate-100 dark:bg-[#151e32] border border-transparent dark:border-slate-800 rounded-full px-4 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all placeholder-slate-400 dark:placeholder-slate-500"
              >
              <div class="absolute inset-y-0 end-3 flex items-center pointer-events-none text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              </div>
            </div>

            <!-- زر تبديل اللغة -->
            <a :href="`/lang/${locale === 'ar' ? 'en' : 'ar'}`" class="text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-emerald-500 transition-colors px-1">
              {{ locale === 'ar' ? 'EN' : 'AR' }}
            </a>

            <!-- زر تبديل الوضع المظلم والمضيء -->
            <button @click="toggleTheme" class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
              <svg v-if="!isDarkMode" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </button>

            <!-- أزرار الدخول والتسجيل -->
            <div class="hidden sm:flex items-center gap-3">
              <Link href="/login" class="h-10 px-4 flex items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                {{ t('btnLogin') }}
              </Link>
              <Link href="/register" class="h-10 px-5 flex items-center justify-center rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold transition-all shadow-lg shadow-emerald-500/20">
                {{ t('btnSignup') }}
              </Link>
            </div>
            
          </div>
        </div>
      </header>

      <!-- ========================================== -->
      <!-- 2. منطقة المحتوى الديناميكية -->
      <!-- ========================================== -->
      <main class="flex-1 min-w-0 w-full relative">
        <slot />
      </main>

      <!-- ========================================== -->
      <!-- 3. التذييل (Footer) -->
      <!-- ========================================== -->
      <footer class="w-full bg-white dark:bg-[#0b1121] border-t border-slate-200 dark:border-slate-800/80 pt-16 pb-8 transition-colors duration-300">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          
          <div class="grid grid-cols-1 md:grid-cols-12 gap-10 items-center mb-12">
            
            <!-- الأيقونات الاجتماعية -->
            <div class="md:col-span-3 flex items-center gap-3">
              <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#151e32] flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-500 dark:hover:text-white transition-all">𝕏</a>
              <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#151e32] flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-500 dark:hover:text-white transition-all">✈️</a>
              <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#151e32] flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-500 dark:hover:text-white transition-all">▶️</a>
              <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#151e32] flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-500 dark:hover:text-white transition-all">f</a>
            </div>

            <!-- النشرة البريدية -->
            <div class="md:col-span-6 flex flex-col md:flex-row items-center justify-center gap-4 w-full">
              <div class="flex w-full max-w-md relative">
                <input 
                  type="email" 
                  :placeholder="t('newsletterPlaceholder')" 
                  class="w-full h-12 bg-slate-50 dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-full px-6 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:border-emerald-500/50 transition-all pe-32"
                >
                <button class="absolute inset-y-1 end-1 h-10 px-6 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold transition-all">
                  {{ t('btnSubscribe') }}
                </button>
              </div>
              <div class="text-center md:text-start rtl:md:text-right">
                <h4 class="text-sm font-bold text-slate-800 dark:text-white mb-1">{{ t('newsletterTitle') }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('newsletterDesc') }}</p>
              </div>
            </div>

          </div>

          <!-- خط فاصل -->
          <div class="w-full h-px bg-slate-200 dark:bg-slate-800/80 mb-8"></div>

          <!-- روابط الفوتر وحقوق النشر -->
          <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="flex flex-wrap justify-center items-center gap-6 text-sm font-medium text-slate-600 dark:text-slate-400">
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerAbout') }}</a>
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerDisclaimer') }}</a>
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerContact') }}</a>
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerTerms') }}</a>
            </div>
            
            <div class="text-xs text-slate-500 dark:text-slate-500 font-medium">
              {{ t('copyright') }}
            </div>
          </div>
          
          <!-- التنبيه القانوني المدمج -->
          <div class="mt-6 text-[10px] text-slate-400 dark:text-slate-600 text-center max-w-4xl mx-auto leading-relaxed">
            {{ t('legalText') }}
          </div>

        </div>
      </footer>

    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';

// 1. جلب بيانات الصفحة للتعرف على اللغة
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// 2. إدارة حالة الوضع المظلم/المضيء عالمياً
const isDarkMode = ref(true); // الوضع الافتراضي داكن

// دالة تحديث كلاس الـ HTML
const updateHtmlClass = () => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
  updateHtmlClass();
};

onMounted(() => {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    isDarkMode.value = savedTheme === 'dark';
  }
  // حقن الكلاس فور تحميل الصفحة
  updateHtmlClass();
});

// 3. قاموس الترجمة التفاعلي الخاص بالـ Layout
const translations = {
  ar: {
    navHome: "الرئيسية",
    navNews: "الأخبار",
    navAnalytics: "التحليلات",
    navPrices: "الأسعار",
    navIndicators: "المؤشرات",
    navTools: "الأدوات",
    navCalendar: "التقويم الاقتصادي",
    searchPlaceholder: "ابحث عن عملة أو خبر...",
    btnLogin: "تسجيل الدخول",
    btnSignup: "إنشاء حساب",
    newsletterPlaceholder: "أدخل بريدك الإلكتروني",
    btnSubscribe: "اشترك الآن",
    newsletterTitle: "اشترك في نشرتنا البريدية",
    newsletterDesc: "احصل على أهم الأخبار والتحليلات مباشرة في بريدك",
    footerAbout: "من نحن",
    footerDisclaimer: "إخلاء مسؤولية",
    footerContact: "اتصل بنا",
    footerTerms: "شروط الاستخدام",
    copyright: "جميع الحقوق محفوظة © 2026 CryptoHub.",
    legalText: "تنويه قانوني: هذه المنصة مخصصة للأغراض التعليمية والتحليلية فقط لمحاكاة اتجاهات السوق، ولا تقدم أي نصائح استثمارية أو مالية. تداول العملات الرقمية ينطوي على مخاطر عالية."
  },
  en: {
    navHome: "Home",
    navNews: "News",
    navAnalytics: "Analytics",
    navPrices: "Prices",
    navIndicators: "Indicators",
    navTools: "Tools",
    navCalendar: "Economic Calendar",
    searchPlaceholder: "Search crypto or news...",
    btnLogin: "Log In",
    btnSignup: "Sign Up",
    newsletterPlaceholder: "Enter your email",
    btnSubscribe: "Subscribe",
    newsletterTitle: "Subscribe to our Newsletter",
    newsletterDesc: "Get the latest news and analytics straight to your inbox",
    footerAbout: "About Us",
    footerDisclaimer: "Disclaimer",
    footerContact: "Contact Us",
    footerTerms: "Terms of Use",
    copyright: "All rights reserved © 2026 CryptoHub.",
    legalText: "Disclaimer: This platform is for educational and analytical purposes only, simulating market trends. It does not constitute financial advice. Crypto trading involves high risk."
  }
};

const t = (key) => translations[locale.value][key] || key;
</script>

<style>
/* تنعيم انتقال الألوان عالمياً عند تبديل الوضع */
html, body {
  transition: background-color 0.3s ease, color 0.3s ease;
}
</style>