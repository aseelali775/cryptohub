<template>
    <HomeLayout>
        <Head>
            <title>
                {{ isAr ? 'اتصل بنا | AQL Crypto' : 'Contact Us | AQL Crypto' }}
            </title>

            <meta
                name="description"
                :content="
                    isAr
                        ? 'تواصل مع فريق AQL Crypto للاستفسارات العامة، الشراكات، الملاحظات التحريرية، أو الدعم الفني.'
                        : 'Contact the AQL Crypto team for general inquiries, partnerships, editorial feedback, or technical support.'
                "
            />
        </Head>

        <div
            class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300"
        >
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Header -->
                <div class="text-center mb-12">
                    <h1
                        class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight"
                    >
                        {{ content.title }}
                    </h1>

                    <p
                        class="text-slate-600 dark:text-slate-400 text-lg max-w-2xl mx-auto leading-relaxed"
                    >
                        {{ content.subtitle }}
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 md:grid-cols-3 gap-8"
                    :dir="isAr ? 'rtl' : 'ltr'"
                >

                    <!-- Contact Information -->
                    <div
                        class="md:col-span-1 bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 h-fit"
                    >
                        <h2
                            class="text-xl font-bold text-slate-900 dark:text-white mb-6"
                        >
                            {{ content.info_title }}
                        </h2>

                        <div class="space-y-7">

                            <!-- Email -->
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center shrink-0"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 8l7.89 5.26a2.6 2.6 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm text-slate-500 dark:text-slate-400 mb-1"
                                    >
                                        {{ content.email_label }}
                                    </p>

                                    <a
                                        href="mailto:cryptohubadmin665@gmail.com"
                                        class="text-slate-900 dark:text-white font-semibold hover:text-emerald-500 transition break-all text-sm sm:text-base"
                                    >
                                        cryptohubadmin665@gmail.com
                                    </a>
                                </div>
                            </div>

                            <!-- What can users contact about -->
                            <div
                                class="pt-6 border-t border-slate-100 dark:border-slate-700"
                            >
                                <h3
                                    class="text-base font-bold text-slate-900 dark:text-white mb-3"
                                >
                                    {{ content.help_title }}
                                </h3>

                                <ul
                                    class="space-y-2 text-sm text-slate-600 dark:text-slate-400 leading-relaxed"
                                >
                                    <li
                                        v-for="item in content.help_items"
                                        :key="item"
                                        class="flex items-start gap-2"
                                    >
                                        <span
                                            class="text-emerald-500 mt-1 shrink-0"
                                        >
                                            ✓
                                        </span>

                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Useful Links -->
                            <div
                                class="pt-6 border-t border-slate-100 dark:border-slate-700"
                            >
                                <h3
                                    class="text-base font-bold text-slate-900 dark:text-white mb-3"
                                >
                                    {{ content.links_title }}
                                </h3>

                                <div class="space-y-2">
                                    <a
                                        href="/about"
                                        class="block text-sm text-slate-600 dark:text-slate-400 hover:text-emerald-500 transition"
                                    >
                                        {{ content.about_link }}
                                    </a>

                                    <a
                                        href="/editorial-policy"
                                        class="block text-sm text-slate-600 dark:text-slate-400 hover:text-emerald-500 transition"
                                    >
                                        {{ content.editorial_link }}
                                    </a>

                                    <a
                                        href="/privacy-policy"
                                        class="block text-sm text-slate-600 dark:text-slate-400 hover:text-emerald-500 transition"
                                    >
                                        {{ content.privacy_link }}
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div
                        class="md:col-span-2 bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700"
                    >

                        <!-- Success Message -->
                        <div
                            v-if="$page.props.flash && $page.props.flash.success"
                            class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/50"
                        >
                            {{ formatFlashMessage($page.props.flash.success) }}
                        </div>

                        <!-- Intro -->
                        <div class="mb-7">
                            <h2
                                class="text-2xl font-bold text-slate-900 dark:text-white mb-2"
                            >
                                {{ content.form_title }}
                            </h2>

                            <p
                                class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed"
                            >
                                {{ content.form_description }}
                            </p>
                        </div>

                        <form
                            @submit.prevent="submit"
                            class="space-y-6"
                            novalidate
                        >

                            <!-- Name + Email -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                                <div>
                                    <label
                                        for="contact-name"
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                                    >
                                        {{ content.form.name }}
                                    </label>

                                    <input
                                        id="contact-name"
                                        v-model="form.name"
                                        type="text"
                                        name="name"
                                        autocomplete="name"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                        :placeholder="content.form.name_placeholder"
                                        required
                                    >

                                    <span
                                        v-if="form.errors.name"
                                        class="block text-red-500 text-sm mt-1"
                                    >
                                        {{ form.errors.name }}
                                    </span>
                                </div>

                                <div>
                                    <label
                                        for="contact-email"
                                        class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                                    >
                                        {{ content.form.email }}
                                    </label>

                                    <input
                                        id="contact-email"
                                        v-model="form.email"
                                        type="email"
                                        name="email"
                                        autocomplete="email"
                                        inputmode="email"
                                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                        :placeholder="content.form.email_placeholder"
                                        required
                                    >

                                    <span
                                        v-if="form.errors.email"
                                        class="block text-red-500 text-sm mt-1"
                                    >
                                        {{ form.errors.email }}
                                    </span>
                                </div>

                            </div>

                            <!-- Subject -->
                            <div>
                                <label
                                    for="contact-subject"
                                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                                >
                                    {{ content.form.subject }}
                                </label>

                                <input
                                    id="contact-subject"
                                    v-model="form.subject"
                                    type="text"
                                    name="subject"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition"
                                    :placeholder="content.form.subject_placeholder"
                                    required
                                >

                                <span
                                    v-if="form.errors.subject"
                                    class="block text-red-500 text-sm mt-1"
                                >
                                    {{ form.errors.subject }}
                                </span>
                            </div>

                            <!-- Message -->
                            <div>
                                <label
                                    for="contact-message"
                                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                                >
                                    {{ content.form.message }}
                                </label>

                                <textarea
                                    id="contact-message"
                                    v-model="form.message"
                                    name="message"
                                    rows="6"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition resize-none"
                                    :placeholder="content.form.message_placeholder"
                                    required
                                ></textarea>

                                <span
                                    v-if="form.errors.message"
                                    class="block text-red-500 text-sm mt-1"
                                >
                                    {{ form.errors.message }}
                                </span>
                            </div>

                            <!-- Submit -->
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-4 rounded-xl transition duration-300 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                <svg
                                    v-if="form.processing"
                                    class="animate-spin h-5 w-5 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v8H4z"
                                    />
                                </svg>

                                <span>
                                    {{
                                        form.processing
                                            ? content.form.sending
                                            : content.form.submit
                                    }}
                                </span>
                            </button>

                        </form>
                    </div>

                </div>
            </div>
        </div>
    </HomeLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import HomeLayout from '@/layouts/HomeLayout.vue';

