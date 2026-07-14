import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'; // 🟢 1. استيراد بلجن تيلويند الجديد بالملّي

export default defineConfig({
    plugins: [
        tailwindcss(), // 🟢 2. تفعيل البلجن هنا في أول المصفوفة ليقوم بمعالجة الـ CSS
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        vue(),
    ],
});