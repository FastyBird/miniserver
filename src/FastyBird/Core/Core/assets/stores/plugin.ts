import { App } from 'vue';

import { provideStoresManager } from './injection';
import { StoresManager } from './manager';

export default {
	install: async (app: App): Promise<void> => {
		const manager = new StoresManager();

		app.config.globalProperties['storesManager'] = manager;

		provideStoresManager(app, manager);
	},
};
