<template>
  <div :dir="locale === 'ar' ? 'rtl' : 'ltr'">
    <div class="min-h-screen bg-slate-50 dark:bg-[#0b1121] text-slate-800 dark:text-slate-100 flex flex-col antialiased font-sans transition-colors duration-300 selection:bg-emerald-500 selection:text-white">
      
      <header class="w-full bg-white dark:bg-[#0b1121] border-b border-gray-200 dark:border-slate-800/80 sticky top-0 z-40 transition-colors duration-300">
        <div class="max-w-[1440px] mx-auto h-20 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
          
          <Link href="/" class="flex items-center gap-2 flex-shrink-0 cursor-pointer group">
            <div class="w-8 h-8 rounded bg-emerald-500 flex items-center justify-center transform rotate-45 group-hover:scale-105 transition-transform">
              <div class="w-3 h-3 bg-white rounded-full"></div>
            </div>
            <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white font-mono ms-1 group-hover:text-emerald-500 transition-colors">
              AQL CRYPTO
            </span>
          </Link>

          <nav class="hidden lg:flex items-center gap-8 h-full">
            <Link href="/" class="h-full flex items-center text-sm transition-colors border-b-2" :class="$page.url === '/' ? 'font-bold text-emerald-600 dark:text-emerald-400 border-emerald-500' : 'font-medium text-slate-600 dark:text-slate-400 border-transparent hover:text-slate-900 dark:hover:text-white'">
              {{ t('navHome') }}
            </Link>

            <Link href="/prices" class="h-full flex items-center text-sm transition-colors border-b-2" :class="$page.url.startsWith('/prices') || $page.url.startsWith('/crypto') ? 'font-bold text-emerald-600 dark:text-emerald-400 border-emerald-500' : 'font-medium text-slate-600 dark:text-slate-400 border-transparent hover:text-slate-900 dark:hover:text-white'">
              {{ t('navPrices') }}
            </Link>

            <Link href="/news" class="h-full flex items-center text-sm transition-colors border-b-2" :class="$page.url.startsWith('/news') ? 'font-bold text-emerald-600 dark:text-emerald-400 border-emerald-500' : 'font-medium text-slate-600 dark:text-slate-400 border-transparent hover:text-slate-900 dark:hover:text-white'">
              {{ t('navNews') }}
            </Link>

            <Link href="/ai-market" class="h-full flex items-center gap-2 text-sm transition-colors border-b-2 group" :class="$page.url.startsWith('/ai-market') ? 'font-bold text-indigo-600 dark:text-indigo-400 border-indigo-500' : 'font-medium text-slate-600 dark:text-slate-400 border-transparent hover:text-indigo-600 dark:hover:text-indigo-400'">
              <span>{{ t('navAiMarket') }}</span>
              <span class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-[10px] font-black font-mono px-1.5 py-0.5 rounded-md uppercase tracking-wider shadow-sm group-hover:animate-pulse">
                AI
              </span>
            </Link>
          </nav>

          <div class="flex items-center gap-3 sm:gap-5 flex-shrink-0">
            
            <button @click="mobileMenuOpen = true" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
              </svg>
            </button>

            <div class="hidden md:flex relative group opacity-60 cursor-not-allowed">
              <input type="text" :placeholder="t('searchPlaceholder')" disabled class="h-10 w-48 lg:w-64 bg-slate-100 dark:bg-[#151e32] border border-transparent dark:border-slate-800 rounded-full px-4 text-sm text-slate-700 dark:text-slate-200 cursor-not-allowed placeholder-slate-500">
              <div class="absolute inset-y-0 end-3 flex items-center pointer-events-none text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              </div>
            </div>

            <a :href="`/lang/${locale === 'ar' ? 'en' : 'ar'}`" class="text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-emerald-500 transition-colors px-1">
              {{ locale === 'ar' ? 'EN' : 'AR' }}
            </a>

            <button @click="toggleTheme" class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
              <svg v-if="!isDarkMode" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </button>
            
          </div>
        </div>
      </header>

      <Teleport to="body">
        <transition 
          enter-active-class="transition-opacity duration-300" 
          enter-from-class="opacity-0" 
          enter-to-class="opacity-100" 
          leave-active-class="transition-opacity duration-300" 
          leave-from-class="opacity-100" 
          leave-to-class="opacity-0"
        >
          <div v-if="mobileMenuOpen" @click="mobileMenuOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 lg:hidden"></div>
        </transition>

        <transition 
          enter-active-class="transition-transform duration-300 ease-out" 
          :enter-from-class="locale === 'ar' ? 'translate-x-full' : '-translate-x-full'" 
          enter-to-class="translate-x-0" 
          leave-active-class="transition-transform duration-300 ease-in" 
          leave-from-class="translate-x-0" 
          :leave-to-class="locale === 'ar' ? 'translate-x-full' : '-translate-x-full'"
        >
          <div 
            v-if="mobileMenuOpen" 
            class="fixed top-0 bottom-0 z-[60] w-[280px] bg-white dark:bg-[#0b1121] shadow-2xl flex flex-col lg:hidden"
            :class="locale === 'ar' ? 'right-0 left-auto border-l border-slate-200 dark:border-slate-800/80' : 'left-0 right-auto border-r border-slate-200 dark:border-slate-800/80'"
            :dir="locale === 'ar' ? 'rtl' : 'ltr'"
          >
            <div class="h-20 px-6 flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 shrink-0">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded bg-emerald-500 flex items-center justify-center transform rotate-45">
                  <div class="w-2 h-2 bg-white rounded-full"></div>
                </div>
                <span class="text-lg font-black tracking-tight text-slate-900 dark:text-white font-mono ms-1">
                  Aql Crypto
                </span>
              </div>
              <button @click="mobileMenuOpen = false" class="p-2 -me-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
              <Link href="/" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" :class="{ 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': $page.url === '/' }">
                {{ t('navHome') }}
              </Link>
              
              <Link href="/prices" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" :class="{ 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': $page.url.startsWith('/prices') || $page.url.startsWith('/crypto') }">
                {{ t('navPrices') }}
              </Link>
              
              <Link href="/news" @click="mobileMenuOpen = false" class="block px-4 py-3 rounded-xl text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" :class="{ 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400': $page.url.startsWith('/news') }">
                {{ t('navNews') }}
              </Link>
              
              <Link href="/ai-market" @click="mobileMenuOpen = false" class="flex items-center justify-between px-4 py-3 rounded-xl text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" :class="{ 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400': $page.url.startsWith('/ai-market') }">
                <span class="flex items-center gap-2">
                  <span>{{ t('navAiMarket') }}</span>
                </span>
                <span class="bg-indigo-500 text-white text-[10px] px-2 py-0.5 rounded uppercase tracking-wider font-mono shadow-sm">
                  AI
                </span>
              </Link>
            </div>
            
            <div class="p-6 border-t border-slate-100 dark:border-slate-800/80 shrink-0">
              <p class="text-xs text-center text-slate-400 font-medium uppercase tracking-widest font-mono">
                Aql Crypto v1.0
              </p>
            </div>
          </div>
        </transition>
      </Teleport>

      <main class="flex-1 min-w-0 w-full relative">
        <slot />
      </main>

      <footer class="w-full bg-white dark:bg-[#0b1121] border-t border-slate-200 dark:border-slate-800/80 pt-16 pb-8 transition-colors duration-300">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
          
          <div class="grid grid-cols-1 md:grid-cols-12 gap-10 items-center mb-12">
            <div class="md:col-span-3 flex items-center gap-3">
              <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#151e32] flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-emerald-500 hover:text-white transition-all">𝕏</a>
              <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#151e32] flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-emerald-500 hover:text-white transition-all">✈️</a>
              <a href="#" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#151e32] flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-emerald-500 hover:text-white transition-all">▶️</a>
            </div>

            <div class="md:col-span-6 flex flex-col md:flex-row items-center justify-center gap-4 w-full">
              <div class="flex w-full max-w-md relative opacity-60">
                <input type="email" disabled :placeholder="t('newsletterPlaceholder')" class="w-full h-12 bg-slate-50 dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-full px-6 text-sm text-slate-700 dark:text-slate-200 cursor-not-allowed pe-32">
                <button disabled class="absolute inset-y-1 end-1 h-10 px-6 rounded-full bg-emerald-500 text-white text-sm font-semibold cursor-not-allowed">
                  {{ t('btnSubscribe') }}
                </button>
              </div>
              <div class="text-center md:text-start rtl:md:text-right">
                <h4 class="text-sm font-bold text-slate-800 dark:text-white mb-1">{{ t('newsletterTitle') }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('newsletterDesc') }}</p>
              </div>
            </div>
          </div>

          <div class="w-full h-px bg-slate-200 dark:bg-slate-800/80 mb-8"></div>

          <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            <div class="flex flex-wrap justify-center items-center gap-6 text-sm font-medium text-slate-600 dark:text-slate-400">
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerAbout') }}</a>
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerDisclaimer') }}</a>
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerContact') }}</a>
              <a href="#" class="hover:text-emerald-500 transition-colors">{{ t('footerTerms') }}</a>
            </div>
            <div class="text-xs text-slate-500 font-medium">
              {{ t('copyright') }}
            </div>
          </div>
          
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

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// حالة القائمة الجانبية للهاتف
const mobileMenuOpen = ref(false);

// إدارة حالة الوضع المظلم/المضيء
const isDarkMode = ref(true);

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
  updateHtmlClass();
});

