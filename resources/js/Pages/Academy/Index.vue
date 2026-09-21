<template>
    <Head>
        <title>{{ seoTitle }}</title>

        <link rel="canonical" :href="canonicalUrl" />

        <meta
            head-key="description"
            name="description"
            :content="seoDescription"
        />

        <meta
            head-key="og:type"
            property="og:type"
            content="website"
        />

        <meta
            head-key="og:url"
            property="og:url"
            :content="canonicalUrl"
        />

        <meta
            head-key="og:title"
            property="og:title"
            :content="seoTitle"
        />

        <meta
            head-key="og:description"
            property="og:description"
            :content="seoDescription"
        />

        <meta
            head-key="og:image"
            property="og:image"
            content="https://aqlcrypto.com/images/default-og.jpg"
        />

        <meta
            head-key="twitter:card"
            name="twitter:card"
            content="summary_large_image"
        />

        <meta
            head-key="twitter:title"
            name="twitter:title"
            :content="seoTitle"
        />

        <meta
            head-key="twitter:description"
            name="twitter:description"
            :content="seoDescription"
        />

        <meta
            head-key="twitter:image"
            name="twitter:image"
            content="https://aqlcrypto.com/images/default-og.jpg"
        />
    </Head>

    <div
        class="min-h-screen bg-slate-50 dark:bg-[#0b1121] text-slate-800 dark:text-slate-100 transition-colors duration-300"
    >
        <!-- Hero -->
        <section
            class="relative overflow-hidden border-b border-slate-200 dark:border-slate-800/80"
        >
            <div
                class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-slate-50 dark:from-emerald-950/20 dark:via-[#0b1121] dark:to-[#0b1121]"
            ></div>

            <div
                class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-24"
            >
                <div class="max-w-3xl mx-auto text-center">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider mb-6"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-emerald-500"
                        ></span>

                        {{ t('academyLabel') }}
                    </div>

                    <h1
                        class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 dark:text-white leading-tight"
                    >
                        {{ t('academyTitle') }}
                    </h1>

                    <p
                        class="mt-6 text-base sm:text-lg lg:text-xl leading-8 text-slate-600 dark:text-slate-400 max-w-2xl mx-auto"
                    >
                        {{ t('academyDescription') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Topics -->
        <main
            class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8"
            >
                <div>
                    <p
                        class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mb-2"
                    >
                        {{ t('academyBrowse') }}
                    </p>

                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"
                    >
                        {{ t('academyTopics') }}
                    </h2>
                </div>

                <div
                    class="text-sm text-slate-500 dark:text-slate-400"
                >
                    {{ topics.length }} {{ t('academyTopicCount') }}
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="topics.length === 0"
                class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#121927] p-10 text-center"
            >
                <div
                    class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-7 h-7"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                        />
                    </svg>
                </div>

                <h3
                    class="text-lg font-bold text-slate-900 dark:text-white"
                >
                    {{ t('academyComingSoon') }}
                </h3>

                <p
                    class="mt-2 text-sm text-slate-500 dark:text-slate-400"
                >
                    {{ t('academyComingSoonDescription') }}
                </p>
            </div>

            <!-- Topics Grid -->
            <div
                v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"
            >
                <Link
                    v-for="topic in topics"
                    :key="topic.id"
                    :href="`/academy/${topic.slug}`"
                    class="group relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#121927] hover:border-emerald-300 dark:hover:border-emerald-500/40 hover:-translate-y-1 transition-all duration-300 shadow-sm hover:shadow-lg"
                >
                    <!-- Image -->
                    <div
                        v-if="topic.image"
                        class="h-40 overflow-hidden bg-slate-100 dark:bg-slate-800"
                    >
                        <img
                            :src="topic.image"
                            :alt="topic.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                    </div>

                    <div
                        v-else
                        class="h-40 bg-gradient-to-br from-emerald-50 via-slate-50 to-white dark:from-emerald-950/20 dark:via-[#151e32] dark:to-[#121927] flex items-center justify-center"
                    >
                        <div
                            class="w-16 h-16 rounded-2xl bg-white dark:bg-[#0b1121] border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center shadow-sm"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-8 h-8 text-emerald-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.7"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5">
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <h3
                                class="text-lg font-black text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"
                            >
                                {{ topic.name }}
                            </h3>

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 shrink-0 text-slate-300 dark:text-slate-600 group-hover:text-emerald-500 transition-all"
                                :class="locale === 'ar'
                                    ? 'group-hover:-translate-x-1'
                                    : 'group-hover:translate-x-1'"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    :d="locale === 'ar'
                                        ? 'M15 19l-7-7 7-7'
                                        : 'M9 5l7 7-7 7'"
                                />
                            </svg>
                        </div>

                        <p
                            v-if="topic.description"
                            class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400 line-clamp-3"
                        >
                            {{ topic.description }}
                        </p>

                        <div
                            class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between"
                        >
                            <span
                                class="text-xs font-semibold text-slate-500 dark:text-slate-400"
                            >
                                {{ topic.articles_count || 0 }}
                                {{ t('academyLessons') }}
                            </span>

                            <span
                                class="text-xs font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                {{ t('academyExplore') }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const locale = computed(() => page.props.locale || 'ar');

const topics = computed(() => page.props.topics || []);

const canonicalUrl = computed(() => {
    const cleanPath = page.url.split('?')[0];

    return cleanPath === '/'
        ? 'https://aqlcrypto.com'
        : `https://aqlcrypto.com${cleanPath}`;
});

const translations = {
    ar: {
        academyLabel: 'AQL Crypto Academy',
        academyTitle: 'أكاديمية العملات الرقمية',
        academyDescription:
            'تعلم أساسيات العملات الرقمية وتقنية البلوك تشين والتمويل اللامركزي من خلال دروس واضحة ومنظمة تساعدك على فهم السوق والتقنيات المرتبطة به.',
        academyBrowse: 'ابدأ التعلم',
        academyTopics: 'المواضيع التعليمية',
        academyTopicCount: 'موضوع',
        academyLessons: 'درس',
        academyExplore: 'استكشف الموضوع',
        academyComingSoon: 'المحتوى التعليمي قادم قريبًا',
        academyComingSoonDescription:
            'نعمل حاليًا على إعداد مجموعة من الدروس التعليمية المنظمة حول أهم مفاهيم العملات الرقمية وتقنياتها.',
        seoTitle: 'أكاديمية العملات الرقمية | AQL Crypto',
        seoDescription:
            'أكاديمية AQL Crypto التعليمية: تعلم Bitcoin وBlockchain وDeFi وStaking وWeb3 وغيرها من مفاهيم العملات الرقمية.'
    },

    en: {
        academyLabel: 'AQL Crypto Academy',
        academyTitle: 'Cryptocurrency Academy',
        academyDescription:
            'Learn the fundamentals of cryptocurrencies, blockchain, decentralized finance, and related technologies through clear and structured educational lessons.',
        academyBrowse: 'Start Learning',
        academyTopics: 'Learning Topics',
        academyTopicCount: 'topics',
        academyLessons: 'lessons',
        academyExplore: 'Explore Topic',
        academyComingSoon: 'Educational content is coming soon',
        academyComingSoonDescription:
            'We are currently preparing a structured collection of educational lessons covering the most important cryptocurrency concepts and technologies.',
        seoTitle: 'Cryptocurrency Academy | AQL Crypto',
        seoDescription:
            'AQL Crypto Academy: Learn about Bitcoin, Blockchain, DeFi, Staking, Web3, and other cryptocurrency concepts.'
    }
};

const t = (key) => {
    return translations[locale.value]?.[key] || key;
};

const seoTitle = computed(() => t('seoTitle'));

const seoDescription = computed(() => t('seoDescription'));
</script>