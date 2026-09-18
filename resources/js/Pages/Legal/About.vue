<template>
    <HomeLayout>
        <Head>
            <title>{{ isAr ? 'من نحن | AQL Crypto' : 'About Us | AQL Crypto' }}</title>
            <meta
                name="description"
                :content="
                    isAr
                        ? 'تعرف على منصة AQL Crypto، المركز الرائد لمعلومات وتحليلات سوق العملات الرقمية باستخدام الذكاء الاصطناعي.'
                        : 'Discover AQL Crypto, the leading hub for cryptocurrency market intelligence and AI-driven analysis.'
                "
            />
        </Head>

        <div class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="text-center mb-16">
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                        {{ content.title }}
                    </h1>

                    <div class="w-20 h-1 bg-emerald-500 mx-auto rounded"></div>
                </div>

                <div
                    class="space-y-12 text-slate-700 dark:text-slate-300 leading-relaxed text-lg"
                    :dir="isAr ? 'rtl' : 'ltr'"
                >

                    <!-- Who We Are -->
                    <section class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                            {{ content.who_we_are_title }}
                        </h2>

                        <p class="mb-4">
                            {{ content.who_we_are_p1 }}
                        </p>

                        <p>
                            {{ content.who_we_are_p2 }}
                        </p>
                    </section>

                    <!-- What We Offer -->
                    <section class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                            {{ content.what_we_offer_title }}
                        </h2>

                        <ul class="space-y-4">
                            <li
                                v-for="(feature, index) in content.features"
                                :key="index"
                                class="flex items-start"
                            >
                                <span class="text-emerald-500 mr-3 ms-3 mt-1">✦</span>

                                <div>
                                    <strong class="text-slate-900 dark:text-white block">
                                        {{ feature.title }}
                                    </strong>

                                    <span class="text-sm">
                                        {{ feature.desc }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <!-- Editorial Team -->
                    <section class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                                <svg
                                    class="w-5 h-5 text-emerald-600 dark:text-emerald-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 12a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 0114 0"
                                    />
                                </svg>
                            </div>

                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                                {{ content.editorial_team_title }}
                            </h2>
                        </div>

                        <p class="mb-4">
                            {{ content.editorial_team_p1 }}
                        </p>

                        <p>
                            {{ content.editorial_team_p2 }}
                        </p>
                    </section>

                    <!-- Mission -->
                    <section class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                            {{ content.mission_title }}
                        </h2>

                        <p>
                            {{ content.mission_p1 }}
                        </p>
                    </section>

                    <!-- Important Notice -->
                    <section class="bg-amber-50 dark:bg-amber-900/20 p-6 rounded-xl border border-amber-200 dark:border-amber-700/50">
                        <div class="flex items-center gap-3 mb-2">
                            <svg
                                class="w-6 h-6 text-amber-600 dark:text-amber-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 01-18 0z"
                                />
                            </svg>

                            <h3 class="text-lg font-bold text-amber-800 dark:text-amber-400">
                                {{ content.disclaimer_title }}
                            </h3>
                        </div>

                        <p class="text-amber-700 dark:text-amber-300 text-base">
                            {{ content.disclaimer_desc }}
                        </p>
                    </section>

                </div>
            </div>
        </div>
    </HomeLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import HomeLayout from '@/layouts/HomeLayout.vue';

const page = usePage();

const locale = computed(() => page.props.locale || 'ar');

const isAr = computed(() => locale.value === 'ar');

const translations = {
    ar: {
        title: "من نحن",

        who_we_are_title: "من هي AQL Crypto؟",

        who_we_are_p1:
            "منصة AQL Crypto هي البوابة الرائدة لمعلومات وتحليلات سوق العملات الرقمية الموجهة للمستثمر والمهتم العربي والعالمي. نحن نؤمن بأن البيانات الدقيقة والتحليل العميق هما المفتاح الأساسي لفهم عالم الكريبتو سريع التغير.",

        who_we_are_p2:
            "لقد قمنا بدمج أحدث تقنيات الذكاء الاصطناعي مع البيانات اللحظية للسوق لنقدم لك تجربة استكشاف وتحليل متقدمة، تساعدك على فهم اتجاهات السوق، ومراقبة الأخبار، وقراءة المؤشرات المالية بكل سهولة ووضوح.",

        what_we_offer_title: "ماذا نقدم؟",

        features: [
            {
                title: "أخبار العملات الرقمية",
                desc: "تغطية لحظية لأهم وأحدث الأخبار المؤثرة في سوق الكريبتو من مصادر عالمية موثوقة."
            },
            {
                title: "أسعار السوق المباشرة",
                desc: "بيانات حية وأسعار دقيقة لأهم العملات الرقمية مع مخططات بيانية تفاعلية."
            },
            {
                title: "مرصد ذكاء السوق (AI Market)",
                desc: "تحليل ذكي لمعنويات السوق وتأثير الأخبار على الأسعار باستخدام خوارزميات الذكاء الاصطناعي."
            },
            {
                title: "تقارير العملات الذكية (AI Coin Reports)",
                desc: "تقارير تحليلية شاملة ومفصلة لكل عملة رقمية يتم إنشاؤها عبر محركات الذكاء الاصطناعي لمساعدتك في فهم الأساسيات والزخم."
            }
        ],

        editorial_team_title: "فريق التحرير",

        editorial_team_p1:
            "يتم نشر الأخبار والتحليلات على AQL Crypto تحت الاسم التحريري Aql Crypto Editorial Team، وهو الاسم المستخدم للمحتوى التحريري المنشور على المنصة.",

        editorial_team_p2:
            "يعتمد إعداد المحتوى على مجموعة من المصادر الإخبارية العامة والموثوقة، وبيانات السوق، والمعلومات المتاحة من المصادر المتخصصة، إلى جانب أدوات الذكاء الاصطناعي التي تُستخدم للمساعدة في جمع المعلومات وتنظيمها وتحليلها وصياغة المحتوى. ويهدف هذا النهج إلى تقديم محتوى واضح ومفيد يساعد القارئ على فهم تطورات سوق العملات الرقمية، مع توضيح المصادر والسياق وحدود التحليل عند الحاجة.",

        mission_title: "مهمتنا",

        mission_p1:
            "مهمتنا هي تبسيط عالم العملات الرقمية المعقد ووضعه بين يديك في قالب سهل، دقيق، وموثوق. نسعى لتمكين المستخدم من اتخاذ قرارات مدروسة بناءً على بيانات حقيقية وتحليلات منطقية خالية من العاطفة.",

        disclaimer_title: "توضيح هام",

        disclaimer_desc:
            "منصة AQL Crypto هي منصة معلوماتية وإخبارية فقط. نحن لا نقدم خدمات التداول، ولا نطلب إيداع الأموال، ولا نحتفظ بالأصول الرقمية للمستخدمين، ولا نقدم أي نصائح استثمارية أو مالية مباشرة."
    },

    en: {
        title: "About Us",

        who_we_are_title: "Who is AQL Crypto?",

        who_we_are_p1:
            "AQL Crypto is a premier cryptocurrency market intelligence and information portal designed for global investors and enthusiasts. We believe that accurate data and meaningful analysis are essential to understanding the fast-paced crypto world.",

        who_we_are_p2:
            "We have integrated advanced Artificial Intelligence technologies with real-time market data to offer an enhanced exploration and analysis experience, helping users understand market trends, monitor news, and follow financial indicators with ease and clarity.",

        what_we_offer_title: "What We Offer",

        features: [
            {
                title: "Cryptocurrency News",
                desc: "Real-time coverage of the most impactful crypto news from reliable global sources."
            },
            {
                title: "Live Market Prices",
                desc: "Live data and precise prices for top cryptocurrencies with interactive charts."
            },
            {
                title: "AI Market Intelligence",
                desc: "Smart analysis of market sentiment and news impact using advanced AI algorithms."
            },
            {
                title: "AI Coin Reports",
                desc: "Comprehensive analytical reports for individual cryptocurrencies generated with AI tools to help users understand fundamentals and momentum."
            }
        ],

        editorial_team_title: "Editorial Team",

        editorial_team_p1:
            "News and analysis published on AQL Crypto are presented under the editorial name Aql Crypto Editorial Team, which is the editorial identity used for content published on the platform.",

        editorial_team_p2:
            "Content preparation relies on a combination of public and reliable news sources, market data, and information from specialized sources, together with Artificial Intelligence tools used to assist with information gathering, organization, analysis, and content drafting. This approach is intended to provide clear and useful content that helps readers understand developments in the cryptocurrency market, while identifying sources, context, and analytical limitations where appropriate.",

        mission_title: "Our Mission",

        mission_p1:
            "Our mission is to simplify the complex world of cryptocurrency and deliver it to you in an accessible, accurate, and reliable format. We strive to empower users to make informed decisions based on real data and logic-driven analysis.",

        disclaimer_title: "Important Notice",

        disclaimer_desc:
            "AQL Crypto is strictly an informational and news platform. We do NOT offer trading services, we do not request fund deposits, we do not hold user assets, and we do not provide direct financial or investment advice."
    }
};

const content = computed(() => translations[locale.value]);
</script>