// الترجمات الموحدة
const translations = {
  ar: {
    navHome: "الرئيسية",
    navPrices: "الأسعار",
    navNews: "الأخبار",
    navAiMarket: "ذكاء السوق",
    searchPlaceholder: "البحث العالمي (قريباً...)",
    newsletterPlaceholder: "أدخل بريدك الإلكتروني",
    btnSubscribe: "قريباً",
    newsletterTitle: "النشرة البريدية (قريباً)",
    newsletterDesc: "سيتم تفعيل الاشتراك قريباً لتقديم أهم التحليلات.",
    footerAbout: "من نحن",
    footerDisclaimer: "إخلاء مسؤولية",
    footerContact: "اتصل بنا",
    footerTerms: "شروط الاستخدام",
    copyright: "جميع الحقوق محفوظة © 2026 Aql Crypto.",
    legalText: "تنويه قانوني: هذه المنصة مخصصة للأغراض التعليمية والتحليلية فقط لمحاكاة اتجاهات السوق، ولا تقدم أي نصائح استثمارية أو مالية. تداول العملات الرقمية ينطوي على مخاطر عالية."
  },
  en: {
    navHome: "Home",
    navPrices: "Prices",
    navNews: "News",
    navAiMarket: "AI Market",
    searchPlaceholder: "Global Search (Coming Soon...)",
    newsletterPlaceholder: "Enter your email",
    btnSubscribe: "Soon",
    newsletterTitle: "Newsletter (Soon)",
    newsletterDesc: "Subscription will be activated soon for top analytics.",
    footerAbout: "About Us",
    footerDisclaimer: "Disclaimer",
    footerContact: "Contact Us",
    footerTerms: "Terms of Use",
    copyright: "All rights reserved © 2026 Aql Crypto.",
    legalText: "Disclaimer: This platform is for educational and analytical purposes only, simulating market trends. It does not constitute financial advice. Crypto trading involves high risk."
  }
};

const t = (key) => translations[locale.value][key] || key;
</script>

<style>
html, body {
  transition: background-color 0.3s ease, color 0.3s ease;
}
</style>