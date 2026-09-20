import { App } from 'vue';

import defaultsDeep from 'lodash.defaultsdeep';
import get from 'lodash.get';
import 'virtual:uno.css';

import { IExtensionOptions, ModulePrefix, injectStoresManager, useFlashMessage, wampClient } from '@fastybird/miniserver-core';

import {
	channelControlsStoreKey,
	channelPropertiesStoreKey,
	channelsStoreKey,
	connectorControlsStoreKey,
	connectorPropertiesStoreKey,
	connectorsStoreKey,
	deviceControlsStoreKey,
	devicePropertiesStoreKey,
	devicesStoreKey,
	metaKey,
} from './configuration';
import locales, { MessageSchema } from './locales';
import {
	registerChannelsControlsStore,
	registerChannelsPropertiesStore,
	registerChannelsStore,
	registerConnectorsControlsStore,
	registerConnectorsPropertiesStore,
	registerConnectorsStore,
	registerDevicesControlsStore,
	registerDevicesPropertiesStore,
	registerDevicesStore,
} from './models';
import moduleRouter from './router';

export default {
	install: async (app: App, options: IExtensionOptions<{ 'en-US': MessageSchema }>): Promise<void> => {
		moduleRouter(options.router);

		app.provide(metaKey, options.meta);

		wampClient.subscribe(`/${ModulePrefix.DEVICES}/v1/exchange`, (data: string): void => {
			onWsMessage(app, data);
		});

		for (const [locale, translations] of Object.entries(locales)) {
			const currentMessages = options.i18n.global.getLocaleMessage(locale);
			const mergedMessages = defaultsDeep(currentMessages, { devicesModule: translations });

			options.i18n.global.setLocaleMessage(locale, mergedMessages);
		}

		const storesManager = injectStoresManager(app);

		const channelsStore = registerChannelsStore(options.store);
		const channelControlsStore = registerChannelsControlsStore(options.store);
		const channelPropertiesStore = registerChannelsPropertiesStore(options.store);
		const connectorsStore = registerConnectorsStore(options.store);
		const connectorControlsStore = registerConnectorsControlsStore(options.store);
		const connectorPropertiesStore = registerConnectorsPropertiesStore(options.store);
		const devicesStore = registerDevicesStore(options.store);
		const deviceControlsStore = registerDevicesControlsStore(options.store);
		const devicePropertiesStore = registerDevicesPropertiesStore(options.store);

		app.provide(channelsStoreKey, channelsStore);
		storesManager.addStore(channelsStoreKey, channelsStore);
		app.provide(channelControlsStoreKey, channelControlsStore);
		storesManager.addStore(channelControlsStoreKey, channelControlsStore);
		app.provide(channelPropertiesStoreKey, channelPropertiesStore);
		storesManager.addStore(channelPropertiesStoreKey, channelPropertiesStore);
		app.provide(connectorsStoreKey, connectorsStore);
		storesManager.addStore(connectorsStoreKey, connectorsStore);
		app.provide(connectorControlsStoreKey, connectorControlsStore);
		storesManager.addStore(connectorControlsStoreKey, connectorControlsStore);
		app.provide(connectorPropertiesStoreKey, connectorPropertiesStore);
		storesManager.addStore(connectorPropertiesStoreKey, connectorPropertiesStore);
		app.provide(devicesStoreKey, devicesStore);
		storesManager.addStore(devicesStoreKey, devicesStore);
		app.provide(deviceControlsStoreKey, deviceControlsStore);
		storesManager.addStore(deviceControlsStoreKey, deviceControlsStore);
		app.provide(devicePropertiesStoreKey, devicePropertiesStore);
		storesManager.addStore(devicePropertiesStoreKey, devicePropertiesStore);
	},
};

const onWsMessage = (app: App, data: string): void => {
	const flashMessage = useFlashMessage();

	const body = JSON.parse(data);

	const storesManager = injectStoresManager(app);

	const stores = [
		storesManager.getStore(channelsStoreKey),
		storesManager.getStore(channelControlsStoreKey),
		storesManager.getStore(channelPropertiesStoreKey),
		storesManager.getStore(connectorsStoreKey),
		storesManager.getStore(connectorControlsStoreKey),
		storesManager.getStore(connectorPropertiesStoreKey),
		storesManager.getStore(devicesStoreKey),
		storesManager.getStore(deviceControlsStoreKey),
		storesManager.getStore(devicePropertiesStoreKey),
	];

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
					.catch((e: any): void => {
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
export * from './components';
export * from './composables';

export * from './types';
