<template>
  <HomeLayout>
    <Head>
      <title head-key="title">{{ seoTitle }}</title>

      <link head-key="canonical" rel="canonical" :href="canonicalUrl" />

      <meta head-key="description" name="description" :content="seoDescription" />
      <meta head-key="keywords" name="keywords" :content="seoKeywords" />

      <meta head-key="og:type" property="og:type" content="website" />
      <meta head-key="og:title" property="og:title" :content="seoTitle" />
      <meta head-key="og:description" property="og:description" :content="seoDescription" />
      <meta head-key="og:url" property="og:url" :content="canonicalUrl" />
      <meta head-key="og:image" property="og:image" content="https://aqlcrypto.com/images/default-og.jpg" />

      <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
      <meta head-key="twitter:title" name="twitter:title" :content="seoTitle" />
      <meta head-key="twitter:description" name="twitter:description" :content="seoDescription" />
      <meta head-key="twitter:image" name="twitter:image" content="https://aqlcrypto.com/images/default-og.jpg" />
      <meta head-key="twitter:url" name="twitter:url" :content="canonicalUrl" />
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-12 space-y-10" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
          <div class="flex items-center justify-center gap-3">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 text-xs font-bold font-mono">
              <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
              </span>
              Live AI Processing
            </span>
            <span v-if="aiStats.lastUpdated" class="text-xs font-bold text-slate-400 dark:text-slate-500 font-mono">
              🕒 {{ locale === 'ar' ? `آخر تحديث: ${aiStats.lastUpdated}` : `Updated: ${aiStats.lastUpdated}` }}
            </span>
          </div>

          <h1 class="text-3xl sm:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
            {{ locale === 'ar' ? 'مرصد ذكاء السوق' : 'AI Market Intelligence' }}
          </h1>
          
          <p class="text-slate-500 dark:text-slate-400 text-base sm:text-lg font-medium leading-relaxed max-w-2xl mx-auto mt-4">
            {{ locale === 'ar' ? 'لوحة ذكاء اصطناعي تفاعلية ترصد اتجاهات السوق، وتحلل المشاعر، وتكتشف الأصول الأكثر تداولاً.' : 'Interactive AI dashboard tracking market sentiment, trends, and the most mentioned coins.' }}
            <br class="hidden sm:block">
            <span class="text-sm text-indigo-600 dark:text-indigo-400 font-bold mt-2 inline-block bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1 rounded-lg">
              {{ locale === 'ar' ? `(تم تحليل ${aiStats.total_analyzed} تقريراً إخبارياً بنجاح)` : `(Analyzed ${aiStats.total_analyzed} news reports)` }}
            </span>
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <div class="bg-gradient-to-br p-6 rounded-3xl border shadow-sm flex items-center justify-between"
               :class="aiStats.marketMood === 'Bullish' ? 'from-emerald-50 to-white dark:from-emerald-950/20 dark:to-[#151e32] border-emerald-200 dark:border-emerald-500/30' : (aiStats.marketMood === 'Bearish' ? 'from-rose-50 to-white dark:from-rose-950/20 dark:to-[#151e32] border-rose-200 dark:border-rose-500/30' : 'from-amber-50 to-white dark:from-amber-950/20 dark:to-[#151e32] border-amber-200 dark:border-amber-500/30')"
          >
            <div>
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 block mb-1">
                {{ locale === 'ar' ? 'خلاصة توجه السوق الحالي' : 'Overall Market Mood' }}
              </span>
              <h2 class="text-2xl sm:text-3xl font-black flex items-center gap-2"
                  :class="aiStats.marketMood === 'Bullish' ? 'text-emerald-600 dark:text-emerald-400' : (aiStats.marketMood === 'Bearish' ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400')"
              >
                <span v-if="aiStats.marketMood === 'Bullish'">🟢 {{ locale === 'ar' ? 'السوق متفائل جداً' : 'Bullish Sentiment' }}</span>
                <span v-else-if="aiStats.marketMood === 'Bearish'">🔴 {{ locale === 'ar' ? 'السوق متشائم' : 'Bearish Sentiment' }}</span>
                <span v-else>⚪ {{ locale === 'ar' ? 'السوق في حالة تحفّظ' : 'Neutral Sentiment' }}</span>
              </h2>
            </div>
            <div class="text-3xl sm:text-4xl hover:scale-110 transition-transform cursor-default">
              {{ aiStats.marketMood === 'Bullish' ? '🚀' : (aiStats.marketMood === 'Bearish' ? '📉' : '⚖️') }}
            </div>
          </div>

          <div v-if="aiStats.mostMentionedCoin" class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-950/20 dark:to-[#151e32] p-6 rounded-3xl border border-indigo-200 dark:border-indigo-500/30 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 block mb-1">
                🏆 {{ locale === 'ar' ? 'العملة الأكثر تداولاً في الأخبار' : 'Most Mentioned Coin' }}
              </span>
              <div class="flex items-center gap-3 mt-1">
                <Link :href="`/crypto/${aiStats.mostMentionedCoin.symbol?.toLowerCase() || ''}`" class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white hover:text-indigo-500 transition-colors">
                  {{ aiStats.mostMentionedCoin.name }}
                </Link>
                <span class="bg-indigo-500 text-white font-mono font-bold text-xs px-2 py-0.5 rounded-md">
                  {{ aiStats.mostMentionedCoin.symbol }}
                </span>
              </div>
            </div>
            <div class="text-center">
              <span class="block text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                {{ aiStats.mostMentionedCoin.count }}
              </span>
              <span class="text-[10px] font-bold text-slate-400 block uppercase tracking-wider">
                {{ locale === 'ar' ? 'إشارة' : 'Mentions' }}
              </span>
            </div>
          </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <div class="bg-white dark:bg-[#151e32] p-8 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
              🧭 {{ locale === 'ar' ? 'توزيع مشاعر الأخبار' : 'Sentiment Breakdown' }}
            </h3>
            
            <div class="space-y-6">
              <div>
                <div class="flex justify-between mb-2 text-sm font-bold">
                  <span class="text-emerald-600 dark:text-emerald-400">🟢 {{ locale === 'ar' ? 'صعودي (Bullish)' : 'Bullish' }}</span>
                  <span class="text-slate-900 dark:text-white font-mono">{{ aiStats.sentiment.bullish }}%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                  <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000" :style="`width: ${aiStats.sentiment.bullish}%`"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between mb-2 text-sm font-bold">
                  <span class="text-amber-600 dark:text-amber-400">⚪ {{ locale === 'ar' ? 'محايد (Neutral)' : 'Neutral' }}</span>
                  <span class="text-slate-900 dark:text-white font-mono">{{ aiStats.sentiment.neutral }}%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                  <div class="bg-amber-500 h-full rounded-full transition-all duration-1000" :style="`width: ${aiStats.sentiment.neutral}%`"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between mb-2 text-sm font-bold">
                  <span class="text-rose-600 dark:text-rose-400">🔴 {{ locale === 'ar' ? 'هبوطي (Bearish)' : 'Bearish' }}</span>
                  <span class="text-slate-900 dark:text-white font-mono">{{ aiStats.sentiment.bearish }}%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                  <div class="bg-rose-500 h-full rounded-full transition-all duration-1000" :style="`width: ${aiStats.sentiment.bearish}%`"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-[#151e32] p-8 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm hover:shadow-md transition-shadow">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
              🔥 {{ locale === 'ar' ? 'العملات والمواضيع الأكثر تداولاً' : 'Trending Topics & Coins' }}
            </h3>
            <div class="flex flex-col gap-3">
              <template v-for="(item, index) in aiStats.trending" :key="index">
                
                <Link 
                  v-if="item.is_coin"
                  :href="`/crypto/${item.keyword?.toLowerCase()}`"
                  class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors border border-transparent hover:border-emerald-200 dark:hover:border-emerald-500/30 group cursor-pointer"
                >
                  <span class="font-bold text-slate-700 dark:text-slate-200 text-sm sm:text-base group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                    #{{ item.keyword }} <span class="text-[10px] opacity-50 ml-1">({{ locale === 'ar' ? 'عملة' : 'Coin' }})</span>
                  </span>
                  <span class="bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 font-mono font-bold text-xs px-2.5 py-1 rounded-md transition-colors group-hover:bg-emerald-200 dark:group-hover:bg-emerald-500/40">
                    {{ locale === 'ar' ? `${item.count} إشارات` : `${item.count} mentions` }}
                  </span>
                </Link>

                <div 
                  v-else
                  class="flex items-center justify-between p-3 rounded-xl bg-white dark:bg-[#151e32] border border-slate-100 dark:border-slate-700"
                >
                  <span class="font-bold text-slate-600 dark:text-slate-400 text-sm sm:text-base">
                    #{{ item.keyword }}
                  </span>
                  <span class="bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-mono font-bold text-xs px-2.5 py-1 rounded-md">
                    {{ locale === 'ar' ? `${item.count} إشارات` : `${item.count} mentions` }}
                  </span>
                </div>

              </template>
            </div>
          </div>

        </div>

        <div>
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
              ⚡ {{ locale === 'ar' ? 'الأخبار الأشد تأثيراً على السوق' : 'Most Impactful News' }}
            </h3>
          </div>
          
          <div v-if="impactfulNews.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <Link :href="`/news/${news.id}${news.slug ? '-' + news.slug.replace(new RegExp('-' + news.id + '$'), '') : ''}`" v-for="news in impactfulNews" :key="news.id" class="bg-white dark:bg-[#151e32] rounded-3xl border border-slate-200 dark:border-slate-800 p-6 hover:border-indigo-500 transition-colors shadow-sm hover:shadow-md group flex flex-col justify-between">
              
              <div>
                <div class="flex justify-between items-center mb-4">
                  <span class="text-[10px] font-mono font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded uppercase tracking-wider">
                    {{ news.source }}
                  </span>
                  <div class="flex items-center gap-2">
                    <span v-if="news.related_symbol" class="text-xs font-mono font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-100 dark:border-indigo-500/20">
                      {{ news.related_symbol }}
                    </span>
                    <span class="text-[10px] px-2 py-1 rounded font-black font-mono bg-amber-50 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                      ⚡ {{ news.impact_score }}/10
                    </span>
                  </div>
                </div>

                <h4 class="font-bold text-slate-900 dark:text-white mb-3 leading-snug group-hover:text-indigo-500 transition-colors line-clamp-2" :class="locale === 'ar' ? 'text-right' : 'text-left'">
                  {{ news.translations[locale === 'ar' ? 'ar' : 'en'].title }}
                </h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-3 leading-relaxed" :class="locale === 'ar' ? 'text-right' : 'text-left'">
                  {{ locale === 'ar' ? news.translations.ar.summary : news.translations.en.content }}
                </p>
              </div>
              
              <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex justify-between items-center text-xs text-slate-400">
                <span class="font-medium text-indigo-500 flex items-center gap-1 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform">
                  {{ locale === 'ar' ? 'قراءة التحليل' : 'Read Analysis' }}
                  <span v-if="locale === 'ar'">←</span><span v-else>→</span>
                </span>
                <span class="font-mono">{{ new Date(news.published_at).toLocaleDateString(locale === 'ar' ? 'ar-EG' : 'en-US', { month: 'short', day: 'numeric' }) }}</span>
              </div>
            </Link>
          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/layouts/HomeLayout.vue';
import { computed } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  aiStats: { type: Object, required: true },
  impactfulNews: { type: Array, required: true }
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// ======================================================
// SEO + Canonical
// ======================================================

const seoTitle = computed(() =>
    locale.value === 'ar'
        ? 'مرصد ذكاء السوق | Aql Crypto'
        : 'AI Market Intelligence | Aql Crypto'
);

const seoDescription = computed(() =>
    locale.value === 'ar'
        ? 'لوحة ذكاء اصطناعي تفاعلية ترصد اتجاهات السوق، وتحلل المشاعر، وتكتشف الأصول الأكثر تداولاً.'
        : 'Interactive AI dashboard tracking market sentiment, trends, and the most mentioned coins.'
);

const seoKeywords = computed(() => {
    return locale.value === 'ar'
        ? 'ذكاء السوق, الذكاء الاصطناعي للعملات, مشاعر السوق, تحليل الكريبتو, اتجاه السوق, Aql Crypto'
        : 'AI Market Intelligence, Crypto AI, Market Sentiment, Crypto Trends, Aql Crypto';
});

// بناء الرابط المعتمد للصفحة
const canonicalUrl = computed(() => {
    const cleanPath = page.url.split('?')[0];

    return cleanPath === '/'
        ? 'https://aqlcrypto.com'
        : 'https://aqlcrypto.com' + cleanPath;
});
</script>