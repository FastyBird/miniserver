import { App } from 'vue';

import moduleRouter from './router';
import { ITriggersModuleOptions, InstallFunction } from './types';
import { configurationKey, metaKey } from './configuration';

export function createTriggersModule(): InstallFunction {
	const plugin: InstallFunction = {
		install(app: App, options: ITriggersModuleOptions): void {
			if (this.installed) {
				return;
			}
			this.installed = true;

			if (typeof options.router === 'undefined') {
				throw new Error('Router instance is missing in module configuration');
			}

			moduleRouter(options.router);

			app.provide(metaKey, options.meta);
			app.provide(configurationKey, options.configuration);
		},
	};

	return plugin;
}

export * from './configuration';
export * from './composables';
export * from './layouts';
export * from './router';

export * from './types';
