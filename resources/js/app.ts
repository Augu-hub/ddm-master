// resources/js/app.ts

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

// ✅ PrimeVue v3 (chemins v3)
import 'primevue/resources/themes/lara-light-blue/theme.css';
import 'primevue/resources/primevue.min.css';
import 'primeicons/primeicons.css';

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

// ✅ PrimeVue v3 - plugin + (quelques) composants globaux utiles
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import Tree from 'primevue/tree';
import TreeSelect from 'primevue/treeselect';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

// ---- Types Vite
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
  resolve: (name) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue')
    ),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) });

    app
      .use(plugin)
      .use(createPinia())
      .use(ZiggyVue)
      .use(createBootstrap())
      .use(VueApexCharts)
      // ✅ PrimeVue v3
      .use(PrimeVue, {
        ripple: true,
        inputStyle: 'outlined',
        locale: {
          // (optionnel) un peu de FR si tu veux
          startsWith: 'Commence par',
          contains: 'Contient',
          notContains: 'Ne contient pas',
          endsWith: 'Se termine par',
          equals: 'Égal à',
          notEquals: 'Différent de',
          noFilter: 'Aucun filtre',
        },
      })
      .use(ToastService);

    // Composants PrimeVue globaux (utiles partout)
    app.component('PToast', Toast);
    app.component('PDialog', Dialog);
    app.component('PTree', Tree);
    app.component('PTreeSelect', TreeSelect);
    app.component('PDropdown', Dropdown);
    app.component('PMultiSelect', MultiSelect);
    app.component('PInputText', InputText);
    app.component('PTextarea', Textarea);

    app.mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
