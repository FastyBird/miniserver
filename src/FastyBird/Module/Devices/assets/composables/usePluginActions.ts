import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';

import { ElMessageBox } from 'element-plus';
import get from 'lodash.get';

import { injectStoresManager } from '@fastybird/miniserver-core';
import { useFlashMessage } from '@fastybird/miniserver-core';

import { connectorPlugins, connectorsStoreKey } from '../configuration';
import { ApplicationError } from '../errors';
import { IConnector, IConnectorPlugin, UsePluginActions } from '../types';

import { useRoutesNames } from './useRoutesNames';

export const usePluginActions = (): UsePluginActions => {
	const router = useRouter();
	const route = useRoute();

	const { t } = useI18n();

	const routeNames = useRoutesNames();
	const flashMessage = useFlashMessage();

	const storesManager = injectStoresManager();

	const connectorsStore = storesManager.getStore(connectorsStoreKey);

	const remove = async (type: IConnectorPlugin['type']): Promise<void> => {
		const plugin: IConnectorPlugin | null = connectorPlugins.find((plugin) => plugin.type === type) ?? null;

		if (plugin === null) {
			throw new ApplicationError("Something went wrong, plugin can't be loaded", null, { statusCode: 503, message: 'Something went wrong' });
		}

		ElMessageBox.confirm(t('devicesModule.messages.plugins.confirmRemove', { plugin: plugin.name }), t('devicesModule.headings.plugins.remove'), {
			confirmButtonText: t('devicesModule.buttons.yes.title'),
			cancelButtonText: t('devicesModule.buttons.no.title'),
			type: 'warning',
		})
			.then(async (): Promise<void> => {
				if (
					route.matched.find((matched) => {
						return matched.name === routeNames.pluginDetail;
					}) !== undefined &&
					route.params.plugin === type
				) {
					await router.push({ name: routeNames.plugins });
				}

				const connectors: IConnector[] = connectorsStore.findAll().filter((connector: IConnector): boolean => {
					return plugin.type === connector.type.type;
				});

				try {
					for (const connector of connectors) {
						await connectorsStore.remove({ id: connector.id });
					}

					flashMessage.success(
						t('devicesModule.messages.plugins.removed', {
							plugin: plugin.name,
						})
					);
				} catch (e: any) {
					const errorMessage = t('devicesModule.messages.plugins.notRemoved', {
						plugin: plugin.name,
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
					t('devicesModule.messages.plugins.removeCanceled', {
						plugin: plugin.name,
					})
				);
			});
	};

	return {
		remove,
	};
};
