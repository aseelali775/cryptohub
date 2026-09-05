import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    return pages[`./Pages/${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
});

// تتبع التنقلات بين الصفحات وإرسالها إلى Google Analytics
router.on('navigate', (event) => {
    if (typeof gtag !== 'undefined') {
        gtag('config', 'G-WKHYN6DQJT', {
            page_path: event.detail.page.url
        });
    }
});