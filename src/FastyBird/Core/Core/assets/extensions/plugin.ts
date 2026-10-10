import { App } from 'vue';

import { IExtensionsOptions } from '../types';

export default {
	install: async (app: App, options: IExtensionsOptions<any>): Promise<void> => {
		for (const extension of options.extensions) {
			if (typeof extension.module.install === 'function') {
				app.use(extension.module, options.options);
			} else {
				console.error(`The module for ${extension.name} does not export a valid Vue plugin.`);
			}
		}
	},
};
