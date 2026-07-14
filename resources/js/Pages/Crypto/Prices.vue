<template>
  <DashboardLayout>
    
    <Head>
      <title>
        {{ locale === 'ar' ? 'سوق العملات الرقمية بالكامل | CryptoHub' : 'All Crypto Markets | CryptoHub' }}
      </title>
      <meta 
        name="description" 
        :content="locale === 'ar' ? 'استعراض شامل وقائمة حية لأفضل 20 عملة مشفرة في الأسواق العالمية مع تحديث فوري للأسعار ونسب التغير.' : 'Comprehensive list and live feed tracking the top 20 crypto assets globally with instant price updates.'" 
      />
      <meta property="og:title" :content="locale === 'ar' ? 'سوق العملات الرقمية بالكامل | CryptoHub' : 'All Crypto Markets | CryptoHub'" />
    </Head>

    <div class="space-y-6" :class="locale === 'ar' ? 'text-right' : 'text-left'">
      
      <div class="bg-[#1e293b] p-6 rounded-2xl border border-slate-800 shadow-xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-white">
            {{ locale === 'ar' ? 'سوق العملات الرقمية بالكامل' : 'All Crypto Markets' }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">
            {{ locale === 'ar' ? 'قائمة شاملة تستعرض أفضل 20 عملة مشفرة في السوق حالياً' : 'Comprehensive list tracking top 20 crypto assets' }}
          </p>
        </div>
      </div>

      <div class="bg-[#1e293b] border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto touch-pan-x">
          <table class="w-full border-collapse min-w-[600px]" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
            <thead>
              <tr class="border-b border-slate-800 bg-[#0f172a]/40 text-slate-400 text-xs font-semibold whitespace-nowrap">
                <th class="p-4" :class="locale === 'ar' ? 'text-right' : 'text-left'">{{ locale === 'ar' ? 'العملة' : 'Asset' }}</th>
                <th class="p-4" :class="locale === 'ar' ? 'text-left' : 'text-right'">{{ locale === 'ar' ? 'السعر الحالي' : 'Price' }}</th>
                <th class="p-4" :class="locale === 'ar' ? 'text-left' : 'text-right'">{{ locale === 'ar' ? 'تغير (24س)' : 'Change (24h)' }}</th>
                <th class="p-4 text-center">{{ locale === 'ar' ? 'الإجراءات' : 'Actions' }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-sm whitespace-nowrap">
              <tr v-for="crypto in cryptos" :key="crypto.id" class="hover:bg-[#0f172a]/20 transition-colors">
                
                <td class="p-4 flex items-center gap-3">
                  <img :src="crypto.image_url" class="w-7 h-7 rounded-full bg-slate-800 object-contain flex-shrink-0" :alt="crypto.name" />
                  <div class="min-w-0">
                    <span class="font-bold text-white block leading-none mb-1 truncate">{{ crypto.name }}</span>
                    <span class="text-[10px] text-slate-500 uppercase font-mono tracking-wider block">{{ crypto.symbol }}</span>
                  </div>
                </td>
                
                <td class="p-4 font-mono font-medium text-slate-200" :class="locale === 'ar' ? 'text-left' : 'text-right'">
                  ${{ Number(crypto.current_price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                </td>
                
                <td class="p-4 font-mono font-bold" :class="[crypto.change_24h >= 0 ? 'text-emerald-400' : 'text-rose-400', locale === 'ar' ? 'text-left' : 'text-right']">
                  {{ crypto.change_24h >= 0 ? '▲' : '▼' }} {{ Math.abs(crypto.change_24h) }}%
                </td>
                
                <td class="p-4 text-center">
                  <button 
                    @click="router.get(`/crypto/${crypto.symbol.toLowerCase()}`)" 
                    class="px-4 py-1.5 text-xs font-medium rounded-lg bg-[#0f172a] text-slate-300 border border-slate-800 hover:bg-emerald-500 hover:text-slate-900 hover:border-emerald-500 transition-all cursor-pointer shadow-sm"
                  >
                    {{ locale === 'ar' ? 'تحليل' : 'Analyze' }}
                  </button>
                </td>

              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '../../layouts/DashboardLayout.vue';
import { router, usePage, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
  cryptos: Array
});

// استخراج مفتاح اللغة النشطة من لارافل لتوحيد أداء السيو والتوافق البصري
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
</script>