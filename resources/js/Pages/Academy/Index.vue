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
            head-key="og:site_name"
            property="og:site_name"
            content="AQL Crypto"
        />

        <meta
            head-key="og:locale"
            property="og:locale"
            :content="locale === 'ar' ? 'ar_YE' : 'en_US'"
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
            content="https://aqlcrypto.com/images/default-og.jpg"
        />

        <!-- Academy CollectionPage JSON-LD -->
        <component
            is="script"
            type="application/ld+json"
            head-key="academy-index-jsonld"
        >
            {{ JSON.stringify(academyJsonLd) }}
        </component>

        <!-- Breadcrumb JSON-LD -->
        <component
            is="script"
            type="application/ld+json"
            head-key="academy-index-breadcrumb-jsonld"
        >
            {{ JSON.stringify(breadcrumbJsonLd) }}
        </component>
    </Head>

    <div
        class="min-h-screen bg-slate-50 dark:bg-[#080d18] text-slate-800 dark:text-slate-100 transition-colors duration-300"
    >
        <!-- =========================================================
             HERO
        ========================================================== -->
        <section
            class="relative overflow-hidden border-b border-slate-200 dark:border-slate-800"
        >
            <!-- Background -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-slate-100 dark:from-emerald-950/25 dark:via-[#080d18] dark:to-[#0b1121]"
            ></div>

            <!-- Decorative glows -->
            <div
                class="absolute -top-40 -end-32 w-[30rem] h-[30rem] rounded-full bg-emerald-400/10 blur-3xl"
            ></div>

            <div
                class="absolute -bottom-48 -start-32 w-[32rem] h-[32rem] rounded-full bg-cyan-400/5 blur-3xl"
            ></div>

            <!-- Technical grid -->
            <div
                class="absolute inset-0 opacity-[0.035] dark:opacity-[0.05]"
                style="
                    background-image:
                        linear-gradient(currentColor 1px, transparent 1px),
                        linear-gradient(90deg, currentColor 1px, transparent 1px);
                    background-size: 32px 32px;
                "
            ></div>

            <div
                class="relative max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-24"
            >
                <div
                    class="grid lg:grid-cols-[1fr_380px] gap-12 lg:gap-20 items-center"
                >
                    <!-- Hero Content -->
                    <div class="max-w-3xl">
                        <!-- Label -->
                        <div
                            class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-full bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200/80 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-xs font-bold tracking-wide mb-7"
                        >
                            <span class="relative flex w-2 h-2">
                                <span
                                    class="absolute inline-flex w-full h-full rounded-full bg-emerald-400 opacity-60 animate-ping"
                                ></span>

                                <span
                                    class="relative inline-flex w-2 h-2 rounded-full bg-emerald-500"
                                ></span>
                            </span>

                            {{ t('academyLabel') }}
                        </div>

                        <!-- Title -->
                        <h1
                            class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black tracking-tight text-slate-950 dark:text-white leading-[1.06]"
                        >
                            {{ t('academyTitle') }}
                        </h1>

                        <!-- Description -->
                        <p
                            class="mt-7 max-w-2xl text-base sm:text-lg lg:text-xl leading-8 text-slate-600 dark:text-slate-400"
                        >
                            {{ t('academyDescription') }}
                        </p>

                        <!-- CTA -->
                        <div
                            class="mt-9 flex flex-wrap items-center gap-3"
                        >
                            <a
                                href="#topics"
                                class="inline-flex items-center justify-center gap-2.5 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-lg shadow-emerald-600/20 hover:-translate-y-0.5 transition-all"
                            >
                                {{ t('startLearning') }}

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
                                        d="M12 5v14m0 0l-6-6m6 6l6-6"
                                    />
                                </svg>
                            </a>

                            <span
                                class="text-sm text-slate-500 dark:text-slate-400"
                            >
                                {{ t('learnAtYourPace') }}
                            </span>
                        </div>
                    </div>

                    <!-- Academy Visual -->
                    <div class="hidden lg:block">
                        <div class="relative mx-auto w-[330px] h-[330px]">
                            <!-- Outer glow -->
                            <div
                                class="absolute inset-4 rounded-[3rem] bg-emerald-400/10 blur-2xl"
                            ></div>

                            <!-- Main card -->
                            <div
                                class="absolute inset-0 rounded-[3rem] bg-white/80 dark:bg-[#101827]/90 border border-emerald-200/70 dark:border-emerald-500/20 shadow-2xl overflow-hidden"
                            >
                                <!-- Decorative circles -->
                                <div
                                    class="absolute -top-20 -end-20 w-56 h-56 rounded-full border border-emerald-300/20 dark:border-emerald-500/10"
                                ></div>

                                <div
                                    class="absolute -bottom-24 -start-20 w-64 h-64 rounded-full border border-cyan-300/20 dark:border-cyan-500/10"
                                ></div>

                                <!-- Content -->
                                <div
                                    class="relative h-full flex flex-col items-center justify-center text-center px-8"
                                >
                                    <div
                                        class="w-20 h-20 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center mb-5 shadow-sm"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-10 h-10 text-emerald-500"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.4"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                                            />
                                        </svg>
                                    </div>

                                    <div
                                        class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-400"
                                    >
                                        AQL CRYPTO
                                    </div>

                                    <div
                                        class="mt-2 text-2xl font-black text-slate-950 dark:text-white"
                                    >
                                        Academy
                                    </div>

                                    <div
                                        class="mt-2 text-sm text-slate-500 dark:text-slate-400"
                                    >
                                        {{ topics.length }}
                                        {{ t('academyTopicCount') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Floating stat -->
                            <div
                                class="absolute -top-4 -start-5 px-4 py-3 rounded-2xl bg-white dark:bg-[#111a2b] border border-slate-200 dark:border-slate-700 shadow-xl"
                            >
                                <div
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                >
                                    {{ t('knowledge') }}
                                </div>

                                <div
                                    class="mt-1 text-sm font-black text-slate-900 dark:text-white"
                                >
                                    {{ t('structuredLearning') }}
                                </div>
                            </div>

                            <!-- Floating stat -->
                            <div
                                class="absolute -bottom-4 -end-5 px-4 py-3 rounded-2xl bg-white dark:bg-[#111a2b] border border-slate-200 dark:border-slate-700 shadow-xl"
                            >
                                <div
                                    class="text-[10px] font-bold uppercase tracking-wider text-slate-400"
                                >
                                    AQL
                                </div>

                                <div
                                    class="mt-1 flex items-center gap-1.5 text-sm font-black text-emerald-600 dark:text-emerald-400"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-emerald-500"
                                    ></span>

                                    {{ t('learnExplore') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========================================================
             STATS
        ========================================================== -->
        <section
            class="relative border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-[#0b1121]/80"
        >
            <div
                class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="grid grid-cols-2 md:grid-cols-3 divide-x rtl:divide-x-reverse divide-slate-200 dark:divide-slate-800"
                >
                    <!-- Topics -->
                    <div class="px-4 sm:px-8 py-6 sm:py-7 text-center">
                        <div
                            class="text-2xl sm:text-3xl font-black text-slate-950 dark:text-white"
                        >
                            {{ topics.length }}
                        </div>

                        <div
                            class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400"
                        >
                            {{ t('academyTopicCount') }}
                        </div>
                    </div>

                    <!-- Lessons -->
                    <div class="px-4 sm:px-8 py-6 sm:py-7 text-center">
                        <div
                            class="text-2xl sm:text-3xl font-black text-slate-950 dark:text-white"
                        >
                            {{ totalLessons }}
                        </div>

                        <div
                            class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400"
                        >
                            {{ t('totalLessons') }}
                        </div>
                    </div>

                    <!-- Learning -->
                    <div
                        class="col-span-2 md:col-span-1 px-4 sm:px-8 py-6 sm:py-7 text-center"
                    >
                        <div
                            class="flex items-center justify-center gap-2 text-emerald-600 dark:text-emerald-400"
                        >
                            <span
                                class="w-2 h-2 rounded-full bg-emerald-500"
                            ></span>

                            <span
                                class="text-sm font-black"
                            >
                                {{ t('alwaysGrowing') }}
                            </span>
                        </div>

                        <div
                            class="mt-1 text-xs sm:text-sm text-slate-500 dark:text-slate-400"
                        >
                            {{ t('academyStatus') }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========================================================
             TOPICS
        ========================================================== -->
        <main
            id="topics"
            class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-18 lg:py-20"
        >
            <!-- Heading -->
            <div
                class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5 mb-9"
            >
                <div>
                    <p
                        class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mb-2"
                    >
                        {{ t('academyBrowse') }}
                    </p>

                    <h2
                        class="text-3xl sm:text-4xl font-black text-slate-950 dark:text-white tracking-tight"
                    >
                        {{ t('academyTopics') }}
                    </h2>

                    <p
                        class="mt-3 max-w-2xl text-sm sm:text-base leading-7 text-slate-500 dark:text-slate-400"
                    >
                        {{ t('topicsDescription') }}
                    </p>
                </div>

                <div
                    class="shrink-0 inline-flex items-center gap-2.5 text-sm font-semibold text-slate-500 dark:text-slate-400"
                >
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-black"
                    >
                        {{ topics.length }}
                    </span>

                    {{ t('academyTopicCount') }}
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="topics.length === 0"
                class="relative overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#101827] p-10 sm:p-16 text-center shadow-sm"
            >
                <div
                    class="absolute inset-0 bg-gradient-to-br from-emerald-50/70 via-transparent to-transparent dark:from-emerald-500/[0.04]"
                ></div>

                <div class="relative">
                    <div
                        class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-9 h-9 text-emerald-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.7"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18s-3.332 0-4.5 1.253"
                            />
                        </svg>
                    </div>

                    <h3
                        class="text-xl sm:text-2xl font-black text-slate-950 dark:text-white"
                    >
                        {{ t('academyComingSoon') }}
                    </h3>

                    <p
                        class="mt-3 max-w-xl mx-auto text-sm leading-7 text-slate-500 dark:text-slate-400"
                    >
                        {{ t('academyComingSoonDescription') }}
                    </p>
                </div>
            </div>

            <!-- Topics Grid -->
            <div
                v-else
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"
            >
                <Link
                    v-for="(topic, index) in topics"
                    :key="topic.id"
                    :href="`/academy/${topic.slug}`"
                    class="group relative block overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#101827] shadow-sm hover:shadow-2xl hover:shadow-slate-900/5 dark:hover:shadow-black/20 hover:-translate-y-1 transition-all duration-300"
                    :class="topic.articles_count > 0
                        ? 'hover:border-emerald-300 dark:hover:border-emerald-500/40'
                        : ''"
                >
                    <!-- Image -->
                    <div
                        v-if="topic.image"
                        class="relative h-44 overflow-hidden bg-slate-100 dark:bg-slate-900"
                    >
                        <img
                            :src="topic.image"
                            :alt="topic.name"
                            loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                        />

                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"
                        ></div>

                        <div
                            class="absolute top-4 start-4 w-9 h-9 rounded-xl bg-black/20 backdrop-blur-md border border-white/20 flex items-center justify-center text-white text-xs font-black"
                        >
                            {{ String(index + 1).padStart(2, '0') }}
                        </div>
                    </div>

                    <!-- Placeholder -->
                    <div
                        v-else
                        class="relative h-44 overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-slate-100 dark:from-emerald-950/25 dark:via-[#111a2b] dark:to-[#101827]"
                    >
                        <div
                            class="absolute -top-16 -end-16 w-44 h-44 rounded-full border border-emerald-300/20 dark:border-emerald-500/10"
                        ></div>

                        <div
                            class="absolute -bottom-20 -start-12 w-48 h-48 rounded-full border border-cyan-300/20 dark:border-cyan-500/10"
                        ></div>

                        <div
                            class="absolute top-4 start-4 w-9 h-9 rounded-xl bg-white/70 dark:bg-[#0b1121]/70 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xs font-black text-slate-500 dark:text-slate-400"
                        >
                            {{ String(index + 1).padStart(2, '0') }}
                        </div>

                        <div
                            class="relative h-full flex items-center justify-center"
                        >
                            <div
                                class="w-16 h-16 rounded-2xl bg-white/90 dark:bg-[#0b1121] border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-300"
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
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18 18.246 18 16.5 18s-3.332 0-4.5 1.253"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5 sm:p-6">
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <div
                                    class="text-[10px] font-black uppercase tracking-[0.15em] text-emerald-600 dark:text-emerald-400 mb-2"
                                >
                                    {{ t('learningTrack') }}
                                </div>

                                <h3
                                    class="text-xl font-black text-slate-950 dark:text-white leading-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors"
                                >
                                    {{ topic.name }}
                                </h3>
                            </div>

                            <div
                                class="shrink-0 w-9 h-9 rounded-xl bg-slate-50 dark:bg-slate-800/80 flex items-center justify-center text-slate-300 dark:text-slate-600 group-hover:bg-emerald-50 dark:group-hover:bg-emerald-500/10 group-hover:text-emerald-500 transition-all"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4 transition-transform duration-300"
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
                        </div>

                        <p
                            v-if="topic.description"
                            class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400 line-clamp-3"
                        >
                            {{ topic.description }}
                        </p>

                        <div
                            class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3"
                        >
                            <div
                                class="inline-flex items-center gap-2"
                            >
                                <span
                                    class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.7"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5 21 6.253v13C19.832 18 18.246 18 16.5 18s-3.332 0-4.5 1.253"
                                        />
                                    </svg>
                                </span>

                                <span
                                    class="text-xs font-bold text-slate-500 dark:text-slate-400"
                                >
                                    {{ topic.articles_count || 0 }}
                                    {{ t('academyLessons') }}
                                </span>
                            </div>

                            <span
                                class="text-xs font-black text-emerald-600 dark:text-emerald-400"
                            >
                                {{ t('academyExplore') }}
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- =====================================================
                 BOTTOM CTA
            ========================================================== -->
            <div
                v-if="topics.length > 0"
                class="relative overflow-hidden mt-14 rounded-3xl border border-emerald-200/70 dark:border-emerald-500/20 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 dark:from-emerald-950/20 dark:via-[#101827] dark:to-cyan-950/10"
            >
                <div
                    class="absolute -top-24 -end-24 w-64 h-64 rounded-full border border-emerald-300/20 dark:border-emerald-500/10"
                ></div>

                <div
                    class="relative p-7 sm:p-9 flex flex-col md:flex-row md:items-center md:justify-between gap-6"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="shrink-0 w-12 h-12 rounded-xl bg-white dark:bg-[#0b1121] border border-emerald-200 dark:border-emerald-500/20 flex items-center justify-center shadow-sm"
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
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18 18.246 18 16.5 18s-3.332 0-4.5 1.253"
                                />
                            </svg>
                        </div>

                        <div>
                            <h2
                                class="text-lg sm:text-xl font-black text-slate-950 dark:text-white"
                            >
                                {{ t('readyToLearn') }}
                            </h2>

                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400"
                            >
                                {{ t('readyToLearnDescription') }}
                            </p>
                        </div>
                    </div>

                    <a
                        href="#topics"
                        class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all hover:-translate-y-0.5"
                    >
                        {{ t('browseTopics') }}

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
                                d="M12 5v14m0 0l-6-6m6 6l6-6"
                            />
                        </svg>
                    </a>
                </div>
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

const topics = computed(() => page.props.topics || []);

const canonicalUrl = computed(() => {
    const cleanPath = page.url.split('?')[0];

    return cleanPath === '/'
        ? 'https://aqlcrypto.com'
        : `https://aqlcrypto.com${cleanPath}`;
});

/*
|--------------------------------------------------------------------------
| Calculated Academy Statistics
|--------------------------------------------------------------------------
*/

const totalLessons = computed(() => {
    return topics.value.reduce(
        (total, topic) => total + Number(topic.articles_count || 0),
        0
    );
});

/*
|--------------------------------------------------------------------------
| Translations
|--------------------------------------------------------------------------
*/

const translations = {
    ar: {
        academyLabel: 'AQL Crypto Academy',

        academyTitle: 'أكاديمية العملات الرقمية',

        academyDescription:
            'تعلم أساسيات العملات الرقمية وتقنية البلوك تشين والتمويل اللامركزي من خلال دروس واضحة ومنظمة تساعدك على فهم السوق والتقنيات المرتبطة به.',

        startLearning: 'ابدأ التعلم',

        learnAtYourPace: 'تعلّم بالوتيرة التي تناسبك',

        academyBrowse: 'مسارات التعلم',

        academyTopics: 'استكشف المواضيع التعليمية',

        topicsDescription:
            'اختر موضوعًا وابدأ رحلة تعليمية منظمة لفهم المفاهيم والتقنيات الأساسية في عالم العملات الرقمية.',

        academyTopicCount: 'موضوع',

        academyLessons: 'درس',

        academyExplore: 'استكشف المسار',

        learningTrack: 'مسار تعليمي',

        totalLessons: 'إجمالي الدروس',

        alwaysGrowing: 'المحتوى يتطور باستمرار',

        academyStatus: 'أكاديمية AQL Crypto',

        knowledge: 'المعرفة',

        structuredLearning: 'تعلم منظم',

        learnExplore: 'تعلّم واستكشف',

        readyToLearn: 'جاهز لبدء رحلة التعلم؟',

        readyToLearnDescription:
            'اختر أحد المسارات التعليمية وابدأ من الأساسيات خطوة بخطوة.',

        browseTopics: 'استكشف المواضيع',

        academyComingSoon: 'المحتوى التعليمي قادم قريبًا',

        academyComingSoonDescription:
            'نعمل حاليًا على إعداد مجموعة من الدروس التعليمية المنظمة حول أهم مفاهيم العملات الرقمية وتقنياتها.',

        seoTitle: 'أكاديمية العملات الرقمية | AQL Crypto',

        seoDescription:
            'أكاديمية AQL Crypto التعليمية: تعلم Bitcoin وBlockchain وDeFi وStaking وWeb3 وغيرها من مفاهيم العملات الرقمية.',
    },

    en: {
        academyLabel: 'AQL Crypto Academy',

        academyTitle: 'Cryptocurrency Academy',

        academyDescription:
            'Learn the fundamentals of cryptocurrencies, blockchain, decentralized finance, and related technologies through clear and structured educational lessons.',

        startLearning: 'Start Learning',

        learnAtYourPace: 'Learn at your own pace',

        academyBrowse: 'Learning Tracks',

        academyTopics: 'Explore Learning Topics',

        topicsDescription:
            'Choose a topic and follow a structured learning path to understand the essential concepts and technologies behind the digital asset ecosystem.',

        academyTopicCount: 'topics',

        academyLessons: 'lessons',

        academyExplore: 'Explore Track',

        learningTrack: 'Learning Track',

        totalLessons: 'Total Lessons',

        alwaysGrowing: 'Content continuously expanding',

        academyStatus: 'AQL Crypto Academy',

        knowledge: 'Knowledge',

        structuredLearning: 'Structured Learning',

        learnExplore: 'Learn & Explore',

        readyToLearn: 'Ready to start learning?',

        readyToLearnDescription:
            'Choose a learning track and build your understanding step by step.',

        browseTopics: 'Browse Topics',

        academyComingSoon: 'Educational content is coming soon',

        academyComingSoonDescription:
            'We are currently preparing a structured collection of educational lessons covering the most important cryptocurrency concepts and technologies.',

        seoTitle: 'Cryptocurrency Academy | AQL Crypto',

        seoDescription:
            'AQL Crypto Academy: Learn about Bitcoin, Blockchain, DeFi, Staking, Web3, and other cryptocurrency concepts.',
    },
};

const t = (key) => {
    return translations[locale.value]?.[key] || key;
};

const seoTitle = computed(() => t('seoTitle'));

const seoDescription = computed(() => t('seoDescription'));

/*
|--------------------------------------------------------------------------
| Academy CollectionPage JSON-LD
|--------------------------------------------------------------------------
*/

const academyJsonLd = computed(() => {
    const itemList = topics.value.map((topic, index) => ({
        '@type': 'ListItem',

        position: index + 1,

        name: topic.name,

        url: `https://aqlcrypto.com/academy/${topic.slug}`,
    }));

    return {
        '@context': 'https://schema.org',

        '@type': 'CollectionPage',

        '@id': `${canonicalUrl.value}#academy`,

        url: canonicalUrl.value,

        name: seoTitle.value,

        description: seoDescription.value,

        inLanguage: locale.value === 'ar' ? 'ar' : 'en',

        isPartOf: {
            '@type': 'WebSite',

            '@id': 'https://aqlcrypto.com/#website',

            name: 'AQL Crypto',

            url: 'https://aqlcrypto.com',
        },

        publisher: {
            '@type': 'Organization',

            '@id': 'https://aqlcrypto.com/#organization',

            name: 'AQL Crypto',

            url: 'https://aqlcrypto.com',

            logo: {
                '@type': 'ImageObject',

                url: 'https://aqlcrypto.com/images/logos/logo-horizontal-dark.webp',
            },
        },

        mainEntity: {
            '@type': 'ItemList',

            numberOfItems: itemList.length,

            itemListElement: itemList,
        },
    };
});

/*
|--------------------------------------------------------------------------
| Breadcrumb JSON-LD
|--------------------------------------------------------------------------
*/

const breadcrumbJsonLd = computed(() => ({
    '@context': 'https://schema.org',

    '@type': 'BreadcrumbList',

    '@id': `${canonicalUrl.value}#breadcrumb`,

    itemListElement: [
        {
            '@type': 'ListItem',

            position: 1,

            name:
                locale.value === 'ar'
                    ? 'الرئيسية'
                    : 'Home',

            item: 'https://aqlcrypto.com/',
        },

        {
            '@type': 'ListItem',

            position: 2,

            name:
                locale.value === 'ar'
                    ? 'أكاديمية العملات الرقمية'
                    : 'Cryptocurrency Academy',

            item: canonicalUrl.value,
        },
    ],
}));
</script>