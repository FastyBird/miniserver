import { App } from 'vue';

import defaultsDeep from 'lodash.defaultsdeep';
import get from 'lodash.get';
import 'virtual:uno.css';

import { IExtensionOptions, ModulePrefix, wampClient } from '@fastybird/miniserver-core';

import { useFlashMessage } from './composables';
import { metaKey } from './configuration';
import locales, { MessageSchema } from './locales';
import { useDashboards, useGroups, useTabs, useWidgetDataSources, useWidgetDisplay, useWidgets } from './models';
import { registerDashboardsStore } from './models/dashboards';
import { registerGroupsStore } from './models/groups';
import { registerTabsStore } from './models/tabs';
import { registerWidgetsStore } from './models/widgets';
import { registerWidgetDataSourcesStore } from './models/widgets-data-sources';
import { registerWidgetDisplayStore } from './models/widgets-display';
import moduleRouter from './router';

export default {
	install: (app: App, options: IExtensionOptions<{ 'en-US': MessageSchema }>): void => {
		moduleRouter(options.router);

		app.provide(metaKey, options.meta);

		wampClient.subscribe(`/${ModulePrefix.UI}/v1/exchange`, onWsMessage);

		for (const [locale, translations] of Object.entries(locales)) {
			const currentMessages = options.i18n.global.getLocaleMessage(locale);
			const mergedMessages = defaultsDeep(currentMessages, { uiModule: translations });

			options.i18n.global.setLocaleMessage(locale, mergedMessages);
		}

		registerDashboardsStore(options.store);
		registerTabsStore(options.store);
		registerGroupsStore(options.store);
		registerWidgetsStore(options.store);
		registerWidgetDataSourcesStore(options.store);
		registerWidgetDisplayStore(options.store);
	},
};

const onWsMessage = (data: string): void => {
	const flashMessage = useFlashMessage();

	const body = JSON.parse(data);

	const stores = [useDashboards(), useTabs(), useGroups(), useWidgets(), useWidgetDataSources(), useWidgetDisplay()];

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
