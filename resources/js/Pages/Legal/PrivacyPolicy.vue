<template>
    <HomeLayout>
        <Head>
            <title>
                {{ isAr ? 'سياسة الخصوصية | AQL Crypto' : 'Privacy Policy | AQL Crypto' }}
            </title>

            <meta
                name="description"
                :content="
                    isAr
                        ? 'تعرف على سياسة الخصوصية لمنصة AQL Crypto وكيفية جمع واستخدام وحماية بيانات المستخدمين وملفات تعريف الارتباط.'
                        : 'Read the Privacy Policy of AQL Crypto to understand how we collect, use, and protect user data and cookies.'
                "
            />
        </Head>

        <div class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Page Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                        {{ content.title }}
                    </h1>

                    <p class="text-slate-500 dark:text-slate-400">
                        {{ content.last_updated }}
                    </p>
                </div>

                <!-- Privacy Sections -->
                <div
                    class="space-y-8 text-slate-700 dark:text-slate-300 leading-relaxed text-lg"
                    :dir="isAr ? 'rtl' : 'ltr'"
                >
                    <section
                        v-for="(section, index) in content.sections"
                        :key="index"
                        class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                            {{ section.title }}
                        </h2>

                        <div
                            class="space-y-4"
                            v-html="section.body"
                        ></div>
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

        last_updated: "آخر تحديث: سبتمبر 2026",

        sections: [
            {
                title: "1. مقدمة",

                body: `
                    <p>
                        نحن في <strong>AQL Crypto</strong> نأخذ خصوصيتك على محمل الجد.
                        توضح سياسة الخصوصية هذه كيفية جمع واستخدام وحماية المعلومات
                        عند زيارتك لمنصتنا واستخدامك لخدماتها ومحتواها.
                    </p>

                    <p>
                        باستخدامك لمنصة AQL Crypto، فإنك تقر بقراءة وفهم هذه السياسة
                        بالقدر الذي ينطبق على استخدامك للموقع.
                    </p>
                `
            },

            {
                title: "2. البيانات التي نجمعها",

                body: `
                    <p>
                        قد نجمع أنواعًا مختلفة من المعلومات وفقًا لطريقة استخدامك للموقع:
                    </p>

                    <ul class="list-disc ms-6 mt-2 space-y-2">
                        <li>
                            <strong>معلومات تقنية وغير شخصية:</strong>
                            مثل نوع المتصفح، نظام التشغيل، عنوان IP، نوع الجهاز،
                            والصفحات التي تتم زيارتها، وذلك لأغراض تشغيل الموقع
                            وتحسين الأداء وتجربة المستخدم.
                        </li>

                        <li>
                            <strong>معلومات يقدمها المستخدم:</strong>
                            مثل الاسم والبريد الإلكتروني والرسالة، عند استخدام
                            نموذج "اتصل بنا" وإرسال هذه المعلومات طوعًا.
                        </li>

                        <li>
                            <strong>بيانات الاستخدام:</strong>
                            قد يتم جمع معلومات إحصائية حول كيفية استخدام الموقع
                            من خلال خدمات التحليل المستخدمة على المنصة.
                        </li>
                    </ul>
                `
            },

            {
                title: "3. استخدام ملفات تعريف الارتباط (Cookies)",

                body: `
                    <p>
                        تستخدم AQL Crypto ملفات تعريف الارتباط (Cookies) وتقنيات
                        مشابهة للمساعدة في تشغيل الموقع وتحسين تجربة المستخدم
                        وحفظ بعض التفضيلات، مثل اللغة ووضع العرض.
                    </p>

                    <p>
                        قد تُستخدم ملفات تعريف الارتباط أيضًا من قبل خدمات الطرف الثالث
                        المستخدمة على الموقع لأغراض التحليل، وقياس الأداء، وعرض الإعلانات
                        عندما تصبح هذه الخدمات مفعلة.
                    </p>

                    <p>
                        يمكنك التحكم في ملفات تعريف الارتباط من خلال إعدادات المتصفح،
                        مع العلم أن تعطيل بعض ملفات تعريف الارتباط قد يؤثر في بعض
                        وظائف الموقع أو تجربة استخدامه.
                    </p>
                `
            },

            {
                title: "4. Google Analytics",

                body: `
                    <p>
                        قد تستخدم AQL Crypto خدمة <strong>Google Analytics</strong>
                        لفهم كيفية استخدام الزوار للموقع، مثل الصفحات التي تتم زيارتها
                        ومعلومات الاستخدام والأداء.
                    </p>

                    <p>
                        تُستخدم هذه المعلومات لأغراض تحليلية وتحسين المحتوى وتجربة
                        المستخدم وأداء المنصة.
                    </p>
                `
            },

            {
                title: "5. الإعلانات وGoogle AdSense",

                body: `
                    <p>
                        قد تستخدم AQL Crypto خدمة <strong>Google AdSense</strong>
                        لعرض الإعلانات على صفحات الموقع.
                    </p>

                    <p>
                        عند عرض إعلانات Google أو إعلانات من شركائها، قد تستخدم
                        Google ومزودو الإعلانات من الأطراف الثالثة ملفات تعريف
                        الارتباط وتقنيات مشابهة للمساعدة في عرض الإعلانات وقياس
                        أدائها وتحديد مدى ملاءمتها للمستخدم.
                    </p>

                    <p>
                        قد تعتمد الإعلانات المخصصة، حيثما كانت مفعلة ومسموحًا بها،
                        على معلومات مرتبطة بزيارات المستخدم السابقة إلى هذا الموقع
                        أو مواقع أخرى.
                    </p>

                    <p>
                        يمكن للمستخدمين إدارة إعدادات الإعلانات المخصصة من خلال
                        <a
                            href="https://adssettings.google.com/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-emerald-500 hover:underline font-bold"
                        >
                            إعدادات إعلانات Google
                        </a>.
                    </p>

                    <p>
                        قد تستخدم خدمات إعلانية أخرى ملفات تعريف ارتباط أو تقنيات
                        مشابهة وفقًا لسياساتها الخاصة، وسيتم توضيح الجهات ذات الصلة
                        عندما تكون مطلوبة وفق إعدادات الخدمات والقوانين المعمول بها.
                    </p>
                `
            },

            {
                title: "6. خدمات الطرف الثالث",

                body: `
                    <p>
                        قد تعتمد AQL Crypto على بعض الخدمات الخارجية لتشغيل الموقع
                        وتحليل الأداء وحمايته، ومن أمثلتها:
                    </p>

                    <ul class="list-disc ms-6 mt-2 space-y-2">
                        <li>
                            <strong>Google Analytics:</strong>
                            لتحليل حركة المرور واستخدام الموقع.
                        </li>

                        <li>
                            <strong>Google AdSense:</strong>
                            لعرض الإعلانات وقياس أدائها عند تفعيل الخدمة.
                        </li>

                        <li>
                            <strong>Cloudflare:</strong>
                            للمساعدة في حماية الموقع وتحسين الأداء وسرعة الوصول.
                        </li>
                    </ul>

                    <p class="mt-4">
                        قد تخضع هذه الخدمات لسياسات الخصوصية الخاصة بالجهات التي
                        تقدمها، ولذلك ننصح المستخدمين بمراجعة سياسات تلك الجهات
                        للحصول على مزيد من المعلومات حول كيفية معالجة البيانات.
                    </p>
                `
            },

            {
                title: "7. خصوصية المستخدمين في المناطق التي تتطلب موافقة",

                body: `
                    <p>
                        قد تختلف متطلبات الخصوصية والموافقة بحسب موقع المستخدم
                        والقوانين المعمول بها.
                    </p>

                    <p>
                        بالنسبة للمستخدمين في المناطق التي تتطلب الحصول على موافقة
                        لاستخدام ملفات تعريف الارتباط أو معالجة البيانات لأغراض
                        الإعلانات المخصصة، قد تستخدم AQL Crypto آليات الموافقة
                        المناسبة التي توفرها Google أو خدمات إدارة الموافقة المعتمدة.
                    </p>

                    <p>
                        قد يُطلب من المستخدم اختيار ما إذا كان يوافق على استخدام
                        بياناته وملفات تعريف الارتباط لأغراض الإعلانات المخصصة،
                        وفقًا للإعدادات والمتطلبات المعمول بها.
                    </p>
                `
            },

            {
                title: "8. حماية البيانات",

                body: `
                    <p>
                        نتخذ إجراءات تقنية وتنظيمية مناسبة للمساعدة في حماية
                        المعلومات التي يتم جمعها أو تقديمها عبر المنصة من الوصول
                        غير المصرح به أو الاستخدام غير المشروع.
                    </p>

                    <p>
                        ومع ذلك، لا يمكن ضمان أن تكون عملية نقل أو تخزين البيانات
                        عبر الإنترنت آمنة بشكل مطلق.
                    </p>
                `
            },

            {
                title: "9. حقوق المستخدم",

                body: `
                    <p>
                        يحق لك طلب الاطلاع على البيانات الشخصية التي قدمتها لنا
                        من خلال نماذج الاتصال، أو طلب تصحيحها أو حذفها، وفقًا
                        لما تسمح به القوانين واللوائح المعمول بها.
                    </p>

                    <p>
                        يمكنك تقديم طلبك من خلال
                        <a
                            href="/contact"
                            class="text-emerald-500 hover:underline font-bold"
                        >
                            صفحة اتصل بنا
                        </a>.
                    </p>
                `
            },

            {
                title: "10. تحديث سياسة الخصوصية",

                body: `
                    <p>
                        قد نقوم بتحديث سياسة الخصوصية هذه من وقت لآخر لمواكبة
                        التغييرات في خدمات الموقع أو متطلبات الخصوصية والقوانين
                        المعمول بها.
                    </p>

                    <p>
                        سيتم نشر أي تحديثات على هذه الصفحة مع تعديل تاريخ آخر تحديث
                        الموضح في أعلى الصفحة.
                    </p>
                `
            }
        ]
    },

    en: {
        title: "Privacy Policy",

        last_updated: "Last Updated: September 2026",

        sections: [
            {
                title: "1. Introduction",

                body: `
                    <p>
                        At <strong>AQL Crypto</strong>, we take your privacy seriously.
                        This Privacy Policy explains how information may be collected,
                        used, and protected when you visit and use our platform and its content.
                    </p>

                    <p>
                        By using AQL Crypto, you acknowledge that you have read and
                        understood this policy to the extent applicable to your use
                        of the website.
                    </p>
                `
            },

            {
                title: "2. Data We Collect",

                body: `
                    <p>
                        We may collect different types of information depending on
                        how you use the website:
                    </p>

                    <ul class="list-disc ms-6 mt-2 space-y-2">
                        <li>
                            <strong>Technical and non-personal information:</strong>
                            Such as browser type, operating system, IP address,
                            device type, and pages visited, for operating the website,
                            improving performance, and enhancing user experience.
                        </li>

                        <li>
                            <strong>Information provided by users:</strong>
                            Such as name, email address, and message when you voluntarily
                            submit information through our Contact Us form.
                        </li>

                        <li>
                            <strong>Usage data:</strong>
                            Statistical information about how visitors use the website
                            may be collected through analytics services used on the platform.
                        </li>
                    </ul>
                `
            },

            {
                title: "3. Use of Cookies",

                body: `
                    <p>
                        AQL Crypto uses Cookies and similar technologies to help
                        operate the website, improve the browsing experience, and
                        save certain preferences such as language and display mode.
                    </p>

                    <p>
                        Cookies may also be used by third-party services on the website
                        for analytics, performance measurement, and advertising when
                        such services are enabled.
                    </p>

                    <p>
                        You can control Cookies through your browser settings.
                        Disabling certain Cookies may affect some website functions
                        or your overall browsing experience.
                    </p>
                `
            },

            {
                title: "4. Google Analytics",

                body: `
                    <p>
                        AQL Crypto may use <strong>Google Analytics</strong> to understand
                        how visitors use the website, including visited pages,
                        usage information, and performance data.
                    </p>

                    <p>
                        This information is used for analytics and to improve content,
                        user experience, and platform performance.
                    </p>
                `
            },

            {
                title: "5. Advertising and Google AdSense",

                body: `
                    <p>
                        AQL Crypto may use <strong>Google AdSense</strong> to display
                        advertisements on website pages.
                    </p>

                    <p>
                        When Google ads or ads from its partners are displayed,
                        Google and third-party advertising providers may use Cookies
                        and similar technologies to help serve ads, measure their
                        performance, and determine their relevance.
                    </p>

                    <p>
                        Where enabled and legally permitted, personalized advertising
                        may be based on information associated with a user's previous
                        visits to this website or other websites.
                    </p>

                    <p>
                        Users can manage personalized advertising preferences through
                        <a
                            href="https://adssettings.google.com/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-emerald-500 hover:underline font-bold"
                        >
                            Google Ads Settings
                        </a>.
                    </p>

                    <p>
                        Other advertising services may use Cookies or similar
                        technologies according to their own policies. Relevant
                        providers will be identified where required by applicable
                        service settings and laws.
                    </p>
                `
            },

            {
                title: "6. Third-Party Services",

                body: `
                    <p>
                        AQL Crypto may rely on external services to operate,
                        analyze, protect, and improve the website, including:
                    </p>

                    <ul class="list-disc ms-6 mt-2 space-y-2">
                        <li>
                            <strong>Google Analytics:</strong>
                            For analyzing website traffic and usage.
                        </li>

                        <li>
                            <strong>Google AdSense:</strong>
                            For displaying advertisements and measuring advertising
                            performance when the service is enabled.
                        </li>

                        <li>
                            <strong>Cloudflare:</strong>
                            To help protect the website and improve performance
                            and delivery speed.
                        </li>
                    </ul>

                    <p class="mt-4">
                        These services may be subject to the privacy policies of
                        their respective providers. Users are encouraged to review
                        those policies for additional information about how data
                        may be processed.
                    </p>
                `
            },

            {
                title: "7. Privacy and Consent Requirements",

                body: `
                    <p>
                        Privacy and consent requirements may vary depending on the
                        user's location and applicable laws.
                    </p>

                    <p>
                        For users in regions where consent is required for the use
                        of Cookies or the processing of personal data for personalized
                        advertising, AQL Crypto may use appropriate consent mechanisms
                        provided by Google or certified consent management services.
                    </p>

                    <p>
                        Users may be asked to choose whether they consent to the use
                        of data and Cookies for personalized advertising in accordance
                        with applicable settings and requirements.
                    </p>
                `
            },

            {
                title: "8. Data Security",

                body: `
                    <p>
                        We take reasonable technical and organizational measures
                        to help protect information collected or submitted through
                        the platform against unauthorized access or unlawful use.
                    </p>

                    <p>
                        However, no method of transmission or storage over the Internet
                        can be guaranteed to be completely secure.
                    </p>
                `
            },

            {
                title: "9. User Rights",

                body: `
                    <p>
                        You may request access to, correction of, or deletion of
                        personal information you have submitted to us through
                        contact forms, subject to applicable laws and regulations.
                    </p>

                    <p>
                        You can submit such a request through our
                        <a
                            href="/contact"
                            class="text-emerald-500 hover:underline font-bold"
                        >
                            Contact Us
                        </a>
                        page.
                    </p>
                `
            },

            {
                title: "10. Updates to This Privacy Policy",

                body: `
                    <p>
                        We may update this Privacy Policy from time to time to reflect
                        changes in our services or applicable privacy requirements and laws.
                    </p>

                    <p>
                        Any updates will be published on this page together with
                        an updated revision date shown at the top of the policy.
                    </p>
                `
            }
        ]
    }
};

const content = computed(() => translations[locale.value]);
</script>