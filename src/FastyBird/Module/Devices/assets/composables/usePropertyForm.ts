import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

import capitalize from 'lodash.capitalize';
import get from 'lodash.get';

import { injectStoresManager } from '@fastybird/miniserver-core';
import { useFlashMessage } from '@fastybird/miniserver-core';

import { channelPropertiesStoreKey, connectorPropertiesStoreKey, devicePropertiesStoreKey } from '../configuration';
import {
	FormResultType,
	FormResultTypes,
	IChannel,
	IChannelProperty,
	IConnector,
	IConnectorProperty,
	IDevice,
	IDeviceProperty,
	IPropertyForm,
	UsePropertyForm,
} from '../types';

export const usePropertyForm = ({
	connector,
	device,
	channel,
	property,
}: {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
	property: IConnectorProperty | IDeviceProperty | IChannelProperty;
}): UsePropertyForm => {
	const storesManager = injectStoresManager();

	const connectorPropertiesStore = storesManager.getStore(connectorPropertiesStoreKey);
	const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);
	const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	const { t } = useI18n();

	const flashMessage = useFlashMessage();

	const isConnectorProperty = computed<boolean>((): boolean => connector !== undefined);
	const isDeviceProperty = computed<boolean>((): boolean => device !== undefined && channel === undefined);
	const isChannelProperty = computed<boolean>((): boolean => device !== undefined && channel !== undefined);

	const formResult = ref<FormResultType>(FormResultTypes.NONE);

	let timer: number;

	const submit = async (model: IPropertyForm): Promise<'added' | 'saved'> => {
		formResult.value = FormResultTypes.WORKING;

		const isDraft = property.draft;
		const title = model.name ?? capitalize(model.identifier);

		const errorMessage = property.draft
			? t('devicesModule.messages.properties.notCreated', { property: title })
			: t('devicesModule.messages.properties.notEdited', { property: title });

		try {
			if (property.draft) {
				if (isConnectorProperty.value) {
					await connectorPropertiesStore.edit({
						id: property.id,
						data: model,
					});

					if (!connector?.draft && property.draft) {
						await connectorPropertiesStore.save({ id: property.id });
					}
				} else if (isDeviceProperty.value) {
					await devicePropertiesStore.edit({
						id: property.id,
						data: model,
					});

					if (!device?.draft && property.draft) {
						await devicePropertiesStore.save({ id: property.id });
					}
				} else if (isChannelProperty.value) {
					await channelPropertiesStore.edit({
						id: property.id,
						data: model,
					});

					if (!channel?.draft && property.draft) {
						await channelPropertiesStore.save({ id: property.id });
					}
				}
			} else {
				if (isChannelProperty.value) {
					await channelPropertiesStore.edit({
						id: property.id,
						data: model,
					});
				} else if (isDeviceProperty.value) {
					await devicePropertiesStore.edit({
						id: property.id,
						data: model,
					});
				} else if (isConnectorProperty.value) {
					await connectorPropertiesStore.edit({
						id: property.id,
						data: model,
					});
				}
			}

			formResult.value = FormResultTypes.OK;

			const handleTimeout = (result: 'added' | 'saved'): 'added' | 'saved' => {
				clear();

				return result;
			};

			if (
				(isConnectorProperty.value && connector?.draft) ||
				(isDeviceProperty.value && device?.draft) ||
				(isChannelProperty.value && channel?.draft)
			) {
				flashMessage.success(
					t('devicesModule.messages.properties.added', {
						property: title,
					})
				);

				return await new Promise<'added' | 'saved'>((resolve) => {
					timer = window.setTimeout(() => {
						resolve(handleTimeout('added'));
					}, 250);
				});
			}

			if (isDraft) {
				flashMessage.success(
					t('devicesModule.messages.properties.created', {
						property: title,
					})
				);

				return await new Promise<'added' | 'saved'>((resolve) => {
					timer = window.setTimeout(() => {
						resolve(handleTimeout('saved'));
					}, 1000);
				});
			}

			flashMessage.success(
				t('devicesModule.messages.properties.edited', {
					property: title,
				})
			);

			return await new Promise<'added' | 'saved'>((resolve) => {
				timer = window.setTimeout(() => {
					resolve(handleTimeout('saved'));
				}, 250);
			});
		} catch (e: any) {
			formResult.value = FormResultTypes.ERROR;

			timer = window.setTimeout(clear, 2000);

			if (get(e, 'exception', null) !== null) {
				flashMessage.exception(get(e, 'exception', null), errorMessage);
			} else {
				flashMessage.error(errorMessage);
			}

			throw e;
		}
	};

	const clear = (): void => {
		window.clearTimeout(timer);

		formResult.value = FormResultTypes.NONE;
	};

	return {
		submit,
		clear,
		formResult,
	};
};
