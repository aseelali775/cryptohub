<template>
  <HomeLayout>

    <Head>
      <title head-key="title">
        {{ t('title') }} | Aql Crypto
      </title>

      <link
        head-key="canonical"
        rel="canonical"
        :href="canonicalUrl"
      />

      <meta
        head-key="description"
        name="description"
        :content="t('desc')"
      />

      <meta
        head-key="og:title"
        property="og:title"
        :content="t('title') + ' | Aql Crypto'"
      />

      <meta
        head-key="og:description"
        property="og:description"
        :content="t('desc')"
      />

      <meta
        head-key="og:url"
        property="og:url"
        :content="canonicalUrl"
      />

      <meta
        head-key="twitter:title"
        name="twitter:title"
        :content="t('title') + ' | Aql Crypto'"
      />

      <meta
        head-key="twitter:description"
        name="twitter:description"
        :content="t('desc')"
      />

      <meta
        head-key="twitter:url"
        name="twitter:url"
        :content="canonicalUrl"
      />
    </Head>


    <div
      class="w-full min-h-screen pb-24 bg-slate-50 dark:bg-[#0b1121] transition-colors duration-300"
    >

      <div
        class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-10 space-y-4"
        :class="locale === 'ar' ? 'text-right' : 'text-left'"
      >

        <!-- ===================================================== -->
        <!-- Header -->
        <!-- ===================================================== -->

        <div
          class="bg-white dark:bg-[#1e293b] p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-20"
        >

          <div class="flex items-center gap-3">

            <h1
              class="text-xl font-black text-slate-900 dark:text-white"
            >
              {{ t('title') }}
            </h1>

            <span
              class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"
            ></span>

          </div>


          <!-- Search -->

          <form
            @submit.prevent="performSearch"
            class="flex-1 w-full md:max-w-md flex gap-2"
          >

            <div class="relative flex-1">

              <input
                v-model="searchQuery"
                type="text"
                :placeholder="t('search_placeholder')"
                class="w-full bg-slate-50 dark:bg-[#0f172a] border border-slate-200 dark:border-slate-700 text-sm rounded-xl px-4 py-2.5 outline-none focus:border-emerald-500 transition-colors dark:text-white"
              />

            </div>


            <button
              type="submit"
              class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold transition-colors whitespace-nowrap"
            >
              {{ t('search') }}
            </button>

          </form>

        </div>


        <!-- ===================================================== -->
        <!-- Quick Search -->
        <!-- ===================================================== -->

        <div class="flex gap-2 flex-wrap">

          <button
            @click="quickSearch('BTC')"
            class="text-[10px] font-bold px-3 py-1.5 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-colors"
          >
            BTC
          </button>

          <button
            @click="quickSearch('ETH')"
            class="text-[10px] font-bold px-3 py-1.5 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-colors"
          >
            ETH
          </button>

          <button
            @click="quickSearch('SOL')"
            class="text-[10px] font-bold px-3 py-1.5 bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-colors"
          >
            SOL
          </button>

          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="text-[10px] font-bold px-3 py-1.5 bg-rose-50 dark:bg-rose-500/10 text-rose-500 border border-rose-200 dark:border-rose-500/20 rounded-lg hover:bg-rose-500 hover:text-white transition-colors"
          >
            {{ t('clear_search') }}
          </button>

        </div>


        <!-- ===================================================== -->
        <!-- Filters -->
        <!-- ===================================================== -->

        <div
          class="flex overflow-x-auto gap-2 pb-2 scrollbar-none snap-x"
        >

          <button
            @click="changeFilter('all')"
            :class="
              currentFilter === 'all'
                ? 'bg-emerald-500 text-white border-emerald-500'
                : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'
            "
            class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start"
          >
            {{ t('filter_all') }}
          </button>


          <button
            @click="changeFilter('gainers')"
            :class="
              currentFilter === 'gainers'
                ? 'bg-emerald-500 text-white border-emerald-500'
                : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'
            "
            class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start"
          >
            🔥 {{ t('filter_gainers') }}
          </button>


          <button
            @click="changeFilter('mega')"
            :class="
              currentFilter === 'mega'
                ? 'bg-emerald-500 text-white border-emerald-500'
                : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-emerald-500/50'
            "
            class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start"
          >
            💎 {{ t('filter_mega') }}
          </button>


          <button
            @click="changeFilter('losers')"
            :class="
              currentFilter === 'losers'
                ? 'bg-rose-500 text-white border-rose-500'
                : 'bg-white dark:bg-[#151e32] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:border-rose-500/50'
            "
            class="px-4 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap transition-all shadow-sm snap-start"
          >
            📉 {{ t('filter_losers') }}
          </button>

        </div>


        <!-- ===================================================== -->
        <!-- Sorting -->
        <!-- ===================================================== -->

        <div
          class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between"
        >

          <div
            class="text-xs font-bold text-slate-500 dark:text-slate-400"
          >
            {{ t('sort_by') }}
          </div>


          <div class="flex flex-wrap gap-2">

            <button
              @click="changeSort('market_cap')"
              :class="sort === 'market_cap'
                ? 'bg-emerald-500 text-white'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
            >
              {{ t('market_cap') }}
            </button>


            <button
              @click="changeSort('current_price')"
              :class="sort === 'current_price'
                ? 'bg-emerald-500 text-white'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
            >
              {{ t('price') }}
            </button>


            <button
              @click="changeSort('change_24h')"
              :class="sort === 'change_24h'
                ? 'bg-emerald-500 text-white'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
            >
              {{ t('change_24h') }}
            </button>


            <button
              @click="changeSort('volume_24h')"
              :class="sort === 'volume_24h'
                ? 'bg-emerald-500 text-white'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
            >
              {{ t('volume') }}
            </button>


            <button
              @click="toggleDirection"
              class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-emerald-500 hover:text-white transition-colors"
            >
              {{ direction === 'desc' ? '↓' : '↑' }}
              {{ direction === 'desc' ? t('descending') : t('ascending') }}
            </button>

          </div>

        </div>


        <!-- ===================================================== -->
        <!-- Table Header -->
        <!-- ===================================================== -->

        <div
          class="flex items-center justify-between px-4 py-2 text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider"
        >

          <div class="flex gap-4 items-center">

            <span class="w-4 text-center">
              #
            </span>

            <span>
              {{ t('asset') }}
            </span>

          </div>


          <div
            class="flex items-center gap-12 sm:gap-24"
          >

            <span
              class="hidden sm:block text-center w-20"
            >
              {{ t('trend') }}
            </span>

            <span
              class="text-right w-20"
            >
              {{ t('price') }}
            </span>

          </div>

        </div>


        <!-- ===================================================== -->
        <!-- Crypto List -->
        <!-- ===================================================== -->

        <div class="flex flex-col gap-2">

          <Link
            v-for="(crypto, index) in cryptoList"
            :key="crypto.id"
            :href="'/crypto/' + (crypto.symbol?.toLowerCase() || '')"
            class="flex items-center justify-between p-3 sm:p-4 bg-white dark:bg-[#151e32] rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 dark:hover:bg-[#1e293b]/50 transition-all group"
          >

            <!-- Left -->

            <div
              class="flex items-center gap-3 sm:gap-4"
            >

              <span
                class="text-[10px] sm:text-xs font-mono font-bold text-slate-400 w-4 text-center"
              >
                {{ rankNumber(index) }}
              </span>


              <img
                :src="crypto.image_url"
                class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-50 dark:bg-slate-800 p-1 object-contain flex-shrink-0"
                :alt="crypto.name"
                loading="lazy"
              />


              <div class="flex flex-col">

                <span
                  class="font-bold text-slate-900 dark:text-white text-sm sm:text-base group-hover:text-emerald-500 transition-colors uppercase"
                >
                  {{ crypto.symbol }}
                </span>

                <span
                  class="text-[10px] sm:text-xs text-slate-500 font-medium truncate max-w-[80px] sm:max-w-[150px]"
                >
                  {{ crypto.name }}
                </span>

              </div>

            </div>


            <!-- Right -->

            <div
              class="flex items-center gap-6 sm:gap-16"
            >

              <!-- Trend -->

              <div
                class="hidden sm:flex w-20 h-8 items-center justify-center opacity-70 group-hover:opacity-100 transition-opacity"
              >

                <svg
                  viewBox="0 0 100 30"
                  class="w-full h-full"
                  preserveAspectRatio="none"
                >

                  <path
                    d="M0,15 Q10,5 20,15 T40,15 T60,20 T80,10 T100,5"
                    fill="none"
                    :stroke="
                      crypto.change_24h >= 0
                        ? '#10b981'
                        : '#f43f5e'
                    "
                    stroke-width="2"
                    stroke-linecap="round"
                    :class="
                      crypto.change_24h >= 0
                        ? 'drop-shadow-[0_2px_4px_rgba(16,185,129,0.4)]'
                        : 'drop-shadow-[0_2px_4px_rgba(244,63,94,0.4)]'
                    "
                  />

                </svg>

              </div>


              <!-- Price -->

              <div
                class="flex flex-col items-end w-20 sm:w-24"
              >

                <span
                  class="font-mono font-bold text-slate-900 dark:text-white text-sm sm:text-base tracking-tight"
                >
                  ${{ formatPrice(crypto.current_price) }}
                </span>


                <span
                  class="text-[10px] sm:text-xs font-black px-1.5 py-0.5 rounded mt-1 font-mono"
                  :class="
                    crypto.change_24h >= 0
                      ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500'
                      : 'bg-rose-50 dark:bg-rose-500/10 text-rose-500'
                  "
                >

                  {{ crypto.change_24h >= 0 ? '▲ +' : '▼ ' }}

                  {{ formatChange(crypto.change_24h) }}%

                </span>

              </div>

            </div>

          </Link>


          <!-- Empty -->

          <div
            v-if="cryptoList.length === 0"
            class="text-center py-12 text-slate-500 dark:text-slate-400"
          >

            <span class="text-4xl mb-3 block">
              🔍
            </span>

            <p class="text-sm font-bold">
              {{ t('no_results') }}
            </p>

          </div>

        </div>


        <!-- ===================================================== -->
        <!-- Pagination -->
        <!-- ===================================================== -->

        <div
          v-if="pagination.last_page > 1"
          class="bg-white dark:bg-[#151e32] border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4"
        >

          <div
            class="text-xs text-slate-500 dark:text-slate-400 font-bold"
          >
            {{ pagination.from }}–{{ pagination.to }}
            {{ t('of') }}
            {{ pagination.total }}
          </div>


          <div
            class="flex items-center gap-2"
          >

            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-2 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-emerald-500 hover:text-white transition-colors"
            >
              {{ t('previous') }}
            </button>


            <span
              class="px-3 py-2 rounded-lg bg-emerald-500 text-white text-xs font-bold"
            >
              {{ pagination.current_page }}
              /
              {{ pagination.last_page }}
            </span>


            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-2 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-emerald-500 hover:text-white transition-colors"
            >
              {{ t('next') }}
            </button>

          </div>

        </div>

      </div>

    </div>

  </HomeLayout>
