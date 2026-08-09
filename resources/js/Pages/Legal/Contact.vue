<template>
    <HomeLayout>
        <Head>
            <title>{{ isAr ? 'اتصل بنا | AQL Crypto' : 'Contact Us | AQL Crypto' }}</title>
            <meta name="description" :content="isAr ? 'تواصل مع فريق AQL Crypto لأي استفسارات، شراكات، أو دعم فني.' : 'Get in touch with the AQL Crypto team for inquiries, partnerships, or technical support.'" />
        </Head>

        <div class="min-h-screen py-16 bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white mb-4 tracking-tight">
                        {{ content.title }}
                    </h1>
                    <p class="text-slate-600 dark:text-slate-400 text-lg">{{ content.subtitle }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8" :dir="isAr ? 'rtl' : 'ltr'">
                    
                    <div class="md:col-span-1 bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 h-fit">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6">{{ content.info_title }}</h3>
                        
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">{{ content.email_label }}</p>
                                    <a href="mailto:support@aqlcrypto.com" class="text-slate-900 dark:text-white font-semibold hover:text-emerald-500 transition break-all break-words text-sm sm:text-base">
                                        support@aqlcrypto.com
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                        
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/50">
                            {{ formatFlashMessage($page.props.flash.success) }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ content.form.name }}</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition" required>
                                    <span v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ content.form.email }}</label>
                                    <input v-model="form.email" type="email" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition" required>
                                    <span v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ content.form.subject }}</label>
                                <input v-model="form.subject" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition" required>
                                <span v-if="form.errors.subject" class="text-red-500 text-sm mt-1">{{ form.errors.subject }}</span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ content.form.message }}</label>
                                <textarea v-model="form.message" rows="5" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition resize-none" required></textarea>
                                <span v-if="form.errors.message" class="text-red-500 text-sm mt-1">{{ form.errors.message }}</span>
                            </div>

                            <button type="submit" :disabled="form.processing" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-4 rounded-xl transition duration-300 flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                {{ content.form.submit }}
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
    message: ''
});

const submit = () => {
    form.post(route('contact.submit'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

// فصل رسالة النجاح حسب اللغة (لأن الكنترولر يرسلها مقسومة بعلامة |)
const formatFlashMessage = (msg) => {
    if(!msg) return '';
    const parts = msg.split('|');
    return isAr.value ? parts[0].trim() : (parts[1] ? parts[1].trim() : parts[0].trim());
};

const translations = {
    ar: {
        title: "اتصل بنا",
        subtitle: "نحن هنا لمساعدتك والإجابة على كافة استفساراتك.",
        info_title: "معلومات التواصل",
        email_label: "البريد الإلكتروني للدعم:",
        form: {
            name: "الاسم الكامل",
            email: "البريد الإلكتروني",
            subject: "الموضوع",
            message: "الرسالة",
            submit: "إرسال الرسالة"
        }
    },
    en: {
        title: "Contact Us",
        subtitle: "We are here to help and answer any questions you might have.",
        info_title: "Contact Information",
        email_label: "Support Email:",
        form: {
            name: "Full Name",
            email: "Email Address",
            subject: "Subject",
            message: "Message",
            submit: "Send Message"
        }
    }
};

const content = computed(() => translations[locale.value]);
</script>