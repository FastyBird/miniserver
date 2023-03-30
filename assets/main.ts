import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createMetaManager, plugin as metaPlugin } from 'vue-meta';
import toast from 'vue-toastification';
import get from 'lodash/get';
import { createWsExchangeClient } from '@fastybird/ws-exchange-plugin';

import { version } from './../package.json';

import App from '@/App.vue';
import i18n from '@/locales';
import router from '@/router';

import { backendPlugin, eventBusPlugin, eventBusInjectionKey, fontAwesomePlugin } from '@/plugins';

import { createAccountsModule, IAccountsModuleOptions } from '@fastybird/accounts-module';
import { createDevicesModule, IDevicesModuleOptions } from '@fastybird/devices-module';

import 'normalize.css/normalize.css';
import 'nprogress/nprogress.css';
import 'vue-toastification/dist/index.css';
import '@fastybird/web-ui-library/dist/web-ui-library.css';
import '@fastybird/accounts-module/dist/accounts-module.css';
import '@fastybird/devices-module/dist/devices-module.css';

const app = createApp(App);

app.use(i18n);
app.use(createMetaManager());
app.use(metaPlugin);
app.use(createPinia());
app.use(toast);
app.use(createWsExchangeClient(), {
	wsuri: 'ws://localhost:3000/ws-exchange',
	debug: true,
});

// Register app plugins
app.use(backendPlugin, {
	apiPrefix: get(import.meta.env, 'FB_APP_PARAMETER__API_PREFIX', '/api'),
	apiTarget: get(import.meta.env, 'FB_APP_PARAMETER__API_TARGET', null),
	apiKey: get(import.meta.env, 'FB_APP_PARAMETER__API_KEY', null),
	apiPrefixedModules: `${get(import.meta.env, 'FB_APP_PARAMETER__API_PREFIXED_MODULES', true)}`.toLowerCase() === 'true',
});
app.use(eventBusPlugin, {});
app.use(fontAwesomePlugin);

// Register app modules
app.use(createAccountsModule(), {
	router,
	meta: {
		author: 'FastyBird s.r.o.',
		website: 'https://www.fastybird.com',
		version: version,
	},
	configuration: {
		injectionKeys: {
			eventBusInjectionKey,
		},
	},
} as IAccountsModuleOptions);
app.use(createDevicesModule(), {
	router,
	meta: {},
	configuration: {},
} as IDevicesModuleOptions);

app.use(router);

router.isReady().then(() => app.mount('#app'));
