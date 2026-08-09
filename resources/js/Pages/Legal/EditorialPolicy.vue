<template>
    <HomeLayout>
        <Head>
            <title>{{ isAr ? 'سياسة التحرير | AQL Crypto' : 'Editorial Policy | AQL Crypto' }}</title>
            <meta name="description" :content="isAr ? 'تعرف على معايير الشفافية، مصادر البيانات، وكيفية عمل خوارزميات الذكاء الاصطناعي في منصة AQL Crypto.' : 'Learn about our transparency standards, data sources, and how AI algorithms work at AQL Crypto.'" />
        </Head>

        <div class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="mb-12 border-l-4 border-emerald-500 pl-4 rtl:pr-4 rtl:pl-0 rtl:border-l-0 rtl:border-r-4">
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                        {{ content.title }}
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 text-lg">{{ content.subtitle }}</p>
                </div>

                <div class="space-y-8 text-slate-700 dark:text-slate-300 leading-relaxed text-lg" :dir="isAr ? 'rtl' : 'ltr'">
                    <section v-for="(section, index) in content.sections" :key="index" class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-emerald-200 dark:hover:border-emerald-800/50 transition-colors">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
                            <span class="w-8 h-8 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-black">{{ index + 1 }}</span>
                            {{ section.title }}
                        </h2>
                        <div class="space-y-4" v-html="section.body"></div>
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
        title: "سياسة التحرير والشفافية",
        subtitle: "نلتزم في AQL Crypto بتقديم معلومات دقيقة وموثوقة لجمهورنا.",
        sections: [
            {
                title: "كيف يتم جمع الأخبار والبيانات؟",
                body: "<p>تعتمد منصة <strong>AQL Crypto</strong> على استخراج الأخبار من وكالات الأنباء المالية ومواقع العملات الرقمية المعتمدة والموثوقة عالمياً. أما بالنسبة لأسعار العملات وحجم التداول، فيتم جلبها لحظياً عبر واجهات برمجة تطبيقات (APIs) مرخصة ومباشرة من الأسواق لضمان الدقة.</p>"
            },
            {
                title: "كيف يعمل مرصد ذكاء السوق (AI Market)؟",
                body: "<p>نحن نستخدم نماذج لغوية ضخمة ومتقدمة للذكاء الاصطناعي (LLMs) لمعالجة الأخبار الخام. يقوم الذكاء الاصطناعي بقراءة الخبر، إعادة صياغته بأسلوب صحفي احترافي، واستنتاج معنويات السوق (Sentiment) ما إذا كان تأثير الخبر <strong>إيجابياً (Bullish)</strong>، <strong>سلبياً (Bearish)</strong>، أو <strong>محايداً (Neutral)</strong> بناءً على سياق الحدث.</p>"
            },
            {
                title: "كيف يتم حساب درجة التأثير (Impact Score)؟",
                body: "<p>يقوم محرك الذكاء الاصطناعي الخاص بنا بتعيين درجة تأثير تتراوح من 1 إلى 10 لكل خبر. تُحسب هذه الدرجة بناءً على عوامل معقدة تشمل: حجم القيمة السوقية للعملة المذكورة، طبيعة الخبر (تنظيمي، اختراق أمني، شراكة كبرى)، وحجم السيولة المتوقع تأثرها. الأخبار ذات الدرجة (8-10) تعتبر أخباراً حاسمة قادرة على تغيير اتجاه السوق.</p>"
            },
            {
                title: "سياسة المراجعة وتحديد المحتوى الآلي",
                body: "<p>نؤمن بالشفافية المطلقة مع مستخدمينا. لذلك، أي محتوى يتم تلخيصه أو تحليله بواسطة الذكاء الاصطناعي يحمل علامة واضحة (AI Disclaimer). ورغم أننا نعتمد على الأتمتة لضمان سرعة نقل الخبر للمستثمر، إلا أن أنظمتنا تخضع لمراقبة دورية من قبل فريقنا لضمان الجودة وعدم وجود استنتاجات خاطئة (AI Hallucinations).</p>"
            },
            {
                title: "حيادية المنصة",
                body: "<p>منصة AQL Crypto هي جهة إعلامية <strong>مستقلة تماماً</strong>. نحن لا نروج لأي عملة رقمية بشكل مدفوع، ولا نقبل مقالات الرعاية المضللة، ولا نشجع على شراء أصل مالي معين. جميع تحليلاتنا مبنية على قراءة مجردة للبيانات والأحداث.</p>"
            }
        ]
    },
    en: {
        title: "Editorial & Transparency Policy",
        subtitle: "At AQL Crypto, we are committed to delivering accurate and reliable intelligence.",
        sections: [
            {
                title: "How are News and Data Collected?",
                body: "<p><strong>AQL Crypto</strong> aggregates news from certified global financial news agencies and trusted cryptocurrency portals. Cryptocurrency prices and trading volumes are fetched in real-time through licensed APIs directly from the markets to ensure absolute accuracy.</p>"
            },
            {
                title: "How does the AI Market Intelligence Work?",
                body: "<p>We utilize advanced Large Language Models (LLMs) to process raw news. The AI reads the news, rewrites it in a professional journalistic tone, and deduces the market sentiment—whether the news impact is <strong>Bullish</strong>, <strong>Bearish</strong>, or <strong>Neutral</strong> based on the context of the event.</p>"
            },
            {
                title: "How is the Impact Score Calculated?",
                body: "<p>Our AI engine assigns an Impact Score ranging from 1 to 10 for each article. This score is calculated based on complex factors including: the market cap of the mentioned asset, the nature of the news (regulatory, security breach, major partnership), and the expected impact on liquidity. Scores of (8-10) represent critical, market-moving events.</p>"
            },
            {
                title: "Review Policy and AI Labeling",
                body: "<p>We believe in absolute transparency. Therefore, any content summarized or analyzed by AI carries a clear AI Disclaimer. Although we rely on automation to ensure rapid news delivery, our systems undergo periodic human oversight to ensure quality and prevent AI hallucinations.</p>"
            },
            {
                title: "Platform Neutrality",
                body: "<p>AQL Crypto is a strictly <strong>independent</strong> media and data entity. We do not engage in paid promotions for specific cryptocurrencies, we do not accept misleading sponsored articles, and we do not encourage the purchase of any specific financial asset. All our analyses are based on an objective reading of data and events.</p>"
            }
        ]
    }
};

const content = computed(() => translations[locale.value]);
</script>