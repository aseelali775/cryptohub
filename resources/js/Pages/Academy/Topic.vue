<template>
    <Head>
        <title>{{ seoTitle }}</title>

        <!-- Canonical -->
        <link
            rel="canonical"
            :href="canonicalUrl"
        />

        <!-- Description -->
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

        <!-- =========================================================
             Open Graph
        ========================================================== -->

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
            :content="ogImage"
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

        <!-- =========================================================
             Twitter
        ========================================================== -->

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
            :content="ogImage"
        />

        <!-- =========================================================
             CollectionPage + ItemList
        ========================================================== -->

        <component
            is="script"
            type="application/ld+json"
            head-key="academy-topic-jsonld"
        >
            {{ JSON.stringify(topicJsonLd) }}
        </component>

        <!-- =========================================================
             Breadcrumb
        ========================================================== -->

        <component
            is="script"
            type="application/ld+json"
            head-key="academy-topic-breadcrumb-jsonld"
        >
            {{ JSON.stringify(breadcrumbJsonLd) }}
        </component>
    </Head>

    <div
        class="min-h-screen bg-slate-50 text-slate-800 transition-colors duration-300 dark:bg-[#080d18] dark:text-slate-100"
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

            <!-- Decorative glow -->
            <div
                class="absolute -end-32 -top-32 h-96 w-96 rounded-full bg-emerald-400/10 blur-3xl"
            ></div>

            <div
                class="absolute -bottom-40 -start-32 h-96 w-96 rounded-full bg-cyan-400/5 blur-3xl"
            ></div>

            <!-- Grid -->
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
                class="relative mx-auto max-w-[1400px] px-4 py-10 sm:px-6 sm:py-14 lg:px-8 lg:py-18"
            >
                <!-- ================================================= -->
                <!-- Breadcrumb -->
                <!-- ================================================= -->

                <nav
                    class="mb-10 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400"
                    aria-label="Breadcrumb"
                >
                    <Link
                        href="/academy"
                        class="font-medium transition-colors hover:text-emerald-600 dark:hover:text-emerald-400"
                    >
                        {{ t('academy') }}
                    </Link>

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 shrink-0"
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
                        class="font-semibold text-slate-800 dark:text-slate-200"
                    >
                        {{ topic.name }}
                    </span>
                </nav>

                <!-- ================================================= -->
                <!-- Hero Content -->
                <!-- ================================================= -->

                <div
                    class="grid items-center gap-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-16"
                >
                    <div class="max-w-4xl">
                        <!-- Badge -->
                        <div
                            class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-200/80 bg-emerald-50 px-3.5 py-2 text-xs font-bold tracking-wide text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400"
                        >
                            <span
                                class="relative flex h-2 w-2"
                            >
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"
                                ></span>

                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                                ></span>
                            </span>

                            AQL Crypto Academy
                        </div>

                        <!-- Title -->
                        <h1
                            class="text-4xl font-black leading-[1.08] tracking-tight text-slate-950 dark:text-white sm:text-5xl lg:text-6xl"
                        >
                            {{ topic.name }}
                        </h1>

                        <!-- Description -->
                        <p
                            v-if="topic.description"
                            class="mt-6 max-w-3xl text-base leading-8 text-slate-600 dark:text-slate-400 sm:text-lg"
                        >
                            {{ topic.description }}
                        </p>

                        <!-- Stats / Links -->
                        <div
                            class="mt-8 flex flex-wrap items-center gap-3"
                        >
                            <!-- Lesson count -->
                            <div
                                class="inline-flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white/80 px-4 py-2.5 shadow-sm dark:border-slate-700/80 dark:bg-white/[0.04]"
                            >
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
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

                                <div
                                    :class="
                                        locale === 'ar'
                                            ? 'text-right'
                                            : 'text-left'
                                    "
                                >
                                    <div
                                        class="text-sm font-black text-slate-900 dark:text-white"
                                    >
                                        {{ articles.length }}
                                    </div>

                                    <div
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {{ t('lessonCount') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Academy -->
                            <Link
                                href="/academy"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm font-bold text-slate-700 transition-all hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:bg-white/[0.03] dark:text-slate-200 dark:hover:border-emerald-500/40 dark:hover:text-emerald-400"
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
                                        stroke-width="1.7"
                                        d="M4 5.5A2.5 2.5 0 016.5 3H20v16H6.5A2.5 2.5 0 014 16.5v-11z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.7"
                                        d="M4 16.5A2.5 2.5 0 016.5 14H20"
                                    />
                                </svg>

                                {{ t('allTopics') }}
                            </Link>

                            <!-- Market -->
                            <Link
                                v-if="marketLink"
                                :href="marketLink.url"
                                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50/80 px-4 py-2.5 text-sm font-bold text-emerald-700 transition-all hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/15"
                            >
                                {{ t('viewMarket') }}

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
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
                            </Link>
                        </div>
                    </div>

                    <!-- ================================================= -->
                    <!-- Topic Visual -->
                    <!-- ================================================= -->

                    <div class="hidden justify-end lg:flex">
                        <div class="relative h-48 w-48">
                            <div
                                class="absolute inset-0 rounded-[2rem] bg-gradient-to-br from-emerald-400/20 to-cyan-400/10 blur-xl"
                            ></div>

                            <div
                                class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-[2rem] border border-emerald-200/70 bg-white/80 shadow-2xl dark:border-emerald-500/20 dark:bg-[#111a2b]/90"
                            >
                                <div
                                    class="absolute -end-12 -top-12 h-32 w-32 rounded-full bg-emerald-400/10"
                                ></div>

                                <div
                                    class="absolute -bottom-10 -start-10 h-28 w-28 rounded-full bg-cyan-400/10"
                                ></div>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="relative h-20 w-20 text-emerald-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.25"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18 18.246 18 16.5 18s-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========================================================
             LESSONS
        ========================================================== -->

        <main
            class="mx-auto max-w-[1200px] px-4 py-12 sm:px-6 sm:py-16 lg:px-8"
        >
            <!-- Section heading -->
            <div
                class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="mb-2 text-sm font-bold text-emerald-600 dark:text-emerald-400"
                    >
                        {{ t('learningPath') }}
                    </p>

                    <h2
                        class="text-2xl font-black text-slate-950 dark:text-white sm:text-3xl"
                    >
                        {{ t('lessons') }}
                    </h2>

                    <p
                        class="mt-2 text-sm text-slate-500 dark:text-slate-400"
                    >
                        {{ t('lessonsDescription') }}
                    </p>
                </div>

                <div
                    v-if="articles.length > 0"
                    class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400"
                >
                    <span
                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 font-black text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                    >
                        {{ articles.length }}
                    </span>

                    {{ t('lessonCount') }}
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- Empty -->
            <!-- ===================================================== -->

            <div
                v-if="articles.length === 0"
                class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-[#101827] sm:p-16"
            >
                <div
                    class="absolute inset-0 bg-gradient-to-br from-emerald-50/70 via-transparent to-transparent dark:from-emerald-500/[0.04]"
                ></div>

                <div class="relative">
                    <div
                        class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-9 w-9 text-emerald-500"
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
                        class="text-xl font-black text-slate-950 dark:text-white sm:text-2xl"
                    >
                        {{ t('comingSoon') }}
                    </h3>

                    <p
                        class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-500 dark:text-slate-400"
                    >
                        {{ t('comingSoonDescription') }}
                    </p>

                    <Link
                        href="/academy"
                        class="mt-8 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition-all hover:-translate-y-0.5 hover:bg-emerald-700"
                    >
                        {{ t('backToAcademy') }}

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
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
                    </Link>
                </div>
            </div>

            <!-- ===================================================== -->
            <!-- Articles -->
            <!-- ===================================================== -->

            <div
                v-else
                class="relative"
            >
                <!-- Learning path -->
                <div
                    class="absolute bottom-8 start-[27px] top-8 hidden w-px bg-gradient-to-b from-emerald-300 via-slate-200 to-transparent dark:from-emerald-500/40 dark:via-slate-800 dark:to-transparent md:block"
                ></div>

                <div class="space-y-5">
                    <Link
                        v-for="(article, index) in articles"
                        :key="article.id"
                        :href="articleUrl(article)"
                        class="group relative block md:ps-16"
                    >
                        <!-- Lesson number -->
                        <div
                            class="absolute start-0 top-6 hidden h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 group-hover:border-emerald-400 group-hover:text-emerald-600 dark:border-slate-700 dark:bg-[#101827] dark:group-hover:border-emerald-500/50 dark:group-hover:text-emerald-400 md:flex"
                        >
                            <span
                                class="text-sm font-black text-slate-500 transition-colors dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
                            >
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                        </div>

                        <!-- Card -->
                        <article
                            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-300 group-hover:-translate-y-0.5 group-hover:border-emerald-300 group-hover:shadow-xl group-hover:shadow-slate-900/5 dark:border-slate-800 dark:bg-[#101827] dark:group-hover:border-emerald-500/30 dark:group-hover:shadow-black/20"
                        >
                            <div
                                class="grid md:grid-cols-[220px_1fr]"
                            >
                                <!-- Image -->
                                <div
                                    v-if="article.image"
                                    class="min-h-[190px] overflow-hidden bg-slate-100 dark:bg-slate-900 md:h-full"
                                >
                                    <img
                                        :src="article.image"
                                        :alt="article.title"
                                        loading="lazy"
                                        decoding="async"
                                        class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    />
                                </div>

                                <!-- Placeholder -->
                                <div
                                    v-else
                                    class="relative flex min-h-[190px] h-40 items-center justify-center overflow-hidden bg-gradient-to-br from-emerald-50 via-slate-50 to-white dark:from-emerald-950/25 dark:via-[#111a2b] dark:to-[#101827] md:h-full"
                                >
                                    <div
                                        class="absolute -end-12 -top-12 h-32 w-32 rounded-full bg-emerald-400/10"
                                    ></div>

                                    <div
                                        class="absolute -bottom-14 -start-10 h-36 w-36 rounded-full bg-cyan-400/5"
                                    ></div>

                                    <div
                                        class="relative flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-100 bg-white/80 shadow-sm dark:border-emerald-500/20 dark:bg-[#0b1121]"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-8 w-8 text-emerald-500"
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
                                <div
                                    class="flex min-w-0 flex-col justify-center p-5 sm:p-7"
                                >
                                    <!-- Meta -->
                                    <div
                                        class="mb-3 flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"
                                        >
                                            {{ t('lesson') }}
                                            {{ index + 1 }}
                                        </span>

                                        <span
                                            v-if="article.published_at"
                                            class="text-xs text-slate-400 dark:text-slate-500"
                                        >
                                            {{ formatDate(article.published_at) }}
                                        </span>
                                    </div>

                                    <!-- Article title -->
                                    <h3
                                        class="text-xl font-black leading-tight text-slate-950 transition-colors group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400 sm:text-2xl"
                                    >
                                        {{ article.title }}
                                    </h3>

                                    <!-- Excerpt -->
                                    <p
                                        v-if="article.excerpt"
                                        class="mt-3 line-clamp-3 text-sm leading-7 text-slate-500 dark:text-slate-400 sm:text-[15px]"
                                    >
                                        {{ article.excerpt }}
                                    </p>

                                    <!-- Read -->
                                    <div
                                        class="mt-6 flex items-center justify-between gap-4"
                                    >
                                        <span
                                            class="inline-flex items-center gap-2 text-sm font-bold text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ t('readLesson') }}

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 transition-transform duration-300"
                                                :class="
                                                    locale === 'ar'
                                                        ? 'group-hover:-translate-x-1'
                                                        : 'group-hover:translate-x-1'
                                                "
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    :d="
                                                        locale === 'ar'
                                                            ? 'M15 19l-7-7 7-7'
                                                            : 'M9 5l7 7-7 7'
                                                    "
                                                />
                                            </svg>
                                        </span>

                                        <span
                                            class="hidden text-xs font-semibold text-slate-400 dark:text-slate-500 sm:inline-flex"
                                        >
                                            AQL Crypto Academy
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </Link>
                </div>
            </div>

            <!-- =========================================================
                 MARKET CONNECTION
            ========================================================== -->

            <div
                v-if="marketLink"
                class="mt-12 overflow-hidden rounded-3xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 via-white to-cyan-50 dark:border-emerald-500/20 dark:from-emerald-950/20 dark:via-[#101827] dark:to-cyan-950/10"
            >
                <div
                    class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:justify-between sm:p-8"
                >
                    <div
                        class="flex items-start gap-4"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-500/20 dark:bg-[#0b1121]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6 text-emerald-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.7"
                                    d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7H15a3.5 3.5 0 010 7H6"
                                />
                            </svg>
                        </div>

                        <div>
                            <h2
                                class="text-lg font-black text-slate-950 dark:text-white sm:text-xl"
                            >
                                {{ marketLink.title }}
                            </h2>

                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400"
                            >
                                {{ marketLink.description }}
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="marketLink.url"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition-all hover:-translate-y-0.5 hover:bg-emerald-700"
                    >
                        {{ marketLink.button }}

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
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
                    </Link>
                </div>
            </div>

            <!-- =========================================================
                 Back to Academy
            ========================================================== -->

            <div class="mt-10 text-center">
                <Link
                    href="/academy"
                    class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 transition-colors hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
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

                    {{ t('backToAcademy') }}
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

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

const page = usePage();

const locale = computed(() => page.props.locale || 'ar');

const topic = computed(() => page.props.topic || {});

const articles = computed(() => topic.value.articles || []);

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
| Open Graph Image
|--------------------------------------------------------------------------
*/

const ogImage = computed(() => {
    return (
        topic.value.image ||
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
        learningPath: 'مسار التعلم',
        lessons: 'دروس الموضوع',
        lessonsDescription:
            'تعلّم الموضوع خطوة بخطوة من خلال دروس تعليمية منظمة.',
        lesson: 'الدرس',
        lessonCount: 'درس',
        comingSoon: 'الدروس التعليمية قادمة قريبًا',
        comingSoonDescription:
            'نعمل حاليًا على إعداد الدروس التعليمية لهذا الموضوع. ستظهر الدروس هنا بمجرد نشرها.',
        backToAcademy: 'العودة إلى الأكاديمية',
        allTopics: 'جميع المواضيع',
        readLesson: 'اقرأ الدرس',
        viewMarket: 'بيانات السوق',
        marketTitle: 'استكشف بيانات السوق',
        marketDescription:
            'تابع السعر والبيانات والمؤشرات المرتبطة بهذا الأصل من منصة AQL Crypto.',
        marketButton: 'عرض صفحة السوق',
    },

    en: {
        academy: 'Academy',
        learningPath: 'Learning Path',
        lessons: 'Topic Lessons',
        lessonsDescription:
            'Learn the topic step by step through structured educational lessons.',
        lesson: 'Lesson',
        lessonCount: 'lessons',
        comingSoon: 'Lessons are coming soon',
        comingSoonDescription:
            'We are currently preparing educational lessons for this topic. They will appear here once published.',
        backToAcademy: 'Back to Academy',
        allTopics: 'All Topics',
        readLesson: 'Read Lesson',
        viewMarket: 'Market Data',
        marketTitle: 'Explore Market Data',
        marketDescription:
            'Track price data, market information, and related indicators on AQL Crypto.',
        marketButton: 'View Market Page',
    },
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
    const name = topic.value.name || 'Academy';

    return locale.value === 'ar'
        ? `${name} | أكاديمية AQL Crypto`
        : `${name} | AQL Crypto Academy`;
});

/*
|--------------------------------------------------------------------------
| Meta Description
|--------------------------------------------------------------------------
*/

const seoDescription = computed(() => {
    if (topic.value.description) {
        return topic.value.description;
    }

    return locale.value === 'ar'
        ? `تعلم أساسيات ${topic.value.name || 'العملات الرقمية'} من خلال دروس تعليمية منظمة في أكاديمية AQL Crypto.`
        : `Learn the fundamentals of ${topic.value.name || 'cryptocurrency'} through structured lessons at AQL Crypto Academy.`;
});

/*
|--------------------------------------------------------------------------
| Article URL
|--------------------------------------------------------------------------
*/

const articleUrl = (article) => {
    return `/academy/${topic.value.slug}/${article.slug}`;
};

/*
|--------------------------------------------------------------------------
| Related Market Pages
|--------------------------------------------------------------------------
|
| Verified symbols:
|
| BTC → Bitcoin
| ETH → Ethereum
| SOL → Solana
| XRP → XRP
| ADA → Cardano
|
*/

const marketLinks = {
    bitcoin: {
        symbol: 'BTC',

        arTitle: 'تعلّم بيتكوين ثم تابع بياناته مباشرة',
        enTitle: 'Learn Bitcoin, then explore its market data',

        arDescription:
            'بعد فهم أساسيات بيتكوين، انتقل إلى صفحة السوق لمتابعة السعر والبيانات المرتبطة به.',
        enDescription:
            'After learning the fundamentals of Bitcoin, explore its market page for price and related market data.',
    },

    ethereum: {
        symbol: 'ETH',

        arTitle: 'تعلّم إيثريوم ثم تابع بياناته مباشرة',
        enTitle: 'Learn Ethereum, then explore its market data',

        arDescription:
            'اربط المعرفة التعليمية ببيانات السوق من خلال صفحة إيثريوم على AQL Crypto.',
        enDescription:
            'Connect your learning with market data through Ethereum’s AQL Crypto market page.',
    },

    solana: {
        symbol: 'SOL',

        arTitle: 'تعلّم سولانا ثم تابع بياناتها مباشرة',
        enTitle: 'Learn Solana, then explore its market data',

        arDescription:
            'بعد دراسة سولانا، يمكنك متابعة السعر وبيانات السوق من صفحة الأصل.',
        enDescription:
            'After studying Solana, explore its price and market data from the asset page.',
    },

    xrp: {
        symbol: 'XRP',

        arTitle: 'تعلّم XRP ثم تابع بياناته مباشرة',
        enTitle: 'Learn XRP, then explore its market data',

        arDescription:
            'تابع بيانات XRP السوقية بعد استكمال الدروس التعليمية المرتبطة به.',
        enDescription:
            'Explore XRP market data after completing the related educational lessons.',
    },

    cardano: {
        symbol: 'ADA',

        arTitle: 'تعلّم Cardano ثم تابع بياناته مباشرة',
        enTitle: 'Learn Cardano, then explore its market data',

        arDescription:
            'اربط الدروس التعليمية بصفحة Cardano لمتابعة بيانات السوق.',
        enDescription:
            'Connect the educational content with Cardano market data.',
    },
};

const marketLink = computed(() => {
    const slug = String(topic.value.slug || '').toLowerCase();

    if (!slug || !marketLinks[slug]) {
        return null;
    }

    const item = marketLinks[slug];

    return {
        url: `/crypto/${item.symbol}`,

        title:
            locale.value === 'ar'
                ? item.arTitle
                : item.enTitle,

        description:
            locale.value === 'ar'
                ? item.arDescription
                : item.enDescription,

        button: t('marketButton'),
    };
});

/*
|--------------------------------------------------------------------------
| CollectionPage + ItemList JSON-LD
|--------------------------------------------------------------------------
*/

const topicJsonLd = computed(() => {
    const itemList = articles.value.map((article, index) => {
        const item = {
            '@type': 'ListItem',
            position: index + 1,
            name: article.title,
            url: `https://aqlcrypto.com/academy/${topic.value.slug}/${article.slug}`,
        };

        if (article.image) {
            item.image = article.image;
        }

        return item;
    });

    return {
        '@context': 'https://schema.org',

        '@type': 'CollectionPage',

        '@id': `${canonicalUrl.value}#topic`,

        url: canonicalUrl.value,

        name: seoTitle.value,

        description: seoDescription.value,

        inLanguage:
            locale.value === 'ar'
                ? 'ar'
                : 'en',

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

            itemListOrder: 'https://schema.org/ItemListOrderAscending',

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

            item: 'https://aqlcrypto.com/academy',
        },

        {
            '@type': 'ListItem',
            position: 3,

            name:
                topic.value.name ||
                (
                    locale.value === 'ar'
                        ? 'موضوع الأكاديمية'
                        : 'Academy Topic'
                ),

            item: canonicalUrl.value,
        },
    ],
}));

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
            locale.value === 'ar'
                ? 'ar'
                : 'en',
            {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            }
        ).format(new Date(date));
    } catch {
        return '';
    }
};
</script>