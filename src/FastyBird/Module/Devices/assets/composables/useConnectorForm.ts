import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

import capitalize from 'lodash.capitalize';
import get from 'lodash.get';

import { injectStoresManager } from '@fastybird/miniserver-core';
import { useFlashMessage } from '@fastybird/miniserver-core';

import { connectorPropertiesStoreKey, connectorsStoreKey } from '../configuration';
import { FormResultType, FormResultTypes, IConnector, IConnectorForm, UseConnectorForm } from '../types';

export const useConnectorForm = (connector: IConnector): UseConnectorForm => {
	const storesManager = injectStoresManager();

	const connectorsStore = storesManager.getStore(connectorsStoreKey);
	const propertiesStore = storesManager.getStore(connectorPropertiesStoreKey);

	const { t } = useI18n();

	const flashMessage = useFlashMessage();

	const formResult = ref<FormResultType>(FormResultTypes.NONE);

	let timer: number;

	const submit = async (model: IConnectorForm): Promise<'added' | 'saved'> => {
		formResult.value = FormResultTypes.WORKING;

		const isDraft = connector.draft;
		const title =
			(model.details.name ?? model.details.identifier !== 'undefined') ? capitalize(model.details.identifier) : capitalize(connector.identifier);

		const errorMessage = connector.draft
			? t('devicesModule.messages.connectors.notCreated', { connector: title })
			: t('devicesModule.messages.connectors.notEdited', { connector: title });

		try {
			await connectorsStore.edit({
				id: connector.id,
				data: {
					name: model.details.name,
					comment: model.details.comment,
				},
			});

			if (connector.draft) {
				await connectorsStore.save({
					id: connector.id,
				});
			}
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

		try {
			const properties = propertiesStore.findForConnector(connector.id);

			for (const property of properties) {
				if (property.draft) {
					await propertiesStore.save({ id: property.id });
				}
			}
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

		for (const variablePropertyId in model.properties?.variable ?? []) {
			const property = propertiesStore.findById(variablePropertyId);
			console.log('VALUE', variablePropertyId, model);
			if (property === null) {
				continue;
			}

			try {
				await propertiesStore.edit({
					id: variablePropertyId,
					data: {
						value: model.properties!.variable![variablePropertyId],
					},
				});

				if (property.draft) {
					await propertiesStore.save({
						id: variablePropertyId,
					});
				}
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
		}

		formResult.value = FormResultTypes.OK;

		const handleTimeout = (): 'saved' => {
			clear();

			return 'saved';
		};

		timer = window.setTimeout(clear, 2000);

		if (isDraft) {
			clear();

			flashMessage.success(
				t('devicesModule.messages.connectors.created', {
					connector: title,
				})
			);

			return 'added';
		}

		flashMessage.success(
			t('devicesModule.messages.connectors.edited', {
				connector: title,
			})
		);

		return await new Promise<'added' | 'saved'>((resolve) => {
			timer = window.setTimeout(() => {
				resolve(handleTimeout());
			}, 1000);
		});
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
