// resources/js/utils/url.js

// قراءة رابط المنصة من متغيرات البيئة الفايت
const BASE = import.meta.env.VITE_APP_URL || '';

export function url(path = '') {
    // بناء الرابط بشكل ديناميكي ونظيف يمنع الـ Hardcoding
    return BASE + (path.startsWith('/') ? path : '/' + path);
}