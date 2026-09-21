import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';

import { ElMessageBox } from 'element-plus';
import get from 'lodash.get';

import { injectStoresManager } from '@fastybird/miniserver-core';
import { useFlashMessage } from '@fastybird/miniserver-core';

import { devicesStoreKey } from '../configuration';
import { ApplicationError } from '../errors';
import { IDevice, UseDeviceActions } from '../types';

import { useRoutesNames } from './useRoutesNames';

export const useDeviceActions = (): UseDeviceActions => {
	const router = useRouter();
	const route = useRoute();

	const { t } = useI18n();

	const routeNames = useRoutesNames();
	const flashMessage = useFlashMessage();

	const storesManager = injectStoresManager();

	const devicesStore = storesManager.getStore(devicesStoreKey);

	const remove = async (id: IDevice['id']): Promise<void> => {
		const device = devicesStore.findById(id);

		if (device === null) {
			throw new ApplicationError("Something went wrong, device can't be loaded", null, { statusCode: 503, message: 'Something went wrong' });
		}

		ElMessageBox.confirm(t('devicesModule.messages.devices.confirmRemove', { device: device.title }), t('devicesModule.headings.devices.remove'), {
			confirmButtonText: t('devicesModule.buttons.yes.title'),
			cancelButtonText: t('devicesModule.buttons.no.title'),
			type: 'warning',
		})
			.then(async (): Promise<void> => {
				if (
					route.matched.find((matched) => {
						return matched.name === routeNames.deviceDetail;
					}) !== undefined &&
					route.params.id === id
				) {
					await router.push({ name: routeNames.devices });
				} else if (
					route.matched.find((matched) => {
						return matched.name === routeNames.connectorDetailDeviceDetail;
					}) !== undefined &&
					route.params.deviceId === id
				) {
					await router.push({
						name: routeNames.connectorDetail,
						params: {
							plugin: route.params.plugin,
							id: route.params.id,
						},
					});
				} else if (
					route.matched.find((matched) => {
						return matched.name === routeNames.connectorDetailDeviceSettings;
					}) !== undefined &&
					route.params.deviceId === id
				) {
					await router.push({
						name: routeNames.connectorSettings,
						params: {
							plugin: route.params.plugin,
							id: route.params.id,
						},
					});
				}

				try {
					await devicesStore.remove({ id: device.id });

					flashMessage.success(
						t('devicesModule.messages.devices.removed', {
							device: device.title,
						})
					);
				} catch (e: any) {
					const errorMessage = t('devicesModule.messages.devices.notRemoved', {
						device: device.title,
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
					t('devicesModule.messages.devices.removeCanceled', {
						device: device.title,
					})
				);
			});
	};

	return {
		remove,
	};
};
