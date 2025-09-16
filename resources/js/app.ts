import '@vueup/vue-quill/dist/vue-quill.bubble.css';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import 'apexcharts/dist/apexcharts.css';
import 'choices.js/src/styles/choices.scss';
import 'dropzone/src/dropzone.scss';
import 'flatpickr/dist/flatpickr.css';
import 'gridjs/dist/theme/mermaid.min.css';
import 'jsvectormap/dist/jsvectormap.min.css';
import 'leaflet/dist/leaflet.css';
import 'nouislider/dist/nouislider.css';
import 'simplebar';
import 'swiper/css';
import 'swiper/css/effect-creative';
import 'swiper/css/effect-fade';
import 'swiper/css/effect-flip';
import 'swiper/css/free-mode';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/scrollbar';
import 'swiper/css/thumbs';
import 'vue3-toastify/dist/index.css';

import '@/scss/app.scss';
import '@/scss/icons.scss';
import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createBootstrap } from 'bootstrap-vue-next';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import { ZiggyVue } from 'ziggy-js';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME;

createInertiaApp({
    title: (title) => (title ? `${title} | ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue)
            .use(createBootstrap())
            .use(VueApexCharts)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
