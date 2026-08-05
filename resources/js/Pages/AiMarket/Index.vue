<template>
  <HomeLayout>
    <Head>
      <title>{{ locale === 'ar' ? 'مرصد الذكاء الاصطناعي للأسواق | CryptoHub' : 'AI Market Intelligence | CryptoHub' }}</title>
      <meta name="description" :content="locale === 'ar' ? 'لوحة قيادة تفاعلية ترصد مشاعر السوق والعملات الأكثر تداولاً عبر الذكاء الاصطناعي.' : 'Interactive AI dashboard tracking market sentiment and trending coins.'" />
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
            {{ locale === 'ar' ? 'مرصد الذكاء الاصطناعي' : 'AI Market Intelligence' }}
          </h1>
          <p class="text-slate-500 dark:text-slate-400 text-base sm:text-lg font-medium">
            {{ locale === 'ar' ? `قام نموذجنا بتحليل ${aiStats.total_analyzed} تقريراً إخبارياً لاستخراج نبض الأسواق.` : `Analyzed ${aiStats.total_analyzed} articles to extract market pulse.` }}
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
            <div class="text-3xl sm:text-4xl">
              {{ aiStats.marketMood === 'Bullish' ? '🚀' : (aiStats.marketMood === 'Bearish' ? '📉' : '⚖️') }}
            </div>
          </div>

          <div v-if="aiStats.mostMentionedCoin" class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-950/20 dark:to-[#151e32] p-6 rounded-3xl border border-indigo-200 dark:border-indigo-500/30 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 block mb-1">
                🏆 {{ locale === 'ar' ? 'العملة الأكثر تداولاً في الأخبار' : 'Most Mentioned Coin' }}
              </span>
              <div class="flex items-center gap-3">
                <Link :href="`/crypto/${aiStats.mostMentionedCoin.symbol}`" class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white hover:text-indigo-500 transition-colors">
                  {{ aiStats.mostMentionedCoin.name }}
                </Link>
                <span class="bg-indigo-500 text-white font-mono font-bold text-xs px-2 py-0.5 rounded-md">
                  {{ aiStats.mostMentionedCoin.symbol }}
                </span>
              </div>
            </div>
            <div class="text-right">
              <span class="block text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                {{ aiStats.mostMentionedCoin.count }}
              </span>
              <span class="text-[10px] font-bold text-slate-400">
                {{ locale === 'ar' ? 'إشارة إخبارية' : 'mentions' }}
              </span>
            </div>
          </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <div class="bg-white dark:bg-[#151e32] p-8 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
              🧭 {{ locale === 'ar' ? 'توزيع مشاعر الأخبار' : 'Sentiment Breakdown' }}
            </h3>
            
            <div class="space-y-6">
              <div>
                <div class="flex justify-between mb-2 text-sm font-bold">
                  <span class="text-emerald-600 dark:text-emerald-400">🟢 {{ locale === 'ar' ? 'صعودي (Bullish)' : 'Bullish' }}</span>
                  <span class="text-slate-900 dark:text-white">{{ aiStats.sentiment.bullish }}%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                  <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000" :style="`width: ${aiStats.sentiment.bullish}%`"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between mb-2 text-sm font-bold">
                  <span class="text-amber-600 dark:text-amber-400">⚪ {{ locale === 'ar' ? 'محايد (Neutral)' : 'Neutral' }}</span>
                  <span class="text-slate-900 dark:text-white">{{ aiStats.sentiment.neutral }}%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                  <div class="bg-amber-500 h-full rounded-full transition-all duration-1000" :style="`width: ${aiStats.sentiment.neutral}%`"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between mb-2 text-sm font-bold">
                  <span class="text-rose-600 dark:text-rose-400">🔴 {{ locale === 'ar' ? 'هبوطي (Bearish)' : 'Bearish' }}</span>
                  <span class="text-slate-900 dark:text-white">{{ aiStats.sentiment.bearish }}%</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden">
                  <div class="bg-rose-500 h-full rounded-full transition-all duration-1000" :style="`width: ${aiStats.sentiment.bearish}%`"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-[#151e32] p-8 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-sm">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
              🔥 {{ locale === 'ar' ? 'العملات والمواضيع الأكثر تداولاً' : 'Trending Topics & Coins' }}
            </h3>
            <div class="flex flex-col gap-3">
              <template v-for="(item, index) in aiStats.trending" :key="index">
                
                <Link 
                  v-if="item.is_coin"
                  :href="`/crypto/${item.keyword}`"
                  class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors border border-transparent hover:border-emerald-200 dark:hover:border-emerald-500/30 group cursor-pointer"
                >
                  <span class="font-bold text-slate-700 dark:text-slate-200 text-sm sm:text-base group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                    #{{ item.keyword }} <span class="text-[10px] opacity-50 ml-1">({{ locale === 'ar' ? 'عملة' : 'Coin' }})</span>
                  </span>
                  <span class="bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 font-mono font-bold text-xs px-2.5 py-1 rounded-md">
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
            <Link :href="`/news/${news.id}-${news.slug}`" v-for="news in impactfulNews" :key="news.id" class="bg-white dark:bg-[#151e32] rounded-3xl border border-slate-200 dark:border-slate-800 p-6 hover:border-indigo-500 transition-colors shadow-sm group">
              
              <div class="flex justify-between items-center mb-4">
                <span class="text-xs font-mono font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded">
                  {{ news.source }}
                </span>
                <div class="flex items-center gap-2">
                  <span v-if="news.related_symbol" class="text-xs font-mono font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-100 dark:border-indigo-500/20">
                    {{ news.related_symbol }}
                  </span>
                  <span class="text-xs px-2.5 py-1 rounded font-black font-mono bg-amber-50 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
                    ⚡ {{ news.impact_score }}/10
                  </span>
                </div>
              </div>

              <h4 class="font-bold text-slate-900 dark:text-white mb-2 leading-snug group-hover:text-indigo-500 transition-colors line-clamp-2" :class="locale === 'ar' ? 'text-right' : 'text-left'">
                {{ news.translations[locale === 'ar' ? 'ar' : 'en'].title }}
              </h4>
              <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-3 leading-relaxed" :class="locale === 'ar' ? 'text-right' : 'text-left'">
                {{ locale === 'ar' ? news.translations.ar.summary : news.translations.en.content }}
              </p>
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
</script>