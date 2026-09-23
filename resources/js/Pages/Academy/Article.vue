<template>
    <Head>
        <title>{{ seoTitle }}</title>

        <link
            rel="canonical"
            :href="canonicalUrl"
        />

        <meta
            head-key="description"
            name="description"
            :content="seoDescription"
        />

        <meta
            head-key="robots"
            name="robots"
            content="index, follow"
        />

        <!-- Open Graph -->
        <meta
            head-key="og:type"
            property="og:type"
            content="article"
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
            :content="articleImage"
        />

        <meta
            head-key="og:site_name"
            property="og:site_name"
            content="AQL Crypto"
        />

        <meta
            head-key="og:locale"
            property="og:locale"
            :content="locale === 'ar' ? 'ar_AR' : 'en_US'"
        />

        <!-- Twitter Card -->
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
            :content="articleImage"
        />

        <!-- Article Structured Data -->
        <component
            is="script"
            type="application/ld+json"
            head-key="academy-article-jsonld"
        >
            {{ JSON.stringify(articleJsonLd) }}
        </component>

        <!-- Breadcrumb Structured Data -->
        <component
            is="script"
            type="application/ld+json"
            head-key="academy-breadcrumb-jsonld"
        >
            {{ JSON.stringify(breadcrumbJsonLd) }}
        </component>

        <!-- FAQ Structured Data -->
        <component
            v-if="faqJsonLd"
            is="script"
            type="application/ld+json"
            head-key="academy-faq-jsonld"
        >
            {{ JSON.stringify(faqJsonLd) }}
        </component>
    </Head>

    <div
        class="min-h-screen bg-slate-50 text-slate-800 transition-colors duration-300 dark:bg-[#080d19] dark:text-slate-100"
    >
        <!-- ========================================================= -->
        <!-- Breadcrumb -->
        <!-- ========================================================= -->

        <section
            class="border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800/80 dark:bg-[#0b1220]/90"
        >
            <div
                class="mx-auto max-w-6xl px-4 py-5 sm:px-6 lg:px-8"
            >
                <nav
                    class="flex flex-wrap items-center gap-2 text-sm"
                    aria-label="Breadcrumb"
                >
                    <Link
                        href="/academy"
                        class="font-medium text-slate-500 transition-colors hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400"
                    >
                        {{ t('academy') }}
                    </Link>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-600"
                        :class="locale === 'ar' ? 'rotate-180' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>

                    <Link
                        :href="`/academy/${topic.slug}`"
                        class="font-medium text-slate-500 transition-colors hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400"
                    >
                        {{ topic.name }}
                    </Link>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-600"
                        :class="locale === 'ar' ? 'rotate-180' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>

                    <span
                        class="max-w-[260px] truncate font-semibold text-slate-800 dark:text-slate-200 sm:max-w-none"
                    >
                        {{ article.title }}
                    </span>
                </nav>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- Main -->
        <!-- ========================================================= -->

        <main
            class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8"
        >
            <article>
                <!-- ===================================================== -->
                <!-- Article Header -->
                <!-- ===================================================== -->

                <header
                    class="mx-auto max-w-4xl text-center"
                >
                    <!-- Topic badge -->
                    <Link
                        :href="`/academy/${topic.slug}`"
                        class="group inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold uppercase tracking-wider text-emerald-700 transition-all hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/15"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_0_4px_rgba(16,185,129,0.10)]"
                        ></span>

                        {{ topic.name }}
                    </Link>

                    <!-- Title -->
                    <h1
                        class="mt-6 text-3xl font-black leading-[1.2] tracking-tight text-slate-950 dark:text-white sm:text-4xl lg:text-5xl"
                    >
                        {{ article.title }}
                    </h1>

                    <!-- Excerpt -->
                    <p
                        v-if="article.excerpt"
                        class="mx-auto mt-6 max-w-3xl text-base leading-8 text-slate-600 dark:text-slate-400 sm:text-lg"
                    >
                        {{ article.excerpt }}
                    </p>

                    <!-- Meta -->
                    <div
                        class="mt-7 flex flex-wrap items-center justify-center gap-x-5 gap-y-3 text-sm text-slate-500 dark:text-slate-400"
                    >
                        <!-- Date -->
                        <span class="inline-flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0-2 2Z"
                                />
                            </svg>

                            <span>
                                {{ formatDate(article.published_at) }}
                            </span>
                        </span>

                        <span
                            class="hidden h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700 sm:block"
                        ></span>

                        <!-- Author -->
                        <span class="inline-flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M15 19a6 6 0 0 0-12 0m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6-1v6m3-3h-6"
                                />
                            </svg>

                            <span>
                                {{ t('author') }}
                            </span>
                        </span>

                        <span
                            class="hidden h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700 sm:block"
                        ></span>

                        <!-- Academy -->
                        <span
                            class="font-medium text-slate-600 dark:text-slate-300"
                        >
                            AQL Crypto Academy
                        </span>
                    </div>
                </header>

                <!-- ===================================================== -->
                <!-- Featured Image -->
                <!-- ===================================================== -->

                <figure
                    v-if="article.image"
                    class="mx-auto mt-10 max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-[#101827]"
                >
                    <img
                        :src="article.image"
                        :alt="article.title"
                        class="h-auto max-h-[560px] w-full object-cover"
                        loading="eager"
                        decoding="async"
                    />

                    <figcaption
                        class="border-t border-slate-100 px-5 py-3 text-center text-xs text-slate-500 dark:border-slate-800 dark:text-slate-500"
                    >
                        {{ article.title }}
                    </figcaption>
                </figure>

                <!-- ===================================================== -->
                <!-- Article Layout -->
                <!-- ===================================================== -->

                <div
                    class="mx-auto mt-10 grid max-w-5xl gap-8 lg:grid-cols-[minmax(0,1fr)_280px]"
                >
                    <!-- Main content -->
                    <div
                        class="min-w-0 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#101827] sm:p-8 lg:p-10"
                    >
                        <div
                            class="academy-content prose prose-slate max-w-none dark:prose-invert"
                            :class="
                                locale === 'ar'
                                    ? 'text-right'
                                    : 'text-left'
                            "
                            v-html="article.content"
                        ></div>
                    </div>

                    <!-- ================================================= -->
                    <!-- Internal Navigation -->
                    <!-- ================================================= -->

                    <aside
                        class="h-fit space-y-4 lg:sticky lg:top-24"
                        :class="
                            locale === 'ar'
                                ? 'text-right'
                                : 'text-left'
                        "
                    >
                        <!-- Academy -->
                        <Link
                            href="/academy"
                            class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-slate-800 dark:bg-[#101827] dark:hover:border-emerald-500/40"
                        >
                            <div
                                class="mb-3 flex items-center justify-between gap-3"
                            >
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
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
                                </span>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-slate-300 transition-transform group-hover:translate-x-0.5 group-hover:text-emerald-500 dark:text-slate-700"
                                    :class="
                                        locale === 'ar'
                                            ? 'rotate-180 group-hover:-translate-x-0.5'
                                            : ''
                                    "
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </div>

                            <span
                                class="block text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500"
                            >
                                {{ t('academy') }}
                            </span>

                            <span
                                class="mt-1 block font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ t('academyDescription') }}
                            </span>
                        </Link>

                        <!-- Topic -->
                        <Link
                            :href="`/academy/${topic.slug}`"
                            class="group block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-slate-800 dark:bg-[#101827] dark:hover:border-emerald-500/40"
                        >
                            <div
                                class="mb-3 flex items-center justify-between gap-3"
                            >
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M4 6.5A2.5 2.5 0 0 1 6.5 4H20v16H6.5A2.5 2.5 0 0 0 4 22V6.5Zm0 0V18m4-9h8m-8 4h5"
                                        />
                                    </svg>
                                </span>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-slate-300 transition-transform group-hover:translate-x-0.5 group-hover:text-emerald-500 dark:text-slate-700"
                                    :class="
                                        locale === 'ar'
                                            ? 'rotate-180 group-hover:-translate-x-0.5'
                                            : ''
                                    "
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </div>

                            <span
                                class="block text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500"
                            >
                                {{ t('topic') }}
                            </span>

                            <span
                                class="mt-1 block font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ topic.name }}
                            </span>
                        </Link>

                        <!-- Coin -->
                        <Link
                            v-if="coinPage"
                            :href="coinPage.url"
                            class="group block rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-emerald-500/20 dark:bg-emerald-500/5 dark:hover:border-emerald-500/40"
                        >
                            <div
                                class="mb-3 flex items-center justify-between gap-3"
                            >
                                <span
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-white font-black text-emerald-600 shadow-sm dark:bg-slate-900 dark:text-emerald-400"
                                >
                                    {{ coinPage.symbol }}
                                </span>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-emerald-500 transition-transform group-hover:translate-x-0.5"
                                    :class="
                                        locale === 'ar'
                                            ? 'rotate-180 group-hover:-translate-x-0.5'
                                            : ''
                                    "
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </div>

                            <span
                                class="block text-xs font-bold uppercase tracking-wide text-emerald-600/70 dark:text-emerald-400/70"
                            >
                                {{ t('marketPage') }}
                            </span>

                            <span
                                class="mt-1 block font-bold text-slate-900 dark:text-white"
                            >
                                {{ coinPage.name }}
                            </span>

                            <span
                                class="mt-2 block text-xs leading-6 text-slate-500 dark:text-slate-400"
                            >
                                {{ t('viewMarketData') }}
                            </span>
                        </Link>
                    </aside>
                </div>

                <!-- ===================================================== -->
                <!-- FAQ -->
                <!-- ===================================================== -->

                <section
                    v-if="faqs.length"
                    class="mx-auto mt-10 max-w-5xl rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-[#101827] sm:p-8 lg:p-10"
                    :dir="locale === 'ar' ? 'rtl' : 'ltr'"
                    :aria-labelledby="'faq-title'"
                >
                    <div
                        :class="
                            locale === 'ar'
                                ? 'text-right'
                                : 'text-left'
                        "
                    >
                        <div
                            class="mb-2 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M8.5 9.5a3.5 3.5 0 1 1 7 0c0 2.2-3.5 2.5-3.5 4.5m0 3.5h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                />
                            </svg>

                            {{ t('faqLabel') }}
                        </div>

                        <h2
                            id="faq-title"
                            class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                        >
                            {{ t('faqTitle') }}
                        </h2>

                        <p
                            class="mt-3 max-w-3xl text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-base"
                        >
                            {{ t('faqDescription') }}
                        </p>
                    </div>

                    <div class="mt-7 space-y-4">
                        <details
                            v-for="(faq, index) in faqs"
                            :key="`${faq.question}-${index}`"
                            class="group overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 transition-colors open:border-emerald-200 open:bg-emerald-50/30 dark:border-slate-700 dark:bg-slate-900/40 dark:open:border-emerald-500/30 dark:open:bg-emerald-500/5"
                        >
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between gap-5 px-5 py-5 font-bold text-slate-800 marker:hidden dark:text-slate-100 sm:px-6"
                            >
                                <span
                                    class="leading-7"
                                >
                                    {{ faq.question }}
                                </span>

                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm transition-transform duration-200 group-open:rotate-45 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M12 5v14m-7-7h14"
                                        />
                                    </svg>
                                </span>
                            </summary>

                            <div
                                class="border-t border-slate-200 px-5 pb-5 pt-4 text-sm leading-8 text-slate-600 dark:border-slate-700 dark:text-slate-300 sm:px-6"
                            >
                                {{ faq.answer }}
                            </div>
                        </details>
                    </div>
                </section>

                <!-- ===================================================== -->
                <!-- Bottom Navigation -->
                <!-- ===================================================== -->

                <div
                    class="mx-auto mt-8 max-w-5xl"
                >
                    <Link
                        :href="`/academy/${topic.slug}`"
                        class="group flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-emerald-300 hover:shadow-md dark:border-slate-800 dark:bg-[#101827] dark:hover:border-emerald-500/40"
                    >
                        <div
                            :class="
                                locale === 'ar'
                                    ? 'text-right'
                                    : 'text-left'
                            "
                        >
                            <span
                                class="block text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500"
                            >
                                {{ t('backToTopic') }}
                            </span>

                            <span
                                class="mt-1 block font-bold text-slate-800 dark:text-slate-100"
                            >
                                {{ topic.name }}
                            </span>
                        </div>

                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-transform group-hover:scale-105 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                :class="
                                    locale === 'ar'
                                        ? 'rotate-180'
                                        : ''
                                "
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </span>
                    </Link>
                </div>
            </article>
        </main>
    </div>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import HomeLayout from '@/layouts/HomeLayout.vue';

defineOptions({
    layout: HomeLayout,
});

const page = usePage();

const locale = computed(() => page.props.locale || 'ar');

const article = computed(() => page.props.article || {});

const topic = computed(() => article.value.topic || {});

/*
|--------------------------------------------------------------------------
| Canonical
|--------------------------------------------------------------------------
*/

const canonicalUrl = computed(() => {
    const cleanPath = page.url.split('?')[0];

    return `https://aqlcrypto.com${cleanPath}`;
});

/*
|--------------------------------------------------------------------------
| Article Image
|--------------------------------------------------------------------------
*/

const articleImage = computed(() => {
    return (
        article.value.image ||
        'https://aqlcrypto.com/images/default-og.jpg'
    );
});

/*
|--------------------------------------------------------------------------
| Translations
|--------------------------------------------------------------------------
*/

const translations = {
    ar: {
        academy: 'الأكاديمية',
        academyDescription: 'تعلّم أساسيات وتقنيات سوق العملات الرقمية',
        topic: 'الموضوع',
        author: 'Aql Crypto Editorial Team',
        marketPage: 'صفحة السوق',
        viewMarketData: 'عرض السعر والبيانات والتحليلات',
        backToTopic: 'العودة إلى الموضوع',

        faqLabel: 'الأسئلة الشائعة',
        faqTitle: 'الأسئلة الشائعة حول هذا الموضوع',
        faqDescription:
            'إجابات مختصرة عن أكثر الأسئلة شيوعًا التي قد تهم القارئ حول هذا الموضوع.'
    },

    en: {
        academy: 'Academy',
        academyDescription:
            'Learn the fundamentals and technologies of the crypto market',
        topic: 'Topic',
        author: 'Aql Crypto Editorial Team',
        marketPage: 'Market Page',
        viewMarketData: 'View price, market data and analysis',
        backToTopic: 'Back to Topic',

        faqLabel: 'Frequently Asked Questions',
        faqTitle: 'Frequently Asked Questions',
        faqDescription:
            'Clear answers to common questions readers may have about this topic.'
    }
};

const t = (key) => {
    return translations[locale.value]?.[key] || key;
};

/*
|--------------------------------------------------------------------------
| SEO Title
|--------------------------------------------------------------------------
*/

const seoTitle = computed(() => {
    return (
        article.value.seo_title ||
        `${article.value.title || 'Academy'} | AQL Crypto`
    );
});

/*
|--------------------------------------------------------------------------
| Meta Description
|--------------------------------------------------------------------------
*/

const seoDescription = computed(() => {
    return (
        article.value.meta_description ||
        article.value.excerpt ||
        (locale.value === 'ar'
            ? `تعرّف على ${article.value.title || 'العملات الرقمية'} من خلال أكاديمية AQL Crypto.`
            : `Learn about ${article.value.title || 'cryptocurrency'} through AQL Crypto Academy.`)
    );
});

/*
|--------------------------------------------------------------------------
| FAQ
|--------------------------------------------------------------------------
|
| The controller/model provides faq_ar and faq_en directly.
| We select the correct language here without modifying the
| original database structure.
|
*/

const faqs = computed(() => {
    const source =
        locale.value === 'ar'
            ? article.value.faq_ar
            : article.value.faq_en;

    return Array.isArray(source) ? source : [];
});

/*
|--------------------------------------------------------------------------
| FAQ Structured Data
|--------------------------------------------------------------------------
|
| Only generate FAQPage JSON-LD when visible FAQ content exists.
| The structured data uses exactly the same questions and answers
| rendered on the page.
|
*/

const faqJsonLd = computed(() => {
    if (!faqs.value.length) {
        return null;
    }

    return {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        '@id': `${canonicalUrl.value}#faq`,

        mainEntity: faqs.value
            .filter(
                (faq) =>
                    faq &&
                    typeof faq.question === 'string' &&
                    faq.question.trim() &&
                    typeof faq.answer === 'string' &&
                    faq.answer.trim()
            )
            .map((faq) => ({
                '@type': 'Question',
                name: faq.question,
                acceptedAnswer: {
                    '@type': 'Answer',
                    text: faq.answer
                }
            }))
    };
});

/*
|--------------------------------------------------------------------------
| Coin Internal Links
|--------------------------------------------------------------------------
|
| These links connect educational content with the existing market pages.
| Only verified coin routes are used.
|
*/

const coinPage = computed(() => {
    const slug = String(topic.value.slug || '').toLowerCase();

    const coins = {
        bitcoin: {
            symbol: 'BTC',
            name: locale.value === 'ar' ? 'بيتكوين' : 'Bitcoin',
            url: '/crypto/BTC'
        },

        ethereum: {
            symbol: 'ETH',
            name: locale.value === 'ar' ? 'إيثريوم' : 'Ethereum',
            url: '/crypto/ETH'
        },

        solana: {
            symbol: 'SOL',
            name: locale.value === 'ar' ? 'سولانا' : 'Solana',
            url: '/crypto/SOL'
        },

        xrp: {
            symbol: 'XRP',
            name: 'XRP',
            url: '/crypto/XRP'
        },

        cardano: {
            symbol: 'ADA',
            name: 'Cardano',
            url: '/crypto/ADA'
        }
    };

    return coins[slug] || null;
});

/*
|--------------------------------------------------------------------------
| Date Formatting
|--------------------------------------------------------------------------
*/

const formatDate = (date) => {
    if (!date) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat(
            locale.value === 'ar' ? 'ar' : 'en',
            {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }
        ).format(new Date(date));
    } catch {
        return '';
    }
};

