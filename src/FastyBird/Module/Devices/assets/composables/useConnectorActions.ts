import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';

import { ElMessageBox } from 'element-plus';
import get from 'lodash.get';

import { injectStoresManager } from '@fastybird/miniserver-core';
import { useFlashMessage } from '@fastybird/miniserver-core';

import { connectorsStoreKey } from '../configuration';
import { ApplicationError } from '../errors';
import { IConnector, UseConnectorActions } from '../types';

import { useRoutesNames } from './useRoutesNames';

export const useConnectorActions = (): UseConnectorActions => {
	const router = useRouter();
	const route = useRoute();

	const { t } = useI18n();

	const routeNames = useRoutesNames();
	const flashMessage = useFlashMessage();

	const storesManager = injectStoresManager();

	const connectorsStore = storesManager.getStore(connectorsStoreKey);

	const restart = async (id: IConnector['id']): Promise<void> => {
		// TODO: Handle connector restart action
		console.log('HANDLE ACTION FOR', id);
	};

	const start = async (id: IConnector['id']): Promise<void> => {
		// TODO: Handle connector start action
		console.log('HANDLE ACTION FOR', id);
	};

	const stop = async (id: IConnector['id']): Promise<void> => {
		// TODO: Handle connector stop action
		console.log('HANDLE ACTION FOR', id);
	};

	const remove = async (id: IConnector['id']): Promise<void> => {
		const connector = await connectorsStore.findById(id);

		if (connector === null) {
			throw new ApplicationError("Something went wrong, connector can't be loaded", null, { statusCode: 503, message: 'Something went wrong' });
		}

		ElMessageBox.confirm(
			t('devicesModule.messages.connectors.confirmRemove', { connector: connector.title }),
			t('devicesModule.headings.connectors.remove'),
			{
				confirmButtonText: t('devicesModule.buttons.yes.title'),
				cancelButtonText: t('devicesModule.buttons.no.title'),
				type: 'warning',
			}
		)
			.then(async (): Promise<void> => {
				if (
					route.matched.find((matched) => {
						return matched.name === routeNames.connectorDetail;
					}) !== undefined &&
					route.params.id === id
				) {
					if (typeof route.params.plugin !== 'undefined') {
						await router.push({
							name: routeNames.pluginDetail,
							params: {
								plugin: route.params.plugin,
							},
						});
					} else {
						await router.push({ name: routeNames.plugins });
					}
				}

				try {
					await connectorsStore.remove({ id });

					flashMessage.success(
						t('devicesModule.messages.connectors.removed', {
							connector: connector.title,
						})
					);
				} catch (e: any) {
					const errorMessage = t('devicesModule.messages.connectors.notRemoved', {
						connector: connector.title,
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
					t('devicesModule.messages.connectors.removeCanceled', {
						connector: connector.title,
					})
				);
			});
	};

	return {
		restart,
		start,
		stop,
		remove,
	};
};
