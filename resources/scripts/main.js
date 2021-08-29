import 'dynamic-import-polyfill';

// import titleMixin from "@/Mixins/titleMixin";
import mitt from 'mitt';
import { createApp, h } from 'vue';
import { App as InertiaApp, plugin as InertiaPlugin } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';

import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import '../sass/app.scss';

InertiaProgress.init();

const emitter = mitt();
const app = document.getElementById('app');

const pages = import.meta.glob('./Pages/**/*.vue');

const created = createApp({
    render: () =>
        h(InertiaApp, {
            initialPage: JSON.parse(app.dataset.page),
            resolveComponent: name => {
                const importPage = pages[`./Pages/${name}.vue`];
                if (!importPage) {
                    throw new Error(`Unknown page ${name}. Is it located under Pages with a .vue extension?`);
                }
                return importPage().then(module => module.default)
            }
        }),
})
    .mixin({ methods: { route } })
    // .mixin(titleMixin)
    .use(InertiaPlugin);

created.config.globalProperties.emitter = emitter
created.mount(app);