/*
|--------------------------------------------------------------------------
| Article JSON-LD
|--------------------------------------------------------------------------
*/

const articleJsonLd = computed(() => {
    const publishedAt = article.value.published_at
        ? new Date(article.value.published_at).toISOString()
        : null;

    const updatedAt = article.value.updated_at
        ? new Date(article.value.updated_at).toISOString()
        : publishedAt;

    return {
        '@context': 'https://schema.org',
        '@type': 'Article',
        '@id': `${canonicalUrl.value}#article`,

        headline: article.value.title || '',

        description: seoDescription.value,

        image: [
            articleImage.value
        ],

        datePublished: publishedAt,
        dateModified: updatedAt,

        inLanguage: locale.value === 'ar' ? 'ar' : 'en',

        articleSection: topic.value.name || (
            locale.value === 'ar'
                ? 'الأكاديمية'
                : 'Academy'
        ),

        author: {
            '@type': 'Organization',
            '@id': 'https://aqlcrypto.com/#editorial-team',
            name: 'Aql Crypto Editorial Team',
            url: 'https://aqlcrypto.com/about'
        },

        publisher: {
            '@type': 'Organization',
            '@id': 'https://aqlcrypto.com/#organization',
            name: 'AQL Crypto',
            url: 'https://aqlcrypto.com',
            logo: {
                '@type': 'ImageObject',
                url: 'https://aqlcrypto.com/images/logos/logo-horizontal-dark.webp'
            }
        },

        mainEntityOfPage: {
            '@type': 'WebPage',
            '@id': canonicalUrl.value
        },

        isPartOf: {
            '@type': 'WebSite',
            '@id': 'https://aqlcrypto.com/#website',
            name: 'AQL Crypto',
            url: 'https://aqlcrypto.com'
        },

        about: {
            '@type': 'Thing',
            name: topic.value.name || (
                locale.value === 'ar'
                    ? 'العملات الرقمية'
                    : 'Cryptocurrency'
            )
        }
    };
});

