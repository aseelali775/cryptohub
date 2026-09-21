<template>
    <Head>
        <title>{{ seoTitle }}</title>

        <link rel="canonical" :href="canonicalUrl" />

        <meta
            head-key="description"
            name="description"
            :content="seoDescription"
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

        <!-- Twitter -->
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

        <!-- Article structured data -->
<component
    is="script"
    type="application/ld+json"
    head-key="academy-article-jsonld"
>
    {{ JSON.stringify(articleJsonLd) }}
</component>

<!-- Breadcrumb structured data -->
<component
    is="script"
    type="application/ld+json"
    head-key="academy-breadcrumb-jsonld"
>
    {{ JSON.stringify(breadcrumbJsonLd) }}
</component>
    </Head>

    <div
        class="min-h-screen bg-slate-50 dark:bg-[#0b1121] text-slate-800 dark:text-slate-100 transition-colors duration-300"
    >
        <!-- Header / Breadcrumb -->
        <section
            class="border-b border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0f172a]"
        >
            <div
                class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8 py-6"
            >
                <nav
                    class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400"
                    aria-label="Breadcrumb"
                >
                    <Link
                        href="/academy"
                        class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        {{ t('academy') }}
                    </Link>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4 shrink-0"
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
                        class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
                    >
                        {{ topic.name }}
                    </Link>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4 shrink-0"
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
                        class="font-semibold text-slate-700 dark:text-slate-200"
                    >
                        {{ article.title }}
                    </span>
                </nav>
            </div>
        </section>

        <!-- Article -->
        <main
            class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14"
        >
            <article>
                <!-- Article header -->
                <header class="text-center max-w-4xl mx-auto">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider mb-5"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-emerald-500"
                        ></span>

                        {{ topic.name }}
                    </div>

                    <h1
                        class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-slate-900 dark:text-white leading-tight"
                    >
                        {{ article.title }}
                    </h1>

                    <p
                        v-if="article.excerpt"
                        class="mt-5 text-base sm:text-lg leading-8 text-slate-600 dark:text-slate-400 max-w-3xl mx-auto"
                    >
                        {{ article.excerpt }}
                    </p>

                    <div
                        class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm text-slate-500 dark:text-slate-400"
                    >
                        <span class="inline-flex items-center gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"
                                />
                            </svg>

                            {{ formatDate(article.published_at) }}
                        </span>

                        <span
                            class="hidden sm:block w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"
                        ></span>

                        <span>
                            AQL Crypto Academy
                        </span>
                    </div>
                </header>

                <!-- Featured image -->
                <div
                    v-if="article.image"
                    class="mt-10 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#121927]"
                >
                    <img
                        :src="article.image"
                        :alt="article.title"
                        class="w-full max-h-[520px] object-cover"
                    />
                </div>

                <!-- Content -->
                <div
                    class="mt-10 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#121927] p-6 sm:p-8 lg:p-10"
                >
                    <div
                        class="academy-content prose prose-slate dark:prose-invert max-w-none"
                        :class="locale === 'ar' ? 'text-right' : 'text-left'"
                        v-html="article.content"
                    ></div>
                </div>

                <!-- Navigation -->
                <div
                    class="mt-8 flex flex-col sm:flex-row gap-4"
                >
                    <Link
                        :href="`/academy/${topic.slug}`"
                        class="flex-1 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#121927] p-5 hover:border-emerald-300 dark:hover:border-emerald-500/40 transition-colors"
                    >
                        <span
                            class="block text-xs font-bold text-slate-400 dark:text-slate-500 mb-2"
                        >
                            {{ t('backToTopic') }}
                        </span>

                        <span
                            class="flex items-center justify-between gap-3 font-bold text-slate-800 dark:text-slate-100"
                        >
                            {{ topic.name }}

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-emerald-500 shrink-0"
                                :class="locale === 'ar'
                                    ? 'rotate-180'
                                    : ''"
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

const page = usePage();

const locale = computed(() => page.props.locale || 'ar');

const article = computed(() => page.props.article || {});

const topic = computed(() => article.value.topic || {});

const canonicalUrl = computed(() => {
    const cleanPath = page.url.split('?')[0];

    return `https://aqlcrypto.com${cleanPath}`;
});

const articleImage = computed(() => {
    return (
        article.value.image ||
        'https://aqlcrypto.com/images/default-og.jpg'
    );
});

const translations = {
    ar: {
        academy: 'الأكاديمية',
        backToTopic: 'العودة إلى الموضوع'
    },

    en: {
        academy: 'Academy',
        backToTopic: 'Back to Topic'
    }
};

const t = (key) => {
    return translations[locale.value]?.[key] || key;
};

const seoTitle = computed(() => {
    return (
        article.value.seo_title ||
        `${article.value.title || 'Academy'} | AQL Crypto`
    );
});

const seoDescription = computed(() => {
    return (
        article.value.meta_description ||
        article.value.excerpt ||
        `Learn about ${article.value.title || 'cryptocurrency'} through AQL Crypto Academy.`
    );
});

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

const articleJsonLd = computed(() => {
    const publishedAt = article.value.published_at
        ? new Date(article.value.published_at).toISOString()
        : new Date().toISOString();

    const updatedAt = article.value.updated_at
        ? new Date(article.value.updated_at).toISOString()
        : publishedAt;

    return {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: article.value.title || '',
        description: seoDescription.value,
        image: [articleImage.value],
        datePublished: publishedAt,
        dateModified: updatedAt,
        author: {
            '@type': 'Organization',
            name: 'Aql Crypto Editorial Team',
            url: 'https://aqlcrypto.com/about'
        },
        publisher: {
            '@type': 'Organization',
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
        articleSection: topic.value.name || 'Academy'
    };
});

const breadcrumbJsonLd = computed(() => {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            {
                '@type': 'ListItem',
                position: 1,
                name: locale.value === 'ar' ? 'الأكاديمية' : 'Academy',
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
    font-size: 1rem;
    line-height: 2;
}

.academy-content :deep(h2) {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    font-weight: 800;
    color: rgb(15 23 42);
}

.academy-content :deep(h3) {
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
    font-weight: 800;
    color: rgb(15 23 42);
}

.academy-content :deep(p) {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.academy-content :deep(ul),
.academy-content :deep(ol) {
    margin-top: 1rem;
    margin-bottom: 1rem;
    padding-inline-start: 1.5rem;
}

.academy-content :deep(li) {
    margin-top: 0.5rem;
}

.academy-content :deep(a) {
    color: rgb(5 150 105);
    font-weight: 700;
}

.academy-content :deep(blockquote) {
    margin: 1.5rem 0;
    padding: 1rem 1.25rem;
    border-inline-start: 4px solid rgb(16 185 129);
    background: rgb(240 253 250);
    border-radius: 0.75rem;
}

.dark .academy-content :deep(h2),
.dark .academy-content :deep(h3) {
    color: rgb(248 250 252);
}

.dark .academy-content :deep(blockquote) {
    background: rgba(16, 185, 129, 0.08);
}

.academy-content :deep(code) {
    padding: 0.15rem 0.35rem;
    border-radius: 0.35rem;
    background: rgb(241 245 249);
    font-size: 0.9em;
}

.dark .academy-content :deep(code) {
    background: rgb(30 41 59);
}
</style>