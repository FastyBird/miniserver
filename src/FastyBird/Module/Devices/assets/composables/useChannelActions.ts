import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';

import { ElMessageBox } from 'element-plus';
import get from 'lodash.get';

import { injectStoresManager } from '@fastybird/miniserver-core';
import { useFlashMessage } from '@fastybird/miniserver-core';

import { channelsStoreKey } from '../configuration';
import { ApplicationError } from '../errors';
import { IChannel, UseChannelActions } from '../types';

import { useRoutesNames } from './useRoutesNames';

export const useChannelActions = (): UseChannelActions => {
	const router = useRouter();
	const route = useRoute();

	const { t } = useI18n();

	const routeNames = useRoutesNames();
	const flashMessage = useFlashMessage();

	const storesManager = injectStoresManager();

	const channelsStore = storesManager.getStore(channelsStoreKey);

	const remove = async (id: IChannel['id']): Promise<void> => {
		const channel = channelsStore.findById(id);

		if (channel === null) {
			throw new ApplicationError("Something went wrong, channel can't be loaded", null, { statusCode: 503, message: 'Something went wrong' });
		}

		ElMessageBox.confirm(
			t('devicesModule.messages.channels.confirmRemove', { channel: channel.title }),
			t('devicesModule.headings.channels.remove'),
			{
				confirmButtonText: t('devicesModule.buttons.yes.title'),
				cancelButtonText: t('devicesModule.buttons.no.title'),
				type: 'warning',
			}
		)
			.then(async (): Promise<void> => {
				// Device channel detail page => go to device detail page
				if (
					route.matched.find((matched) => {
						return matched.name === routeNames.channelDetail;
					}) !== undefined &&
					route.params.channelId === id
				) {
					await router.push({
						name: routeNames.deviceDetail,
						params: {
							id: route.params.id,
						},
					});
					// Device channel settings page => go to device settings page
				} else if (
					route.matched.find((matched) => {
						return matched.name === routeNames.channelSettings;
					}) !== undefined &&
					route.params.channelId === id
				) {
					await router.push({
						name: routeNames.deviceSettings,
						params: {
							id: route.params.id,
						},
					});
					// Connector detail => device channel detail page => go to connector device detail page
				} else if (
					route.matched.find((matched) => {
						return matched.name === routeNames.connectorDetailDeviceDetailChannelDetail;
					}) !== undefined &&
					route.params.channelId === id
				) {
					await router.push({
						name: routeNames.connectorDetailDeviceDetail,
						params: {
							plugin: route.params.plugin,
							id: route.params.id,
							deviceId: route.params.deviceId,
						},
					});
					// Connector settings => device channel settings page => go to connector device settings page
				} else if (
					route.matched.find((matched) => {
						return matched.name === routeNames.connectorDetailDeviceDetailChannelSettings;
					}) !== undefined &&
					route.params.channelId === id
				) {
					await router.push({
						name: routeNames.connectorDetailDeviceSettings,
						params: {
							plugin: route.params.plugin,
							id: route.params.id,
							deviceId: route.params.deviceId,
						},
					});
				}

				try {
					await channelsStore.remove({ id: channel.id });

					flashMessage.success(
						t('devicesModule.messages.channels.removed', {
							channel: channel.title,
						})
					);
				} catch (e: any) {
					const errorMessage = t('devicesModule.messages.channels.notRemoved', {
						channel: channel.title,
					});

					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(get(e, 'exception', null), errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}
				}
			})
			.catch(() => {
				flashMessage.info(
					t('devicesModule.messages.channels.removeCanceled', {
						channel: channel.title,
					})
				);
			});
	};

	return {
		remove,
	};
};