/*
|--------------------------------------------------------------------------
| Breadcrumb JSON-LD
|--------------------------------------------------------------------------
*/

const breadcrumbJsonLd = computed(() => {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        '@id': `${canonicalUrl.value}#breadcrumb`,

        itemListElement: [
            {
                '@type': 'ListItem',
                position: 1,
                name:
                    locale.value === 'ar'
                        ? 'الأكاديمية'
                        : 'Academy',
                item: 'https://aqlcrypto.com/academy'
            },

            {
                '@type': 'ListItem',
                position: 2,
                name: topic.value.name || '',
                item: `https://aqlcrypto.com/academy/${topic.value.slug || ''}`
            },

            {
                '@type': 'ListItem',
                position: 3,
                name: article.value.title || '',
                item: canonicalUrl.value
            }
        ]
    };
});
</script>

<style scoped>
.academy-content {
    font-size: 1.05rem;
    line-height: 2;
    color: rgb(51 65 85);
}

.academy-content :deep(h2) {
    position: relative;
    margin-top: 2.75rem;
    margin-bottom: 1.15rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgb(226 232 240);
    font-size: 1.55rem;
    font-weight: 900;
    line-height: 1.4;
    color: rgb(15 23 42);
}

.academy-content :deep(h2:first-child) {
    margin-top: 0;
}

