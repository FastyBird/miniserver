import { createApp } from 'vue';
import { createMetaManager, plugin as metaPlugin } from 'vue-meta';

import { createPinia } from 'pinia';

import get from 'lodash.get';
import 'nprogress/nprogress.css';
import 'virtual:uno.css';

import { IExtensionsOptions, backendPlugin, eventBusPlugin, extensionsPlugin, storesPlugin } from '@fastybird/tools';
import { createWampV1Client } from '@fastybird/vue-wamp-v1';
import '@fastybird/web-ui-theme-chalk/src/index.scss';

import { extensions } from '@config/extensions';

import App from './App.vue';
import i18n from './locales';
import router from './router';
import './styles/base.scss';

const pinia = createPinia();

const app = createApp(App);

app.use(i18n);

app.use(createMetaManager());

app.use(metaPlugin);

app.use(pinia);

app.use(createWampV1Client(), {
	host: 'ws://localhost:3000/ws-exchange',
	debug: true,
});

app.use(eventBusPlugin);

app.use(storesPlugin);

app.use(backendPlugin, {
	apiPrefix: get(import.meta.env, 'FB_APP_PARAMETER__API_PREFIX', '/api'),
	apiTarget: get(import.meta.env, 'FB_APP_PARAMETER__API_TARGET', null),
	apiKey: get(import.meta.env, 'FB_APP_PARAMETER__API_KEY', null),
	apiPrefixedModules: `${get(import.meta.env, 'FB_APP_PARAMETER__API_PREFIXED_MODULES', true)}`.toLowerCase() === 'true',
});

app.use(extensionsPlugin, {
	extensions,
	options: {
		router,
		meta: {
			author: 'FastyBird s.r.o.',
			website: 'https://www.fastybird.com',
			version: __APP_VERSION__,
		},
		store: pinia,
		i18n,
	},
} as IExtensionsOptions<any>);

app.use(router);

router.isReady().then(() => app.mount('#app'));
