<template>
    <HomeLayout>
        <Head>
            <title>{{ isAr ? 'سياسة الخصوصية | AQL Crypto' : 'Privacy Policy | AQL Crypto' }}</title>
            <meta name="description" :content="isAr ? 'تعرف على سياسة الخصوصية لمنصة AQL Crypto وكيفية تعاملنا مع بياناتك وحمايتها.' : 'Read the Privacy Policy of AQL Crypto to understand how we handle and protect your data.'" />
        </Head>

        <div class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="mb-12">
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                        {{ content.title }}
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400">{{ content.last_updated }}</p>
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
        title: "سياسة الخصوصية",
        last_updated: "آخر تحديث: أغسطس 2026",
        sections: [
            {
                title: "1. مقدمة",
                body: "<p>نحن في <strong>AQL Crypto</strong> نأخذ خصوصيتك على محمل الجد. تشرح سياسة الخصوصية هذه كيفية جمع واستخدام وحماية معلوماتك الشخصية عند زيارتك لمنصتنا واستخدامك لخدماتنا.</p>"
            },
            {
                title: "2. البيانات التي نجمعها",
                body: "<p>نقوم بجمع نوعين من المعلومات:</p><ul class='list-disc ms-6 mt-2 space-y-2'><li><strong>معلومات غير شخصية (تلقائية):</strong> مثل نوع المتصفح، نظام التشغيل، عنوان IP، والصفحات التي تزورها، وذلك لتحسين تجربة المستخدم.</li><li><strong>معلومات شخصية:</strong> مثل الاسم والبريد الإلكتروني، والتي يتم جمعها فقط عند قيامك بملء نموذج (اتصل بنا) بإرادتك.</li></ul>"
            },
            {
                title: "3. استخدام ملفات تعريف الارتباط (Cookies)",
                body: "<p>نستخدم ملفات تعريف الارتباط (Cookies) لتحسين تجربة تصفحك، وحفظ تفضيلاتك (مثل اختيار اللغة والوضع الليلي)، وتقديم محتوى وإعلانات مخصصة.</p>"
            },
            {
                title: "4. خدمات الطرف الثالث",
                body: "<p>قد نستخدم خدمات من جهات خارجية لتحسين أداء المنصة وتحليلها، وتشمل:</p><ul class='list-disc ms-6 mt-2 space-y-2'><li><strong>Google Analytics:</strong> لتحليل حركة المرور وفهم سلوك الزوار.</li><li><strong>Google AdSense (مستقبلاً):</strong> لعرض إعلانات ذات صلة. قد تستخدم جوجل ملفات تعريف ارتباط (DoubleClick DART) لعرض إعلانات بناءً على زياراتك السابقة.</li><li><strong>Cloudflare:</strong> لحماية الموقع من الهجمات الخبيثة وتحسين سرعة التحميل.</li></ul>"
            },
            {
                title: "5. حقوق المستخدم",
                body: "<p>يحق لك كمستخدم طلب الاطلاع على أي بيانات شخصية أرسلتها لنا (عبر نماذج الاتصال) أو طلب حذفها. يمكنك ذلك عبر مراسلتنا على البريد الرسمي: <strong>support@aqlcrypto.com</strong></p>"
            }
        ]
    },
    en: {
        title: "Privacy Policy",
        last_updated: "Last Updated: August 2026",
        sections: [
            {
                title: "1. Introduction",
                body: "<p>At <strong>AQL Crypto</strong>, we take your privacy seriously. This Privacy Policy explains how we collect, use, and protect your personal information when you visit and use our platform.</p>"
            },
            {
                title: "2. Data We Collect",
                body: "<p>We collect two types of information:</p><ul class='list-disc ms-6 mt-2 space-y-2'><li><strong>Non-Personal Information (Automatic):</strong> Such as browser type, operating system, IP address, and pages visited, to enhance user experience.</li><li><strong>Personal Information:</strong> Such as name and email, collected only when you voluntarily fill out our Contact Us form.</li></ul>"
            },
            {
                title: "3. Use of Cookies",
                body: "<p>We use Cookies to improve your browsing experience, save your preferences (like language and dark mode), and provide personalized content and ads.</p>"
            },
            {
                title: "4. Third-Party Services",
                body: "<p>We may use third-party services to analyze and improve platform performance, including:</p><ul class='list-disc ms-6 mt-2 space-y-2'><li><strong>Google Analytics:</strong> To analyze traffic and understand visitor behavior.</li><li><strong>Google AdSense (Future):</strong> To serve relevant ads. Google may use DoubleClick DART cookies to serve ads based on your prior visits.</li><li><strong>Cloudflare:</strong> For DDoS protection and improving load speeds.</li></ul>"
            },
            {
                title: "5. User Rights",
                body: "<p>You have the right to request access to or deletion of any personal data you have submitted to us (e.g., via contact forms). You can do this by emailing us at: <strong>support@aqlcrypto.com</strong></p>"
            }
        ]
    }
};

const content = computed(() => translations[locale.value]);
</script>