.academy-content :deep(h3) {
    margin-top: 2rem;
    margin-bottom: 0.8rem;
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1.5;
    color: rgb(15 23 42);
}

.academy-content :deep(h4) {
    margin-top: 1.5rem;
    margin-bottom: 0.65rem;
    font-size: 1.1rem;
    font-weight: 800;
    color: rgb(15 23 42);
}

.academy-content :deep(p) {
    margin-top: 1.1rem;
    margin-bottom: 1.1rem;
}

.academy-content :deep(ul),
.academy-content :deep(ol) {
    margin-top: 1.25rem;
    margin-bottom: 1.25rem;
    padding-inline-start: 1.5rem;
}

.academy-content :deep(li) {
    margin-top: 0.6rem;
}

.academy-content :deep(li::marker) {
    color: rgb(16 185 129);
}

.academy-content :deep(a) {
    color: rgb(5 150 105);
    font-weight: 700;
    text-decoration: none;
    transition: color 0.2s ease;
}

.academy-content :deep(a:hover) {
    color: rgb(4 120 87);
    text-decoration: underline;
}

.academy-content :deep(strong) {
    font-weight: 800;
    color: rgb(15 23 42);
}

.academy-content :deep(blockquote) {
    margin: 1.75rem 0;
    padding: 1.15rem 1.35rem;
    border-inline-start: 4px solid rgb(16 185 129);
    border-radius: 0.9rem;
    background: rgb(240 253 250);
    color: rgb(51 65 85);
}

