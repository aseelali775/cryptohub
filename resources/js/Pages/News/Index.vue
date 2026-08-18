<template>
  <HomeLayout>
    <Head>
      <title>
        {{ locale === 'ar' ? 'آخر أخبار الكريبتو وتلخيصات الذكاء الاصطناعي | Aql Crypto' : 'Latest Crypto News & AI Summaries | Aql Crypto' }}
      </title>
      <meta 
        name="description" 
        :content="locale === 'ar' ? 'تغطية شاملة ومستمرة لأحداث سوق العملات الرقمية العالمية مع تحليل ذكي لأهم الأنباء.' : 'Comprehensive global coverage of crypto events with smart AI analysis.'" />
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16 space-y-8" :class="locale === 'ar' ? 'text-right' : 'text-left'">

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-[#1e293b] p-5 sm:p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
              {{ locale === 'ar' ? 'غرفة الأخبار والتحليلات' : 'News & Analysis Room' }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1.5 font-medium">
              {{ locale === 'ar' ? 'تغطية شاملة لأحداث السوق العالمية مع تحليلات ذكية' : 'Comprehensive coverage of global market events with smart analysis' }}
            </p>
          </div>
          <span class="px-3.5 py-1.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] sm:text-xs rounded-xl font-bold animate-pulse self-start sm:self-auto flex-shrink-0">
            {{ locale === 'ar' ? 'تحديث تلقائي مدعوم بالـ AI' : 'Live AI Feed' }}
          </span>
        </div>

        <div class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-4" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
          
          <div class="flex-1">
            <input 
              v-model="form.search" 
              type="text" 
              :placeholder="locale === 'ar' ? 'ابحث في الأخبار والتحليلات...' : 'Search news...'"
              class="w-full bg-slate-50 dark:bg-[#0f172a] border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-xl px-4 py-3 focus:outline-none focus:border-emerald-500 transition-colors"
            >
          </div>

          <div class="w-full md:w-48">
            <select v-model="form.category" class="w-full bg-slate-50 dark:bg-[#0f172a] border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-xl px-4 py-3 focus:outline-none focus:border-emerald-500 appearance-none cursor-pointer">
              <option value="">{{ locale === 'ar' ? 'جميع التصنيفات' : 'All Categories' }}</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>

          <div class="w-full md:w-40">
            <select v-model="form.sentiment" class="w-full bg-slate-50 dark:bg-[#0f172a] border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-xl px-4 py-3 focus:outline-none focus:border-emerald-500 appearance-none cursor-pointer">
              <option value="">{{ locale === 'ar' ? 'مشاعر السوق' : 'All Sentiments' }}</option>
              <option value="Bullish">🟢 {{ locale === 'ar' ? 'صعودي' : 'Bullish' }}</option>
              <option value="Bearish">🔴 {{ locale === 'ar' ? 'هبوطي' : 'Bearish' }}</option>
              <option value="Neutral">⚪ {{ locale === 'ar' ? 'محايد' : 'Neutral' }}</option>
            </select>
          </div>

          <div class="w-full md:w-48">
            <input 
              v-model="form.date" 
              type="date" 
              class="w-full bg-slate-50 dark:bg-[#0f172a] border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-xl px-4 py-3 focus:outline-none focus:border-emerald-500 cursor-pointer"
            >
          </div>

          <button @click="clearFilters" v-if="hasFilters" class="px-4 py-3 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 font-bold text-sm hover:bg-red-500/20 transition-colors whitespace-nowrap">
            {{ locale === 'ar' ? 'مسح الفلاتر ✖' : 'Clear ✖' }}
          </button>
        </div>

        <div v-if="!newsFeed?.data || newsFeed.data.length === 0" class="py-20 text-center bg-white dark:bg-[#1e293b] rounded-3xl border border-slate-200 dark:border-slate-800">
          <div class="text-6xl mb-4">📭</div>
          <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ locale === 'ar' ? 'لا توجد أخبار تطابق بحثك' : 'No news found' }}</h3>
          <p class="text-slate-500 dark:text-slate-400">{{ locale === 'ar' ? 'حاول تغيير كلمات البحث أو مسح الفلاتر لعرض كل الأخبار.' : 'Try changing your search terms or clearing filters.' }}</p>
          <button @click="clearFilters" class="mt-6 px-6 py-2 rounded-lg bg-emerald-500 text-white font-bold hover:bg-emerald-600 transition-colors">
             {{ locale === 'ar' ? 'عرض جميع الأخبار' : 'Show All News' }}
          </button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div 
            v-for="item in newsFeed.data" 
            :key="item.id" 
            class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-emerald-500/50 transition-all flex flex-col group cursor-pointer"
          >
            <div class="h-48 sm:h-52 overflow-hidden relative bg-slate-900 flex-shrink-0 flex items-center justify-center">
              <img 
                v-if="item.image_url && !brokenImages.has(item.id)" 
                :src="item.image_url" 
                @error="handleImageError(item.id)" 
                class="w-full h-full transition-transform duration-500 group-hover:scale-105 object-cover absolute inset-0"
                :alt="item.translations[locale === 'ar' ? 'ar' : 'en'].title" 
              />
              <div v-else class="absolute inset-0 w-full h-full bg-gradient-to-br from-slate-800 to-[#0b1121] flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
                <span class="text-6xl opacity-20 grayscale filter drop-shadow-md">📰</span>
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/20 to-transparent opacity-80 pointer-events-none"></div>

              <span v-if="item.sentiment === 'Bullish'" class="absolute top-3 left-3 bg-green-500/90 text-white text-[10px] px-2 py-1 rounded font-bold backdrop-blur-sm z-10">🟢 {{ locale === 'ar' ? 'صعودي' : 'Bullish' }}</span>
              <span v-else-if="item.sentiment === 'Bearish'" class="absolute top-3 left-3 bg-red-500/90 text-white text-[10px] px-2 py-1 rounded font-bold backdrop-blur-sm z-10">🔴 {{ locale === 'ar' ? 'هبوطي' : 'Bearish' }}</span>
              <span v-else-if="item.sentiment === 'Neutral'" class="absolute top-3 left-3 bg-slate-500/90 text-white text-[10px] px-2 py-1 rounded font-bold backdrop-blur-sm z-10">⚪ {{ locale === 'ar' ? 'محايد' : 'Neutral' }}</span>

              <span class="absolute bottom-3 right-3 bg-[#0f172a]/80 text-slate-200 text-[10px] px-2.5 py-1.5 rounded font-bold backdrop-blur-sm border border-white/10 z-10 pointer-events-none">
                {{ item.source || 'Aql Crypto' }}
              </span>
            </div>

            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
              <div class="space-y-3 min-w-0">
                <span class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-mono font-semibold block" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
                  {{ item.date || (locale === 'ar' ? 'منذ قليل' : 'Just now') }}
                </span>

                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors leading-snug break-words line-clamp-2" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
                  {{ item?.translations?.[locale === 'ar' ? 'ar' : 'en']?.title }}
                </h3>

                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-3 break-words font-medium" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
                  {{ locale === 'ar' && item.ai_processed ? item.translations.ar.summary : item.translations[locale === 'ar' ? 'ar' : 'en'].content }}
                </p>
              </div>

              <div class="pt-4 border-t border-slate-100 dark:border-slate-800/60 flex justify-between items-center gap-2" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
                <div class="flex items-center gap-1.5 overflow-hidden">
                  <span class="text-[10px] px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-mono font-bold whitespace-nowrap">#{{ item.category || 'Crypto' }}</span>
                  <span v-if="item.impact_score" class="text-[10px] px-1.5 py-1 rounded bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono font-bold whitespace-nowrap" :title="locale === 'ar' ? 'درجة التأثير' : 'Impact Score'">⚡ {{ item.impact_score }}/10</span>
                </div>
                <Link :href="buildNewsUrl(item)" class="text-xs font-bold text-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors flex items-center gap-1 cursor-pointer flex-shrink-0">
                  <span>{{ locale === 'ar' ? 'التفاصيل ←' : 'Read More →' }}</span>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div v-if="newsFeed?.links && newsFeed.links.length > 3" class="flex flex-wrap justify-center items-center gap-2 mt-12 pt-8 border-t border-slate-200 dark:border-slate-800" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
          <component
            :is="link.url ? Link : 'span'"
            v-for="(link, index) in newsFeed.links"
            :key="index"
            :href="link.url"
            v-html="link.label"
            class="px-4 py-2 rounded-xl text-sm font-bold transition-all border"
            :class="{
              'bg-emerald-500 text-white border-emerald-500 shadow-md': link.active,
              'bg-white dark:bg-[#151e32] text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-emerald-500 hover:text-emerald-500 cursor-pointer': link.url && !link.active,
              'bg-slate-50 dark:bg-slate-800/50 text-slate-400 dark:text-slate-600 border-transparent cursor-not-allowed': !link.url
            }"
          />
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Link, usePage, Head, router } from '@inertiajs/vue3';
import { computed, ref, reactive, watch } from 'vue'; 

