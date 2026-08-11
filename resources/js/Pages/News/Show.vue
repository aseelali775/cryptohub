<template>
  <HomeLayout>
    <Head>
      <title head-key="title">{{ seoTitle }} | Aql Crypto</title>

      <link rel="canonical" :href="canonicalUrl" />

      <meta head-key="description" name="description" :content="seoDescription" />
      <meta head-key="keywords" name="keywords" :content="seoKeywords" />

      <meta head-key="og:url" property="og:url" :content="canonicalUrl" />
      <meta head-key="og:title" property="og:title" :content="seoTitle" />
      <meta head-key="og:description" property="og:description" :content="seoDescription" />
      <meta head-key="og:image" property="og:image" :content="newsItem?.image_url" />
      
      <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
      <meta head-key="twitter:url" name="twitter:url" :content="canonicalUrl" />
      <meta head-key="twitter:title" name="twitter:title" :content="seoTitle" />
      <meta head-key="twitter:description" name="twitter:description" :content="seoDescription" />
      <meta head-key="twitter:image" name="twitter:image" :content="newsItem?.image_url" />

      <component :is="'script'" type="application/ld+json">
        {{ JSON.stringify(newsArticleSchema) }}
      </component>

      <component :is="'script'" type="application/ld+json">
        {{ JSON.stringify(breadcrumbSchema) }}
      </component>
    </Head>

    <div class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 sm:pt-16" :class="locale === 'ar' ? 'text-right' : 'text-left'">
        
        <nav class="flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-500 mb-8 overflow-x-auto whitespace-nowrap" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
          <Link href="/" class="hover:text-emerald-500 transition-colors">{{ locale === 'ar' ? 'الرئيسية' : 'Home' }}</Link>
          <span>/</span>
          <Link href="/news" class="hover:text-emerald-500 transition-colors">{{ locale === 'ar' ? 'الأخبار' : 'News' }}</Link>
          <span>/</span>
          <span class="text-slate-400 cursor-default">{{ newsItem?.category || 'Crypto' }}</span>
        </nav>

        <header class="space-y-6 mb-10" v-if="newsItem">
          <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 dark:text-white leading-[1.4] tracking-tight" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            {{ seoTitle }}
          </h1>

          <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-mono" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <span class="px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold tracking-wider">
              {{ newsItem?.source || 'Aql Crypto' }}
            </span>
            
            <span class="px-2 py-1.5 rounded-md bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 font-bold">#{{ newsItem?.category || 'Crypto' }}</span>
            
            <span v-if="newsItem?.sentiment === 'Bullish'" class="px-2 py-1.5 rounded-md bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20 font-bold">🟢 {{ locale === 'ar' ? 'صعودي' : 'Bullish' }}</span>
            <span v-else-if="newsItem?.sentiment === 'Bearish'" class="px-2 py-1.5 rounded-md bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 font-bold">🔴 {{ locale === 'ar' ? 'هبوطي' : 'Bearish' }}</span>
            <span v-else-if="newsItem?.sentiment === 'Neutral'" class="px-2 py-1.5 rounded-md bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 font-bold">⚪ {{ locale === 'ar' ? 'محايد' : 'Neutral' }}</span>

            <span v-if="newsItem?.impact_score" class="px-2 py-1.5 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-bold" :title="locale === 'ar' ? 'تأثير الخبر على السوق' : 'Impact Score'">⚡ {{ newsItem?.impact_score }}/10</span>
          </div>

          <div class="flex items-center gap-4 py-4 border-y border-slate-200 dark:border-slate-800" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center font-bold text-slate-500">AC</div>
            <div class="flex flex-col">
              <span class="text-sm font-bold text-slate-900 dark:text-white">{{ newsItem?.author?.name || 'Aql Crypto Editorial Team' }}</span>
              <span class="text-xs text-slate-500 font-mono">{{ displayDate }}</span>
            </div>
          </div>
        </header>

        <figure v-if="newsItem?.image_url" class="w-full aspect-video sm:h-[450px] rounded-[2rem] overflow-hidden bg-slate-900 shadow-2xl mb-12 relative border border-slate-200 dark:border-slate-800">
          <img :src="newsItem.image_url" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" :alt="newsItem?.translations.en.title" />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
        </figure>

        <div v-if="locale === 'ar' && newsItem?.ai_processed" class="mt-8 space-y-8" dir="rtl">
          
          <article class="max-w-none">
            <p class="text-lg sm:text-xl text-slate-700 dark:text-slate-300 leading-relaxed sm:leading-loose font-medium">
              {{ newsItem.translations.ar.content }}
            </p>
          </article>

          <div v-if="newsItem.translations.ar.context" class="p-6 bg-slate-100 dark:bg-slate-800/50 rounded-2xl border-r-4 border-indigo-500 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
              <span class="text-xl">🌐</span> السياق (Context)
            </h3>
            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">{{ newsItem.translations.ar.context }}</p>
          </div>

          <div v-if="newsItem.translations.ar.analysis" class="p-6 sm:p-8 bg-indigo-50 dark:bg-indigo-900/10 rounded-3xl border border-indigo-100 dark:border-indigo-500/20 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-indigo-500/5 blur-3xl rounded-full pointer-events-none"></div>
            <h3 class="text-xl font-black text-indigo-900 dark:text-indigo-300 mb-4 flex items-center gap-2 relative z-10">
              <span class="text-2xl">🧠</span> تحليل Aql Crypto
            </h3>
            <p class="text-base sm:text-lg text-slate-700 dark:text-slate-300 leading-relaxed font-medium relative z-10">
              {{ newsItem.translations.ar.analysis }}
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-if="newsItem.translations.ar.why_it_matters" class="p-6 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-500/20 shadow-sm">
              <h3 class="text-lg font-bold text-amber-900 dark:text-amber-300 mb-3 flex items-center gap-2">
                <span class="text-xl">💡</span> لماذا يهم هذا الخبر؟
              </h3>
              <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">{{ newsItem.translations.ar.why_it_matters }}</p>
            </div>

            <div v-if="newsItem.translations.ar.what_to_watch" class="p-6 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-500/20 shadow-sm">
              <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-300 mb-3 flex items-center gap-2">
                <span class="text-xl">👀</span> ما الذي يجب مراقبته؟
              </h3>
              <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">{{ newsItem.translations.ar.what_to_watch }}</p>
            </div>
          </div>

          <div v-if="newsItem.translations.ar.limitations" class="p-5 bg-slate-50 dark:bg-[#0f172a] rounded-xl border border-slate-200 dark:border-slate-800">
            <h4 class="text-sm font-bold text-slate-600 dark:text-slate-400 mb-2 flex items-center gap-2">
              <span class="text-base">⚠️</span> حدود التحليل
            </h4>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-500 leading-relaxed">{{ newsItem.translations.ar.limitations }}</p>
          </div>
        </div>

        <div v-else class="mt-8">
          <article class="max-w-none">
            <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-400 leading-relaxed sm:leading-loose whitespace-pre-line font-medium" :class="locale === 'ar' ? 'text-right' : 'text-left'" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
              {{ newsItem?.translations[locale === 'ar' ? 'ar' : 'en'].content }}
            </p>
          </article>
        </div>

        <div v-if="newsItem?.keywords && newsItem.keywords.length > 0" class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-800">
          <div class="flex flex-wrap gap-2" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <span 
              v-for="keyword in newsItem.keywords" 
              :key="keyword" 
              class="px-3.5 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold font-mono transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-default"
            >
              #{{ keyword }}
            </span>
          </div>
        </div>

        <div v-if="relatedNews && relatedNews.length > 0" class="mt-16 border-t border-slate-200 dark:border-slate-800 pt-10">
          <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-6" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            {{ locale === 'ar' ? 'أخبار ذات صلة' : 'Related News' }}
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <Link 
              v-for="relNews in relatedNews" 
              :key="relNews.id" 
              :href="buildUrl(relNews.id, relNews.slug)" 
              class="bg-white dark:bg-[#151e32] p-5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-emerald-500 transition-colors shadow-sm group flex flex-col"
            >
              <span class="text-[10px] font-mono font-bold text-slate-500 mb-2">{{ relNews.source }}</span>
              <h4 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-3 group-hover:text-emerald-500 transition-colors" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
                {{ relNews.translations[locale === 'ar' ? 'ar' : 'en']?.title }}
              </h4>
            </Link>
          </div>
        </div>

        <div class="mt-16 flex flex-col gap-6" v-if="newsItem">
          <div 
            v-if="newsItem?.url"
            class="flex justify-start" 
            :dir="locale === 'ar' ? 'rtl' : 'ltr'"
          >
            <a 
              :href="newsItem.url" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-sm hover:scale-105 transition-transform shadow-md"
            >
              <span>
                {{ locale === 'ar' ? 'قراءة المصدر الأصلي' : 'Read Original Source' }}
              </span>
              <span>↗</span>
            </a>
          </div>

          <div class="p-5 sm:p-6 bg-slate-100 dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl flex flex-col sm:flex-row gap-5 items-start shadow-sm" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <div class="w-12 h-12 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center text-xl flex-shrink-0">
              ⚖️
            </div>
            <div>
              <h4 class="text-sm font-bold text-slate-900 dark:text-slate-200 mb-2">
                {{ locale === 'ar' ? 'تنويه (Disclaimer)' : 'Disclaimer' }}
              </h4>
              <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed font-mono">
                {{ locale === 'ar' 
                  ? 'تم إعداد هذا المحتوى اعتماداً على مصادر أخبار العملات الرقمية والتحليل الآلي للمعلومات. هذا النص لا يمثل أي توجيه مالي أو نصيحة استثمارية.' 
                  : 'This content was prepared based on cryptocurrency news sources and automated data analysis. It does not constitute financial advice.' 
                }}
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </HomeLayout>
</template>

