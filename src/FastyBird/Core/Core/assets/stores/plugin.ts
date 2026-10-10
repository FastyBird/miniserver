import { App } from 'vue';

import { StoresManager, provideStoresManager } from '../stores';

export default {
	install: async (app: App): Promise<void> => {
		const manager = new StoresManager();

		app.config.globalProperties['storesManager'] = manager;

		provideStoresManager(app, manager);
	},
};
