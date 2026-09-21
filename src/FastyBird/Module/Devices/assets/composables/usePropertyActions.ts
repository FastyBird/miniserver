import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElMessageBox } from 'element-plus';
import get from 'lodash.get';

import { injectStoresManager } from '@fastybird/miniserver-core';
import { useFlashMessage } from '@fastybird/miniserver-core';

import { channelPropertiesStoreKey, connectorPropertiesStoreKey, devicePropertiesStoreKey } from '../configuration';
import { ApplicationError } from '../errors';
import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty, UsePropertyActions } from '../types';

export const usePropertyActions = ({
	connector,
	device,
	channel,
}: {
	connector?: IConnector;
	device?: IDevice;
	channel?: IChannel;
}): UsePropertyActions => {
	const { t } = useI18n();

	const flashMessage = useFlashMessage();

	const storesManager = injectStoresManager();

	const connectorPropertiesStore = storesManager.getStore(connectorPropertiesStoreKey);
	const devicePropertiesStore = storesManager.getStore(devicePropertiesStoreKey);
	const channelPropertiesStore = storesManager.getStore(channelPropertiesStoreKey);

	const isConnectorProperty = computed<boolean>((): boolean => connector !== undefined);
	const isDeviceProperty = computed<boolean>((): boolean => device !== undefined && channel === undefined);
	const isChannelProperty = computed<boolean>((): boolean => device !== undefined && channel !== undefined);

	const remove = async (id: IConnectorProperty['id'] | IDeviceProperty['id'] | IChannelProperty['id']): Promise<void> => {
		let property = null;

		if (isConnectorProperty.value) {
			property = await connectorPropertiesStore.findById(id);
		} else if (isDeviceProperty.value) {
			property = await devicePropertiesStore.findById(id);
		} else if (isChannelProperty.value) {
			property = await channelPropertiesStore.findById(id);
		}

		if (property === null) {
			throw new ApplicationError("Something went wrong, property can't be loaded", null, { statusCode: 503, message: 'Something went wrong' });
		}

		ElMessageBox.confirm(
			t('devicesModule.messages.properties.confirmRemove', { property: property.title }),
			t('devicesModule.headings.properties.remove'),
			{
				confirmButtonText: t('devicesModule.buttons.yes.title'),
				cancelButtonText: t('devicesModule.buttons.no.title'),
				type: 'warning',
			}
		)
			.then(async (): Promise<void> => {
				try {
					if (isConnectorProperty.value) {
						await connectorPropertiesStore.remove({ id });
					} else if (isDeviceProperty.value) {
						await devicePropertiesStore.remove({ id });
					} else {
						await channelPropertiesStore.remove({ id });
					}

					flashMessage.success(
						t('devicesModule.messages.properties.removed', {
							property: property.title,
						})
					);
				} catch (e: any) {
					const errorMessage = t('devicesModule.messages.properties.notRemoved', {
						property: property.title,
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
					t('devicesModule.messages.properties.removeCanceled', {
						property: property.title,
					})
				);
			});
	};

	return {
		remove,
	};
};