<script setup>
import HomeLayout from '@/layouts/HomeLayout.vue'; 
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  newsItem: { type: Object, required: true },
  relatedNews: { type: Array, default: () => [] } 
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

// معالجة البيانات للـ SEO لتكون نظيفة
const seoTitle = computed(() => props.newsItem?.translations?.[locale.value === 'ar' ? 'ar' : 'en']?.title || '');

const seoDescription = computed(() => {
    if (locale.value === 'ar') {
        return props.newsItem?.translations?.ar?.summary || props.newsItem?.translations?.ar?.content || '';
    }
    return props.newsItem?.translations?.en?.content || '';
});

const seoKeywords = computed(() => {
    return props.newsItem?.keywords ? props.newsItem.keywords.join(', ') : 'crypto, news';
});

// دالة بناء الروابط النظيفة
const buildUrl = (id, slug) => {
    let cleanSlug = slug || '';
    if (cleanSlug && cleanSlug.endsWith(`-${id}`)) {
        cleanSlug = cleanSlug.replace(new RegExp(`-${id}$`), '');
    }
    return cleanSlug ? `https://aqlcrypto.com/news/${id}-${cleanSlug}` : `https://aqlcrypto.com/news/${id}`;
};

// الرابط الرسمي للصفحة الحالية
const canonicalUrl = computed(() => buildUrl(props.newsItem.id, props.newsItem.slug));