</template>


<script setup>

import HomeLayout from '@/layouts/HomeLayout.vue';

import {
  Link,
  usePage,
  Head,
  router
} from '@inertiajs/vue3';

import {
  computed,
  ref
} from 'vue';


/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({

  cryptos: {
    type: [Array, Object],
    default: () => []
  },

  filters: {
    type: Object,
    default: () => ({
      search: '',
      filter: 'all',
      sort: 'market_cap',
      direction: 'desc'
    })
  }

});


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

const page = usePage();


const locale = computed(
  () => page.props.locale || 'ar'
);


/*
|--------------------------------------------------------------------------
| Canonical
|--------------------------------------------------------------------------
|
| نحافظ على canonical الأساسي /prices
| ولا نجعل صفحات البحث والترتيب نسخًا مستقلة.
|
*/

const canonicalUrl = computed(() => {
  return 'https://aqlcrypto.com/prices';
});


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

const searchQuery = ref(
  props.filters?.search || ''
);


/*
|--------------------------------------------------------------------------
| Current filters
|--------------------------------------------------------------------------
*/

const currentFilter = computed(
  () => props.filters?.filter || 'all'
);


const sort = computed(
  () => props.filters?.sort || 'market_cap'
);


const direction = computed(
  () => props.filters?.direction || 'desc'
);