const props = defineProps({
  newsFeed: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) }
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// دالة حساب تاريخ اليوم بتوقيت الجهاز المحتسب (YYYY-MM-DD)
const getTodayDate = () => {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// معالجة الصور المكسورة
const brokenImages = ref(new Set());
const handleImageError = (id) => {
  brokenImages.value.add(id);
};

// التصنيفات المتاحة
const categories = ['Bitcoin', 'Ethereum', 'Regulation', 'DeFi', 'NFT', 'Mining', 'Market', 'Security', 'Blockchain'];

// حالة الفلاتر (تلقائياً تأخذ التاريخ المحدد أو تاريخ اليوم الافتراضي)
const form = reactive({
  search: props.filters.search || '',
  category: props.filters.category || '',
  sentiment: props.filters.sentiment || '',
  date: props.filters.date || getTodayDate(), // 🟢 هنا يتم عرض تاريخ اليوم تلقائياً
});

// التحقق من وجود فلاتر نَشِطة
const hasFilters = computed(() => {
  return form.search !== '' || form.category !== '' || form.sentiment !== '' || form.date !== '';
});

// بناء رابط الخبر بشكل سليم مع الـ Slug
const buildNewsUrl = (item) => {
  let cleanSlug = item.slug || '';
  if (cleanSlug && cleanSlug.endsWith(`-${item.id}`)) {
    cleanSlug = cleanSlug.replace(new RegExp(`-${item.id}$`), '');
  }
  return cleanSlug ? `/news/${item.id}-${cleanSlug}` : `/news/${item.id}`;
};

// الاستماع للتغييرات وإرسال الطلب تلقائياً
let timeout = null;
watch(form, (newVal) => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    router.get('/news', newVal, { preserveState: true, preserveScroll: true });
  }, 400);
}, { deep: true });

// دالة مسح الفلاتر
const clearFilters = () => {
  form.search = '';
  form.category = '';
  form.sentiment = '';
  form.date = '';
};
</script>