// عرض التاريخ المنسق للقارئ
const displayDate = computed(() => {
    if(!props.newsItem.published_at) return props.newsItem.date;
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(props.newsItem.published_at).toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', options);
});

// 🔴 1. NewsArticle Schema
const newsArticleSchema = computed(() => ({
    "@context": "https://schema.org",
    "@type": "NewsArticle",
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": canonicalUrl.value
    },
    "headline": seoTitle.value,
    "description": seoDescription.value,
    "image": props.newsItem.image_url ? [props.newsItem.image_url] : [],
    "datePublished": props.newsItem.published_at || new Date().toISOString(),
    "dateModified": props.newsItem.updated_at || new Date().toISOString(),
    "author": {
        "@type": "Organization",
        "name": props.newsItem.author?.name || "Aql Crypto Editorial Team",
        "url": props.newsItem.author?.url || "https://aqlcrypto.com/about"
    },
    "publisher": {
        "@type": "Organization",
        "name": props.newsItem.publisher?.name || "Aql Crypto",
        "logo": {
            "@type": "ImageObject",
            "url": props.newsItem.publisher?.logo || "https://aqlcrypto.com/images/default-og.jpg"
        }
    }
}));

// 🔴 2. BreadcrumbList Schema
const breadcrumbSchema = computed(() => ({
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": locale.value === 'ar' ? 'الرئيسية' : 'Home',
            "item": "https://aqlcrypto.com/"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": locale.value === 'ar' ? 'الأخبار' : 'News',
            "item": "https://aqlcrypto.com/news"
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": props.newsItem.category || 'Crypto',
            "item": "https://aqlcrypto.com/news?category=" + (props.newsItem.category || 'Crypto')
        },
        {
            "@type": "ListItem",
            "position": 4,
            "name": seoTitle.value,
            "item": canonicalUrl.value
        }
    ]
}));
</script>