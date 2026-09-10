import { App } from 'vue';

import defaultsDeep from 'lodash.defaultsdeep';
import get from 'lodash.get';
import 'virtual:uno.css';

import { ModulePrefix } from '@fastybird/metadata-library';
import { wampClient } from '@fastybird/vue-wamp-v1';

import { useFlashMessage } from './composables';
import { configurationKey, metaKey } from './configuration';
import locales from './locales';
import { useDashboards, useGroups, useWidgetDataSources, useWidgetDisplay, useWidgets } from './models';
import { registerDashboardsStore } from './models/dashboards';
import { registerGroupsStore } from './models/groups';
import { registerTabsStore } from './models/tabs';
import { registerWidgetsStore } from './models/widgets';
import { registerWidgetDataSourcesStore } from './models/widgets-data-sources';
import { registerWidgetDisplayStore } from './models/widgets-display';
import moduleRouter from './router';
import { IUiModuleOptions, InstallFunction } from './types';

export default function createDevicesModule(): InstallFunction {
	return {
		install(app: App, options: IUiModuleOptions): void {
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

			wampClient.subscribe(`/${ModulePrefix.UI}/v1/exchange`, onWsMessage);

			for (const [locale, translations] of Object.entries(locales)) {
				const currentMessages = options.i18n?.global.getLocaleMessage(locale);
				const mergedMessages = defaultsDeep(currentMessages, translations);

				options.i18n?.global.setLocaleMessage(locale, mergedMessages);
			}

			registerDashboardsStore(options.store);
			registerTabsStore(options.store);
			registerGroupsStore(options.store);
			registerWidgetsStore(options.store);
			registerWidgetDataSourcesStore(options.store);
			registerWidgetDisplayStore(options.store);
		},
	};
}

const onWsMessage = (data: string): void => {
	const flashMessage = useFlashMessage();

	const body = JSON.parse(data);

	const stores = [useDashboards(), useGroups(), useWidgets(), useWidgetDataSources(), useWidgetDisplay()];

	if (
		Object.prototype.hasOwnProperty.call(body, 'routing_key') &&
		Object.prototype.hasOwnProperty.call(body, 'source') &&
		Object.prototype.hasOwnProperty.call(body, 'data')
	) {
		stores.forEach((store) => {
			if (Object.prototype.hasOwnProperty.call(store, 'socketData')) {
				store
					.socketData({
						source: get(body, 'source'),
						routingKey: get(body, 'routing_key'),
						data: JSON.stringify(get(body, 'data')),
					})
					.catch((e): void => {
						if (get(e, 'exception', null) !== null) {
							flashMessage.exception(get(e, 'exception', null), 'Error parsing exchange data');
						} else {
							flashMessage.error('Error parsing exchange data');
						}
					});
			}
		});
	}
};

export * from './configuration';
export * from './composables';
export * from './models';
export * from './router';

export * from './types';
