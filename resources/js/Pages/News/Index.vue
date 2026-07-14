<template>
  <DashboardLayout>
    
    <!-- 🌐 وسم الـ SEO الديناميكي لصفحة الأخبار الذكية -->
    <Head>
      <title>
        {{ locale === 'ar' ? 'آخر أخبار الكريبتو وتلخيصات الذكاء الاصطناعي | CryptoHub' : 'Latest Crypto News & AI Summaries | CryptoHub' }}
      </title>
      <meta 
        name="description" 
        :content="locale === 'ar' ? 'تغطية شاملة ومستمرة لأحداث سوق العملات الرقمية العالمية مع تلخيص ذكي فوري لأهم الأنباء.' : 'Comprehensive global coverage of crypto events with instant smart AI summarization.'" />
      <meta property="og:title" :content="locale === 'ar' ? 'أخبار الكريبتو الذكية | CryptoHub' : 'Crypto News Hub | CryptoHub'" />
    </Head>

    <!-- التحكم بمحاذاة الصفحة بالكامل بناءً على لغة النظام -->
    <div class="space-y-6" :class="locale === 'ar' ? 'text-right' : 'text-left'">
      
      <!-- عنوان القسم - متجاوب الترتيب مع الهواتف -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-[#1e293b] p-4 sm:p-6 rounded-2xl border border-slate-800 shadow-xl gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-white">
            {{ locale === 'ar' ? 'آخر أخبار الكريبتو' : 'Latest Crypto News' }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            {{ locale === 'ar' ? 'تغطية شاملة ومستمرة لأحداث السوق العالمية' : 'Comprehensive global coverage of crypto events' }}
          </p>
        </div>
        <span class="px-3 py-1 bg-purple-500/10 text-purple-400 border border-purple-500/20 text-[10px] sm:text-xs rounded-xl font-medium animate-pulse self-start sm:self-auto flex-shrink-0">
          {{ locale === 'ar' ? 'تحديث تلقائي' : 'Live Feed' }}
        </span>
      </div>

      <!-- شبكة عرض الأخبار (متجاوبة بالكامل من الهاتف إلى الشاشات الكبيرة) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="item in newsFeed" 
          :key="item.id" 
          class="bg-[#1e293b] border border-slate-800 rounded-2xl overflow-hidden shadow-lg hover:border-slate-700 transition-all flex flex-col group"
        >
          <!-- صورة الخبر المحمية الأبعاد -->
          <div class="h-44 sm:h-48 overflow-hidden relative bg-slate-900 flex-shrink-0">
            <img 
              :src="item.image_url" 
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" 
              alt="news image" 
            />
            <span class="absolute bottom-3 right-3 bg-[#0f172a]/80 text-slate-300 text-[10px] px-2 py-1 rounded font-medium backdrop-blur-sm">
              {{ item.source }}
            </span>
          </div>

          <!-- تفاصيل ومحتوى الخبر متناسق المحاذة -->
          <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-4">
            <div class="space-y-2 min-w-0">
              <span class="text-[10px] sm:text-[11px] text-slate-500 font-mono block">{{ item.date }}</span>
              <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-emerald-400 transition-colors leading-snug break-words line-clamp-2">
                {{ item.title }}
              </h3>
              <p class="text-xs text-slate-400 leading-relaxed line-clamp-3 break-words">
                {{ item.content }}
              </p>
            </div>

            <!-- زر الانتقال الفوري أو القراءة الكاملة -->
            <div class="pt-3 border-t border-slate-800/60 flex justify-between items-center gap-2">
              <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-400 font-mono">#Market</span>
              <button class="text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors flex items-center gap-1 cursor-pointer flex-shrink-0">
                <span>{{ locale === 'ar' ? 'اقرأ المزيد ←' : 'Read More →' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </DashboardLayout>
</template>

<script setup>
// إضافة نقطتين إضافيتين للصعود إلى المجلد الأب الصحيح
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { usePage, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
  newsFeed: {
    type: Array,
    required: true
  }
});

// استخراج مفتاح اللغة النشطة من لارافل لتوحيد أداء السيو والتوافق البصري
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
</script>