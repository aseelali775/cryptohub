<template>
    <HomeLayout>
        <Head>
            <title>{{ isAr ? 'إخلاء المسؤولية | AQL Crypto' : 'Disclaimer | AQL Crypto' }}</title>
            <meta name="description" :content="isAr ? 'إخلاء مسؤولية قانوني ومالي هام لاستخدام منصة AQL Crypto.' : 'Important legal and financial disclaimer for using the AQL Crypto platform.'" />
        </Head>

        <div class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="mb-12 border-l-4 border-red-500 pl-4 rtl:pr-4 rtl:pl-0 rtl:border-l-0 rtl:border-r-4">
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                        {{ content.title }}
                    </h1>
                    <p class="text-red-500 font-bold">{{ content.alert }}</p>
                </div>

                <div class="space-y-8 text-slate-700 dark:text-slate-300 leading-relaxed text-lg" :dir="isAr ? 'rtl' : 'ltr'">
                    <section v-for="(section, index) in content.sections" :key="index" class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
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
        title: "إخلاء المسؤولية",
        alert: "يرجى قراءة هذه الصفحة بعناية قبل اتخاذ أي قرارات مالية.",
        sections: [
            {
                title: "1. ليست نصيحة استثمارية (No Financial Advice)",
                body: "<p>المعلومات، التحليلات، وأسعار العملات المنشورة على <strong>AQL Crypto</strong> هي لأغراض <strong>تعليمية وإعلامية فقط</strong>، ولا تمثل بأي حال من الأحوال نصيحة استثمارية، أو مالية، أو قانونية. لا يجب أن تتخذ أي قرار مالي بناءً على المحتوى الموجود هنا دون الرجوع لمستشار مالي معتمد.</p>"
            },
            {
                title: "2. مخاطر السوق العالية",
                body: "<p>سوق العملات الرقمية (الكريبتو) هو سوق شديد التقلب ويحمل مخاطر عالية جداً. قد تفقد جزءاً كبيراً أو كل رأس مالك. <strong>أنت وحدك تتحمل المسؤولية الكاملة</strong> عن أي خسائر مالية قد تتكبدها نتيجة تداولك أو استثمارك.</p>"
            },
            {
                title: "3. دقة البيانات والمحتوى المولد بالذكاء الاصطناعي",
                body: "<p>بعض أقسام المنصة، مثل (AI Market) وتقارير العملات، يتم إنشاؤها وتلخيصها بواسطة تقنيات الذكاء الاصطناعي. على الرغم من سعينا لتقديم بيانات دقيقة، إلا أننا <strong>لا نضمن خلوها من الأخطاء</strong>، والتأخير، أو الهلوسة البرمجية. الأسعار والبيانات تُقدم 'كما هي' (AS IS) من مصادر خارجية.</p>"
            },
            {
                title: "4. الروابط الخارجية",
                body: "<p>قد تحتوي المنصة على روابط لمواقع خارجية. نحن غير مسؤولين عن محتوى أو سياسات تلك المواقع، وزيارتك لها تكون على مسؤوليتك الخاصة.</p>"
            }
        ]
    },
    en: {
        title: "Disclaimer",
        alert: "Please read this page carefully before making any financial decisions.",
        sections: [
            {
                title: "1. No Financial Advice",
                body: "<p>The information, analysis, and prices published on <strong>AQL Crypto</strong> are provided for <strong>educational and informational purposes only</strong>. They do not constitute financial, investment, or legal advice. You should not make any financial decisions based on our content without consulting a certified financial advisor.</p>"
            },
            {
                title: "2. High Market Risks",
                body: "<p>The cryptocurrency market is highly volatile and carries extreme risks. You may lose a significant portion or all of your capital. <strong>You alone are fully responsible</strong> for any financial losses resulting from your trading or investing activities.</p>"
            },
            {
                title: "3. Data Accuracy & AI-Generated Content",
                body: "<p>Certain sections of the platform, such as AI Market and coin reports, are generated and summarized by Artificial Intelligence. While we strive for accuracy, we <strong>do not guarantee</strong> that the data is free from errors, delays, or AI hallucinations. Prices and data are provided 'AS IS' from external sources.</p>"
            },
            {
                title: "4. External Links",
                body: "<p>The platform may contain links to third-party websites. We are not responsible for the content or policies of those sites, and visiting them is at your own risk.</p>"
            }
        ]
    }
};

const content = computed(() => translations[locale.value]);
</script>