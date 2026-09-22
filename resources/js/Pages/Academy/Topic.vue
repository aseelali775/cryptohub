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
                class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16"
            >
                <!-- Breadcrumb -->
                <nav
                    class="flex items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-8"
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
                        class="w-4 h-4"
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
                        {{ topic.name }}
                    </span>
                </nav>

                <div class="max-w-3xl mx-auto text-center">
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider mb-5"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-emerald-500"
                        ></span>

                        AQL Crypto Academy
                    </div>

                    <h1
                        class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 dark:text-white leading-tight"
                    >
                        {{ topic.name }}
                    </h1>

                    <p
                        v-if="topic.description"
                        class="mt-5 text-base sm:text-lg leading-8 text-slate-600 dark:text-slate-400 max-w-2xl mx-auto"
                    >
                        {{ topic.description }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Articles -->
        <main
            class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8"
            >
                <div>
                    <p
                        class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mb-2"
                    >
                        {{ t('learningPath') }}
                    </p>

                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white"
                    >
                        {{ t('lessons') }}
                    </h2>
                </div>

                <div
                    class="text-sm text-slate-500 dark:text-slate-400"
                >
                    {{ articles.length }} {{ t('lessonCount') }}
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="articles.length === 0"
                class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#121927] p-10 sm:p-14 text-center"
            >
                <div
                    class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center"
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

                <h3
                    class="text-xl font-black text-slate-900 dark:text-white"
                >
                    {{ t('comingSoon') }}
                </h3>

                <p
                    class="mt-3 max-w-xl mx-auto text-sm leading-7 text-slate-500 dark:text-slate-400"
                >
                    {{ t('comingSoonDescription') }}
                </p>

                <Link
                    href="/academy"
                    class="inline-flex items-center gap-2 mt-7 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold transition-colors"
                >
                    {{ t('backToAcademy') }}

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4"
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
                </Link>
            </div>

            <!-- Articles list -->
            <div
                v-else
                class="grid grid-cols-1 md:grid-cols-2 gap-5"
            >
                <Link
                    v-for="article in articles"
                    :key="article.id"
                    :href="`/academy/${topic.slug}/${article.slug}`"
                    class="group rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#121927] overflow-hidden hover:border-emerald-300 dark:hover:border-emerald-500/40 hover:-translate-y-1 transition-all duration-300 shadow-sm hover:shadow-lg"
                >
                    <!-- Image -->
                    <div
                        v-if="article.image"
                        class="h-48 overflow-hidden bg-slate-100 dark:bg-slate-800"
                    >
                        <img
                            :src="article.image"
                            :alt="article.title"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                    </div>

                    <div
                        v-else
                        class="h-32 bg-gradient-to-br from-emerald-50 via-slate-50 to-white dark:from-emerald-950/20 dark:via-[#151e32] dark:to-[#121927] flex items-center justify-center"
                    >
                        <div
                            class="w-12 h-12 rounded-xl bg-white dark:bg-[#0b1121] border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-6 h-6 text-emerald-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.7"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18.477 18.246 18.477 19.832 18 21 18 21 17.253"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5">
                        <h3
                            class="text-xl font-black text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"
                        >
                            {{ article.title }}
                        </h3>

                        <p
                            v-if="article.excerpt"
                            class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400 line-clamp-3"
                        >
                            {{ article.excerpt }}
                        </p>

                        <div
                            class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between"
                        >
                            <span
                                class="text-xs font-semibold text-slate-500 dark:text-slate-400"
                            >
                                {{ formatDate(article.published_at) }}
                            </span>

                            <span
                                class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                {{ t('readLesson') }}

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4"
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
import HomeLayout from '@/layouts/HomeLayout.vue';

defineOptions({
    layout: HomeLayout,
});

const page = usePage();

const locale = computed(() => page.props.locale || 'ar');

const topic = computed(() => page.props.topic || {});

const articles = computed(() => topic.value.articles || []);

const canonicalUrl = computed(() => {
    const cleanPath = page.url.split('?')[0];

    return `https://aqlcrypto.com${cleanPath}`;
});

const translations = {
    ar: {
        academy: 'الأكاديمية',
        learningPath: 'مسار التعلم',
        lessons: 'دروس الموضوع',
        lessonCount: 'درس',
        comingSoon: 'الدروس التعليمية قادمة قريبًا',
        comingSoonDescription:
            'نعمل حاليًا على إعداد الدروس التعليمية لهذا الموضوع. ستظهر الدروس هنا بمجرد نشرها.',
        backToAcademy: 'العودة إلى الأكاديمية',
        readLesson: 'اقرأ الدرس'
    },

    en: {
        academy: 'Academy',
        learningPath: 'Learning Path',
        lessons: 'Topic Lessons',
        lessonCount: 'lessons',
        comingSoon: 'Lessons are coming soon',
        comingSoonDescription:
            'We are currently preparing educational lessons for this topic. They will appear here once published.',
        backToAcademy: 'Back to Academy',
        readLesson: 'Read Lesson'
    }
};

const t = (key) => {
    return translations[locale.value]?.[key] || key;
};

const seoTitle = computed(() => {
    const name = topic.value.name || 'Academy';

    return locale.value === 'ar'
        ? `${name} | أكاديمية AQL Crypto`
        : `${name} | AQL Crypto Academy`;
});

const seoDescription = computed(() => {
    if (topic.value.description) {
        return topic.value.description;
    }

    return locale.value === 'ar'
        ? `تعلم أساسيات ${topic.value.name || 'العملات الرقمية'} من خلال أكاديمية AQL Crypto.`
        : `Learn the fundamentals of ${topic.value.name || 'cryptocurrency'} through AQL Crypto Academy.`;
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
                month: 'short',
                day: 'numeric'
            }
        ).format(new Date(date));
    } catch {
        return '';
    }
};
</script>