/*
|--------------------------------------------------------------------------
| Pagination data
|--------------------------------------------------------------------------
*/

const pagination = computed(() => {

  if (
    props.cryptos &&
    !Array.isArray(props.cryptos)
  ) {
    return {
      current_page: props.cryptos.current_page ?? 1,
      last_page: props.cryptos.last_page ?? 1,
      from: props.cryptos.from ?? 0,
      to: props.cryptos.to ?? 0,
      total: props.cryptos.total ?? 0,
      per_page: props.cryptos.per_page ?? 25,
    };
  }

  return {
    current_page: 1,
    last_page: 1,
    from: 1,
    to: Array.isArray(props.cryptos)
      ? props.cryptos.length
      : 0,
    total: Array.isArray(props.cryptos)
      ? props.cryptos.length
      : 0,
    per_page: Array.isArray(props.cryptos)
      ? props.cryptos.length
      : 25,
  };

});


/*
|--------------------------------------------------------------------------
| Current page data
|--------------------------------------------------------------------------
*/

const cryptoList = computed(() => {

  if (Array.isArray(props.cryptos)) {
    return props.cryptos;
  }

  return props.cryptos?.data || [];

});


/*
|--------------------------------------------------------------------------
| Ranking
|--------------------------------------------------------------------------
*/

const rankNumber = (index) => {

  if (pagination.value.from) {
    return pagination.value.from + index;
  }

  return index + 1;
};


/*
|--------------------------------------------------------------------------
| Price formatting
|--------------------------------------------------------------------------
*/

const formatPrice = (value) => {

  const number = Number(value || 0);

  return number.toLocaleString(
    'en-US',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 6
    }
  );

};


/*
|--------------------------------------------------------------------------
| Change formatting
|--------------------------------------------------------------------------
*/

const formatChange = (value) => {

  return Math.abs(
    Number(value || 0)
  ).toFixed(2);

};


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