const page = usePage();

const locale = computed(() => page.props.locale || 'ar');
const isAr = computed(() => locale.value === 'ar');

const form = useForm({
    name: '',
    email: '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post('/contact', {
        preserveScroll: true,

        onSuccess: () => {
            form.reset();
        },
    });
};

// فصل رسالة النجاح حسب اللغة
// الكنترولر الحالي يرسل النص العربي والإنجليزي مفصولين بعلامة |
const formatFlashMessage = (msg) => {
    if (!msg) {
        return '';
    }

    const parts = msg.split('|');

    return isAr.value
        ? parts[0].trim()
        : (parts[1] ? parts[1].trim() : parts[0].trim());
};

const translations = {
    ar: {
        title: 'اتصل بنا',

        subtitle:
            'يمكنك التواصل مع فريق AQL Crypto للاستفسارات العامة، الشراكات، الملاحظات التحريرية، أو الدعم الفني.',

        info_title: 'معلومات التواصل',

        email_label: 'البريد الإلكتروني:',

        help_title: 'يمكنك التواصل معنا بشأن',

        help_items: [
            'الاستفسارات العامة حول محتوى الموقع وخدماته.',
            'الملاحظات أو التصحيحات المتعلقة بالأخبار والمحتوى.',
            'الاستفسارات المتعلقة بالشراكات والتعاون.',
            'المشكلات التقنية أو صعوبات استخدام الموقع.',
        ],

        links_title: 'روابط مفيدة',

        about_link: 'من نحن',

        editorial_link: 'السياسة التحريرية',

        privacy_link: 'سياسة الخصوصية',

        form_title: 'أرسل لنا رسالة',

        form_description:
            'استخدم النموذج أدناه لإرسال استفسارك أو ملاحظتك، وسنتمكن من مراجعة رسالتك والرد عليها عبر البريد الإلكتروني.',

        form: {
            name: 'الاسم الكامل',
            name_placeholder: 'أدخل اسمك',

            email: 'البريد الإلكتروني',
            email_placeholder: 'example@email.com',

            subject: 'الموضوع',
            subject_placeholder: 'موضوع الرسالة',

            message: 'الرسالة',
            message_placeholder: 'اكتب رسالتك أو استفسارك هنا...',

            submit: 'إرسال الرسالة',
            sending: 'جارٍ الإرسال...',
        },
    },

    en: {
        title: 'Contact Us',

        subtitle:
            'Contact the AQL Crypto team for general inquiries, partnerships, editorial feedback, or technical support.',

        info_title: 'Contact Information',

        email_label: 'Email Address:',

        help_title: 'You can contact us about',

        help_items: [
            'General questions about our content and services.',
            'Feedback or corrections regarding news and published content.',
            'Partnership and collaboration inquiries.',
            'Technical issues or difficulties using the website.',
        ],

        links_title: 'Useful Links',

        about_link: 'About Us',

        editorial_link: 'Editorial Policy',

        privacy_link: 'Privacy Policy',

        form_title: 'Send Us a Message',

        form_description:
            'Use the form below to send your question or feedback. We will review your message and respond by email when appropriate.',

        form: {
            name: 'Full Name',
            name_placeholder: 'Enter your name',

            email: 'Email Address',
            email_placeholder: 'example@email.com',

            subject: 'Subject',
            subject_placeholder: 'Message subject',

            message: 'Message',
            message_placeholder: 'Write your message or inquiry here...',

            submit: 'Send Message',
            sending: 'Sending...',
        },
    },
};

const content = computed(() => translations[locale.value] || translations.ar);
</script>