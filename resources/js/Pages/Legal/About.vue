<template>
    <HomeLayout>
        <Head>
            <title>
                {{ isAr ? 'من نحن | AQL Crypto' : 'About Us | AQL Crypto' }}
            </title>

            <meta
                name="description"
                :content="
                    isAr
                        ? 'تعرف على AQL Crypto ومنهجية عملها في تقديم أخبار العملات الرقمية وبيانات السوق والتحليلات والأكاديمية التعليمية باستخدام البيانات وأدوات الذكاء الاصطناعي.'
                        : 'Learn about AQL Crypto, our editorial approach, market data, cryptocurrency news, analysis, and educational Academy.'
                "
            />
        </Head>

        <div
            class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300"
        >
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="text-center mb-16">
                    <h1
                        class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight"
                    >
                        {{ content.title }}
                    </h1>

                    <p
                        class="max-w-2xl mx-auto text-slate-600 dark:text-slate-400 text-lg leading-relaxed"
                    >
                        {{ content.intro }}
                    </p>

                    <div class="w-20 h-1 bg-emerald-500 mx-auto rounded mt-6"></div>
                </div>

                <div
                    class="space-y-10 text-slate-700 dark:text-slate-300 leading-relaxed text-lg"
                    :dir="isAr ? 'rtl' : 'ltr'"
                >

                    <!-- Who We Are -->
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white mb-5"
                        >
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
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white mb-5"
                        >
                            {{ content.what_we_offer_title }}
                        </h2>

                        <ul class="space-y-5">
                            <li
                                v-for="(feature, index) in content.features"
                                :key="index"
                                class="flex items-start"
                            >
                                <span
                                    class="text-emerald-500 mr-3 ms-3 mt-1 flex-shrink-0"
                                >
                                    ✦
                                </span>

                                <div>
                                    <strong
                                        class="text-slate-900 dark:text-white block mb-1"
                                    >
                                        {{ feature.title }}
                                    </strong>

                                    <span class="text-base">
                                        {{ feature.desc }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </section>

                    <!-- Academy -->
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20"
                            >
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
                                        d="M12 14l9-5-9-5-9 5 9 5zm0 0v7m-7-4v-5.5L12 15l7-3.5V17"
                                    />
                                </svg>
                            </div>

                            <h2
                                class="text-2xl font-bold text-slate-900 dark:text-white"
                            >
                                {{ content.academy_title }}
                            </h2>
                        </div>

                        <p class="mb-4">
                            {{ content.academy_p1 }}
                        </p>

                        <p class="mb-6">
                            {{ content.academy_p2 }}
                        </p>

                        <a
                            href="/academy"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition"
                        >
                            {{ content.academy_link }}

                            <span aria-hidden="true">
                                {{ isAr ? '←' : '→' }}
                            </span>
                        </a>
                    </section>

                    <!-- Editorial Team -->
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20"
                            >
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

                            <h2
                                class="text-2xl font-bold text-slate-900 dark:text-white"
                            >
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

                    <!-- Methodology -->
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white mb-5"
                        >
                            {{ content.methodology_title }}
                        </h2>

                        <p class="mb-5">
                            {{ content.methodology_intro }}
                        </p>

                        <ul class="space-y-4">
                            <li
                                v-for="(item, index) in content.methodology_items"
                                :key="index"
                                class="flex items-start"
                            >
                                <span
                                    class="text-emerald-500 mr-3 ms-3 mt-1"
                                >
                                    ✓
                                </span>

                                <div>
                                    <strong
                                        class="text-slate-900 dark:text-white block"
                                    >
                                        {{ item.title }}
                                    </strong>

                                    <span class="text-base">
                                        {{ item.desc }}
                                    </span>
                                </div>
                            </li>
                        </ul>

                        <div class="mt-6">
                            <a
                                href="/editorial-policy"
                                class="text-emerald-600 dark:text-emerald-400 font-semibold hover:underline"
                            >
                                {{ content.editorial_policy_link }}
                            </a>
                        </div>
                    </section>

                    <!-- Data Sources -->
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white mb-5"
                        >
                            {{ content.data_sources_title }}
                        </h2>

                        <p class="mb-5">
                            {{ content.data_sources_intro }}
                        </p>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div
                                v-for="(source, index) in content.data_sources"
                                :key="index"
                                class="rounded-xl border border-slate-200 dark:border-slate-700 p-5"
                            >
                                <h3
                                    class="font-bold text-slate-900 dark:text-white mb-2"
                                >
                                    {{ source.title }}
                                </h3>

                                <p class="text-sm">
                                    {{ source.desc }}
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Mission -->
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white mb-5"
                        >
                            {{ content.mission_title }}
                        </h2>

                        <p>
                            {{ content.mission_p1 }}
                        </p>
                    </section>

                    <!-- Contact / Transparency -->
                    <section
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white mb-5"
                        >
                            {{ content.transparency_title }}
                        </h2>

                        <p class="mb-5">
                            {{ content.transparency_p1 }}
                        </p>

                        <div class="flex flex-wrap gap-3">
                            <a
                                href="/contact"
                                class="inline-flex items-center px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold transition"
                            >
                                {{ content.contact_link }}
                            </a>

                            <a
                                href="/privacy-policy"
                                class="inline-flex items-center px-5 py-3 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                            >
                                {{ content.privacy_link }}
                            </a>
                        </div>
                    </section>

                    <!-- Important Notice -->
                    <section
                        class="bg-amber-50 dark:bg-amber-900/20 p-6 rounded-xl border border-amber-200 dark:border-amber-700/50"
                    >
                        <div class="flex items-center gap-3 mb-3">
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
                                    d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"
                                />
                            </svg>

                            <h3
                                class="text-lg font-bold text-amber-800 dark:text-amber-400"
                            >
                                {{ content.disclaimer_title }}
                            </h3>
                        </div>

                        <p
                            class="text-amber-700 dark:text-amber-300 text-base"
                        >
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
        title: 'من نحن',

        intro:
            'AQL Crypto منصة معلوماتية متخصصة في أخبار سوق العملات الرقمية وبيانات السوق والتحليلات والمحتوى التعليمي، مع التركيز على تقديم المعلومات بصورة واضحة ومنظمة.',

        who_we_are_title: 'من هي AQL Crypto؟',

        who_we_are_p1:
            'AQL Crypto هي منصة مستقلة للمعلومات والتحليلات المتعلقة بسوق العملات الرقمية. تهدف المنصة إلى مساعدة القراء والمهتمين على متابعة تطورات السوق وفهم الأخبار والبيانات والمؤشرات المرتبطة بالأصول الرقمية.',

        who_we_are_p2:
            'تجمع المنصة بين الأخبار، بيانات السوق، التحليلات، صفحات العملات، وأكاديمية تعليمية منظمة لتقديم صورة أكثر وضوحًا عن سوق سريع التغير. وتستخدم تقنيات الذكاء الاصطناعي كأدوات مساعدة في معالجة المعلومات وتحليلها وصياغة بعض المحتوى، مع توضيح المصادر والسياق وحدود التحليل عندما يكون ذلك مناسبًا.',

        what_we_offer_title: 'ماذا نقدم؟',

        features: [
            {
                title: 'أخبار العملات الرقمية',
                desc:
                    'تغطية منظمة لأهم التطورات والأحداث المؤثرة في سوق الأصول الرقمية اعتمادًا على مصادر إخبارية عامة ومتخصصة.'
            },
            {
                title: 'بيانات السوق',
                desc:
                    'أسعار وبيانات سوقية للعملات الرقمية مع مؤشرات ومخططات تساعد على متابعة حركة السوق.'
            },
            {
                title: 'تحليلات السوق',
                desc:
                    'تحليلات تعتمد على البيانات والأخبار المتاحة بهدف تقديم سياق أوضح للتطورات والاتجاهات المختلفة في السوق.'
            },
            {
                title: 'صفحات العملات',
                desc:
                    'صفحات مخصصة لمجموعة من العملات الرئيسية تجمع بيانات السوق والأخبار المرتبطة وبعض التحليلات في مكان واحد.'
            },
            {
                title: 'أدوات ومؤشرات السوق',
                desc:
                    'مؤشرات وأدوات تساعد المستخدم على استكشاف بيانات السوق وفهم بعض المفاهيم والقياسات المالية المرتبطة بالأصول الرقمية.'
            }
        ],

        academy_title: 'AQL Crypto Academy',

        academy_p1:
            'تضم AQL Crypto قسمًا تعليميًا مخصصًا لتبسيط مفاهيم العملات الرقمية وتقنية البلوك تشين. تبدأ الأكاديمية من المفاهيم الأساسية ثم تتدرج إلى موضوعات مثل Bitcoin وBlockchain وDeFi وStaking وWeb3 وNFTs والمحافظ وأساسيات التداول والأمن.',

        academy_p2:
            'تم تصميم محتوى الأكاديمية ليكون مرجعًا تعليميًا منظمًا للقراء الذين يرغبون في فهم المجال قبل الانتقال إلى الموضوعات الأكثر تقدمًا، دون تقديم توصيات استثمارية شخصية.',

        academy_link: 'زيارة أكاديمية AQL Crypto',

        editorial_team_title: 'فريق التحرير',

        editorial_team_p1:
            'يتم نشر الأخبار والتحليلات على AQL Crypto تحت الاسم التحريري Aql Crypto Editorial Team، وهو الاسم المستخدم للمحتوى التحريري المنشور على المنصة.',

        editorial_team_p2:
            'يعتمد إعداد المحتوى على مجموعة من المصادر الإخبارية العامة والمتخصصة، وبيانات السوق، والمعلومات المتاحة من المصادر ذات الصلة. وتُستخدم أدوات الذكاء الاصطناعي للمساعدة في جمع المعلومات وتنظيمها وتحليلها وصياغة بعض المواد، مع الحفاظ على الإشارة إلى المصادر والسياق والقيود التحليلية عند الحاجة.',

        methodology_title: 'منهجية العمل',

        methodology_intro:
            'نهدف إلى تقديم محتوى واضح وقابل للفهم مع توضيح كيفية الحصول على البيانات والمعلومات المستخدمة في المنصة.',

        methodology_items: [
            {
                title: 'جمع الأخبار',
                desc:
                    'يتم جمع الأخبار من مصادر عامة ومتخصصة في سوق العملات الرقمية، ثم معالجتها وتنظيمها داخل نظام الأخبار في المنصة.'
            },
            {
                title: 'معالجة المحتوى',
                desc:
                    'تُستخدم أدوات وتقنيات آلية للمساعدة في تنظيم المعلومات وإنشاء الملخصات والتحليلات والسياق المرتبط بالأخبار.'
            },
            {
                title: 'بيانات السوق',
                desc:
                    'تعتمد بيانات الأسعار ومؤشرات السوق على مزودي بيانات خارجيين موضحين في صفحات البيانات والمنهجية ذات الصلة.'
            },
            {
                title: 'الذكاء الاصطناعي',
                desc:
                    'يُستخدم الذكاء الاصطناعي كأداة مساعدة في تحليل المعلومات وصياغة بعض المحتوى، ولا يُعامل كمصدر مستقل للحقائق.'
            },
            {
                title: 'المراجعة والشفافية',
                desc:
                    'نسعى إلى توضيح المصادر والسياق وحدود التحليل، وتصحيح الأخطاء أو تحديث المعلومات عندما تتوفر بيانات أحدث.'
            }
        ],

        editorial_policy_link: 'الاطلاع على السياسة التحريرية',

        data_sources_title: 'مصادر البيانات',

        data_sources_intro:
            'تعتمد بعض خدمات AQL Crypto على بيانات من مزودين خارجيين متخصصين. تختلف البيانات حسب القسم ويتم توضيح المصدر عندما يكون ذلك ذا صلة.',

        data_sources: [
            {
                title: 'بيانات سوق العملات الرقمية',
                desc:
                    'تُستخدم بيانات CoinGecko لأسعار ومعلومات سوقية مختارة للعملات الرقمية.'
            },
            {
                title: 'مؤشر الخوف والطمع',
                desc:
                    'يتم استخدام Alternative.me لبيانات مؤشر Crypto Fear & Greed.'
            },
            {
                title: 'الأخبار',
                desc:
                    'تعتمد الأخبار على مصادر إخبارية عامة ومتخصصة، مع الاحتفاظ بالإشارة إلى المصدر الأصلي في المواد المنشورة.'
            },
            {
                title: 'التحليلات',
                desc:
                    'تعتمد التحليلات على البيانات والأخبار والسياق المتاح وقت إعدادها، وقد تتغير مع ظهور معلومات جديدة.'
            }
        ],

        mission_title: 'مهمتنا',

        mission_p1:
            'مهمتنا هي جعل المعلومات المتعلقة بالعملات الرقمية أكثر وضوحًا وتنظيمًا وسهولة في الوصول إليها. نعمل على بناء منصة تجمع بين الأخبار وبيانات السوق والتعليم والتحليل لمساعدة المستخدم على فهم المجال واتخاذ قراراته بنفسه بناءً على المعلومات المتاحة.',

        transparency_title: 'الشفافية والتواصل',

        transparency_p1:
            'نؤمن بأهمية الشفافية في المحتوى المالي والمعلوماتي. يمكن للقراء الاطلاع على سياسة التحرير وسياسة الخصوصية والتفاصيل القانونية، كما يمكن التواصل مع فريق المنصة عبر صفحة الاتصال.',

        contact_link: 'تواصل معنا',

        privacy_link: 'سياسة الخصوصية',

        disclaimer_title: 'توضيح هام',

        disclaimer_desc:
            'AQL Crypto منصة معلوماتية وإخبارية وتعليمية فقط. لا نقدم خدمات التداول أو إدارة الأصول، ولا نطلب من المستخدمين إيداع الأموال، ولا نحتفظ بالأصول الرقمية نيابة عن المستخدمين، ولا نقدم نصائح مالية أو استثمارية شخصية. المعلومات والتحليلات المنشورة لأغراض إعلامية وتعليمية وقد تتغير مع تغير البيانات والظروف السوقية.'
    },

    en: {
        title: 'About Us',

        intro:
            'AQL Crypto is an independent information platform focused on cryptocurrency news, market data, analysis, and educational content.',

        who_we_are_title: 'Who is AQL Crypto?',

        who_we_are_p1:
            'AQL Crypto is an independent platform covering cryptocurrency markets, digital assets, and blockchain-related developments. Our goal is to help readers and enthusiasts follow market developments and better understand the news, data, and indicators surrounding digital assets.',

        who_we_are_p2:
            'The platform combines news, market data, analysis, cryptocurrency pages, and a structured educational Academy. Artificial Intelligence tools are used to assist with information processing, analysis, and drafting of selected content, while sources, context, and analytical limitations are identified where appropriate.',

        what_we_offer_title: 'What We Offer',

        features: [
            {
                title: 'Cryptocurrency News',
                desc:
                    'Organized coverage of important developments and events affecting digital asset markets, based on public and specialized sources.'
            },
            {
                title: 'Market Data',
                desc:
                    'Cryptocurrency prices and selected market data with indicators and interactive charts for following market movements.'
            },
            {
                title: 'Market Analysis',
                desc:
                    'Data- and news-based analysis designed to provide additional context around market developments and trends.'
            },
            {
                title: 'Cryptocurrency Pages',
                desc:
                    'Dedicated pages for selected major cryptocurrencies combining market data, related news, and analytical information.'
            },
            {
                title: 'Market Tools and Indicators',
                desc:
                    'Tools and indicators designed to help users explore market data and understand selected measurements related to digital assets.'
            }
        ],

        academy_title: 'AQL Crypto Academy',

        academy_p1:
            'AQL Crypto includes an educational Academy designed to explain cryptocurrency and blockchain concepts in a structured way. Topics range from foundational concepts to Bitcoin, Blockchain, DeFi, Staking, Web3, NFTs, wallets, trading basics, and security.',

        academy_p2:
            'The Academy is intended to provide organized educational material for readers who want to understand the industry before moving into more advanced topics. It does not provide personalized investment recommendations.',

        academy_link: 'Visit AQL Crypto Academy',

        editorial_team_title: 'Editorial Team',

        editorial_team_p1:
            'News and analysis published on AQL Crypto are presented under the editorial name Aql Crypto Editorial Team, which is the editorial identity used for content published on the platform.',

        editorial_team_p2:
            'Content preparation relies on public and specialized news sources, market data, and relevant information. Artificial Intelligence tools are used to assist with information gathering, organization, analysis, and drafting of selected materials, while sources, context, and analytical limitations are identified where appropriate.',

        methodology_title: 'Our Methodology',

        methodology_intro:
            'We aim to provide clear and understandable content while explaining how information and data used across the platform are obtained.',

        methodology_items: [
            {
                title: 'News Collection',
                desc:
                    'News is collected from public and specialized cryptocurrency sources and then processed and organized within the platform.'
            },
            {
                title: 'Content Processing',
                desc:
                    'Automated tools are used to help organize information and produce summaries, analysis, and context related to published news.'
            },
            {
                title: 'Market Data',
                desc:
                    'Prices and selected market indicators rely on external data providers identified in the relevant data and methodology sections.'
            },
            {
                title: 'Artificial Intelligence',
                desc:
                    'AI is used as an assisting technology for information analysis and drafting of selected content and is not treated as an independent source of facts.'
            },
            {
                title: 'Review and Transparency',
                desc:
                    'We aim to identify sources, context, and analytical limitations and to update or correct information when newer or more accurate data becomes available.'
            }
        ],

        editorial_policy_link: 'Read our Editorial Policy',

        data_sources_title: 'Data Sources',

        data_sources_intro:
            'Some AQL Crypto services rely on data from specialized third-party providers. Sources vary by section and are identified where relevant.',

        data_sources: [
            {
                title: 'Cryptocurrency Market Data',
                desc:
                    'CoinGecko data is used for selected cryptocurrency prices and market information.'
            },
            {
                title: 'Fear & Greed Index',
                desc:
                    'Alternative.me is used as a source for Crypto Fear & Greed Index data.'
            },
            {
                title: 'News',
                desc:
                    'News content is based on public and specialized sources, with the original source identified in published materials.'
            },
            {
                title: 'Analysis',
                desc:
                    'Analysis is based on available data, news, and context at the time of preparation and may change as new information becomes available.'
            }
        ],

        mission_title: 'Our Mission',

        mission_p1:
            'Our mission is to make cryptocurrency information clearer, more organized, and easier to access. We are building a platform that combines news, market data, education, and analysis to help users understand the industry and make their own decisions based on available information.',

        transparency_title: 'Transparency and Contact',

        transparency_p1:
            'We believe transparency is important for financial and informational content. Readers can review our Editorial Policy, Privacy Policy, and legal information, and can contact the platform through our Contact page.',

        contact_link: 'Contact Us',

        privacy_link: 'Privacy Policy',

        disclaimer_title: 'Important Notice',

        disclaimer_desc:
            'AQL Crypto is an informational, news, and educational platform. We do not provide trading or asset-management services, do not request user deposits, do not hold digital assets on behalf of users, and do not provide personalized financial or investment advice. Published information and analysis are for informational and educational purposes and may change as market data and circumstances evolve.'
    }
};

const content = computed(() => translations[locale.value] || translations.ar);
</script>