const performSearch = () => {

  router.get(
    '/prices',
    {
      search: searchQuery.value || undefined,
      filter: currentFilter.value !== 'all'
        ? currentFilter.value
        : undefined,
      sort: sort.value,
      direction: direction.value
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true
    }
  );

};


/*
|--------------------------------------------------------------------------
| Quick search
|--------------------------------------------------------------------------
*/

const quickSearch = (symbol) => {

  searchQuery.value = symbol;

  performSearch();

};


/*
|--------------------------------------------------------------------------
| Clear search
|--------------------------------------------------------------------------
*/

const clearSearch = () => {

  searchQuery.value = '';

  performSearch();

};


/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/

const changeFilter = (filter) => {

  router.get(
    '/prices',
    {
      search: searchQuery.value || undefined,
      filter: filter !== 'all'
        ? filter
        : undefined,
      sort: sort.value,
      direction: direction.value
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true
    }
  );

};


/*
|--------------------------------------------------------------------------
| Sort
|--------------------------------------------------------------------------
*/

const changeSort = (newSort) => {

  let newDirection = direction.value;

  /*
   * إذا اخترنا نفس الحقل مرة أخرى
   * نعكس الاتجاه.
   */

  if (sort.value === newSort) {

    newDirection =
      direction.value === 'desc'
        ? 'asc'
        : 'desc';

  } else {

    /*
     * الترتيب الافتراضي للحقول الجديدة
     */

    newDirection = 'desc';

  }


  router.get(
    '/prices',
    {
      search: searchQuery.value || undefined,
      filter: currentFilter.value !== 'all'
        ? currentFilter.value
        : undefined,
      sort: newSort,
      direction: newDirection
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true
    }
  );

};


/*
|--------------------------------------------------------------------------
| Toggle direction
|--------------------------------------------------------------------------
*/

const toggleDirection = () => {

  const newDirection =
    direction.value === 'desc'
      ? 'asc'
      : 'desc';


  router.get(
    '/prices',
    {
      search: searchQuery.value || undefined,
      filter: currentFilter.value !== 'all'
        ? currentFilter.value
        : undefined,
      sort: sort.value,
      direction: newDirection
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true
    }
  );

};


/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const goToPage = (pageNumber) => {

  if (
    pageNumber < 1 ||
    pageNumber > pagination.value.last_page
  ) {
    return;
  }


  router.get(
    '/prices',
    {
      page: pageNumber,
      search: searchQuery.value || undefined,
      filter: currentFilter.value !== 'all'
        ? currentFilter.value
        : undefined,
      sort: sort.value,
      direction: direction.value
    },
    {
      preserveState: true,
      preserveScroll: false,
      replace: true
    }
  );

};


/*
|--------------------------------------------------------------------------
| Translations
|--------------------------------------------------------------------------
*/

const translations = {

  ar: {

    title: 'الأسواق',

    desc: 'متابعة لحظية لأفضل الأصول الرقمية',

    search_placeholder:
      'ابحث عن عملة أو رمز (مثال: BTC)',

    search:
      'بحث',

    clear_search:
      'مسح البحث',

    filter_all:
      'جميع العملات',

    filter_gainers:
      'الأكثر ارتفاعاً',

    filter_mega:
      'القيادية',

    filter_losers:
      'الأكثر انخفاضاً',

    asset:
      'العملة',

    price:
      'السعر',

    trend:
      'التغير 24 ساعة',

    sort_by:
      'ترتيب حسب',

    market_cap:
      'القيمة السوقية',

    change_24h:
      'التغير 24س',

    volume:
      'حجم التداول',

    descending:
      'تنازلي',

    ascending:
      'تصاعدي',

    previous:
      'السابق',

    next:
      'التالي',

    of:
      'من',

    no_results:
      'لا توجد نتائج مطابقة للبحث'

  },


  en: {

    title:
      'Markets',

    desc:
      'Real-time tracking of top digital assets',

    search_placeholder:
      'Search assets or symbol (e.g. BTC)',

    search:
      'Search',

    clear_search:
      'Clear',

    filter_all:
      'All Coins',

    filter_gainers:
      'Top Gainers',

    filter_mega:
      'Mega Caps',

    filter_losers:
      'Top Losers',

    asset:
      'Asset',

    price:
      'Price',

    trend:
      '24h Change',

    sort_by:
      'Sort by',

    market_cap:
      'Market Cap',

    change_24h:
      '24h Change',

    volume:
      'Volume',

    descending:
      'Descending',

    ascending:
      'Ascending',

    previous:
      'Previous',

    next:
      'Next',

    of:
      'of',

    no_results:
      'No matching results found'

  }

};


const t = (key) => {

  return translations[locale.value]?.[key]
    || key;

};

</script>


<style scoped>

.scrollbar-none::-webkit-scrollbar {
  display: none;
}

.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

</style>