<template>
    <HomeLayout>
        <Head>
            <title>{{ isAr ? 'شروط الاستخدام | AQL Crypto' : 'Terms of Use | AQL Crypto' }}</title>
            <meta name="description" :content="isAr ? 'شروط وقواعد استخدام منصة AQL Crypto لمعلومات العملات الرقمية.' : 'Terms and conditions for using the AQL Crypto intelligence platform.'" />
        </Head>

        <div class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="mb-12">
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                        {{ content.title }}
                    </h1>
                </div>

                <div class="space-y-8 text-slate-700 dark:text-slate-300 leading-relaxed text-lg" :dir="isAr ? 'rtl' : 'ltr'">
                    <section v-for="(section, index) in content.sections" :key="index" class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">{{ section.title }}</h2>
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
        title: "شروط الاستخدام",
        sections: [
            {
                title: "1. قبول الشروط",
                body: "<p>دخولك واستخدامك لمنصة <strong>AQL Crypto</strong> يعني موافقتك الكاملة على هذه الشروط. إذا كنت لا توافق على أي جزء منها، يُرجى التوقف عن استخدام المنصة فوراً.</p>"
            },
            {
                title: "2. طبيعة المحتوى",
                body: "<p>جميع المحتويات المتوفرة على المنصة، بما في ذلك الأخبار، التحليلات، أسعار العملات، وتقارير الذكاء الاصطناعي، مقدمة <strong>لأغراض إعلامية وتثقيفية فقط</strong>. قد يتم تحديث المحتوى أو تغييره دون إشعار مسبق.</p>"
            },
            {
                title: "3. الاستخدام المقبول",
                body: "<p>يُمنع منعاً باتاً استخدام المنصة بطرق تضر بالخوادم، أو محاولة اختراق الأنظمة، أو استخدام برمجيات (Scraping/Bots) لسحب البيانات بطريقة مكثفة دون إذن كتابي مسبق.</p>"
            },
            {
                title: "4. حقوق الملكية الفكرية",
                body: "<p>جميع الحقوق، العلامات التجارية، التصاميم، والمحتوى المكتوب (باستثناء الاقتباسات الإخبارية المنسوبة لمصادرها) هي ملك حصري لمنصة AQL Crypto.</p>"
            }
        ]
    },
    en: {
        title: "Terms of Use",
        sections: [
            {
                title: "1. Acceptance of Terms",
                body: "<p>By accessing and using <strong>AQL Crypto</strong>, you fully accept these terms. If you disagree with any part, please discontinue use immediately.</p>"
            },
            {
                title: "2. Nature of Content",
                body: "<p>All content on the platform, including news, analysis, coin prices, and AI reports, is provided for <strong>informational and educational purposes only</strong>. Content is subject to change without notice.</p>"
            },
            {
                title: "3. Acceptable Use",
                body: "<p>It is strictly prohibited to use the platform in ways that harm our servers, attempt to hack systems, or use scraping bots to extract data heavily without prior written permission.</p>"
            },
            {
                title: "4. Intellectual Property",
                body: "<p>All rights, trademarks, designs, and original written content (excluding quoted news attributed to sources) are the exclusive property of AQL Crypto.</p>"
            }
        ]
    }
};

const content = computed(() => translations[locale.value]);
</script>