.academy-content :deep(img) {
    width: 100%;
    height: auto;
    margin: 2rem auto;
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
}

.academy-content :deep(table) {
    width: 100%;
    margin: 1.75rem 0;
    overflow: hidden;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid rgb(226 232 240);
    border-radius: 0.9rem;
}

.academy-content :deep(th),
.academy-content :deep(td) {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid rgb(226 232 240);
    text-align: inherit;
}

.academy-content :deep(th) {
    background: rgb(248 250 252);
    font-weight: 800;
}

.academy-content :deep(tr:last-child td) {
    border-bottom: 0;
}

.academy-content :deep(code) {
    padding: 0.15rem 0.4rem;
    border-radius: 0.4rem;
    background: rgb(241 245 249);
    font-size: 0.9em;
}

.academy-content :deep(pre) {
    overflow-x: auto;
    margin: 1.75rem 0;
    padding: 1.25rem;
    border-radius: 1rem;
    background: rgb(15 23 42);
    color: rgb(226 232 240);
}

.dark .academy-content {
    color: rgb(203 213 225);
}

.dark .academy-content :deep(h2),
.dark .academy-content :deep(h3),
.dark .academy-content :deep(h4) {
    color: rgb(248 250 252);
}

.dark .academy-content :deep(h2) {
    border-bottom-color: rgb(30 41 59);
}

.dark .academy-content :deep(strong) {
    color: rgb(248 250 252);
}

.dark .academy-content :deep(blockquote) {
    background: rgba(16, 185, 129, 0.08);
    color: rgb(203 213 225);
}

.dark .academy-content :deep(img) {
    border-color: rgb(30 41 59);
}

.dark .academy-content :deep(table) {
    border-color: rgb(30 41 59);
}

.dark .academy-content :deep(th),
.dark .academy-content :deep(td) {
    border-bottom-color: rgb(30 41 59);
}

.dark .academy-content :deep(th) {
    background: rgb(15 23 42);
}

.dark .academy-content :deep(code) {
    background: rgb(30 41 59);
    color: rgb(226 232 240);
}

/*
|--------------------------------------------------------------------------
| FAQ
|--------------------------------------------------------------------------
*/

details > summary::-webkit-details-marker {
    display: none;
}

details > summary {
    list-style: none;
}

details > summary::marker {
    display: none;
}
</style>