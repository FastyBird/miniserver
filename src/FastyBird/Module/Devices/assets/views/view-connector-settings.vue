<template>
	<fb-app-bar-heading
		v-if="isSettingsRoute"
		teleport
	>
		<template #icon>
			<connectors-connector-icon
				v-if="connectorData !== null"
				:connector="connectorData.connector"
			/>
		</template>

		<template #title>
			{{ t('devicesModule.headings.connectors.configuration') }}
		</template>

		<template #subtitle>
			{{ connectorData?.connector.draft ? t('devicesModule.subHeadings.connectors.new') : connectorData?.connector.title }}
		</template>
	</fb-app-bar-heading>

	<fb-app-bar-button
		v-if="!isMDDevice && isSettingsRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onClose"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.close.title') }}</span>
	</fb-app-bar-button>

	<fb-app-bar-button
		v-if="!isMDDevice && isSettingsRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		:disabled="isLoading"
		small
		@click="onSubmit"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.save.title') }}</span>
	</fb-app-bar-button>

	<div
		v-loading="(isLoading || connectorsPlugin === null || connectorData === null) && !isSettingsRoute"
		:element-loading-text="t('devicesModule.texts.misc.loadingConnector')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorsPlugin !== null && connectorData !== null">
			<el-scrollbar class="flex-1 md:pb-[3rem]">
				<component
					:is="connectorsPlugin.components.editConnector"
					v-if="typeof connectorsPlugin.components.editConnector !== 'undefined'"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-reset="remoteFormReset"
					v-model:remote-form-result="remoteFormResult"
					:connector-data="connectorData"
					:loading="isLoading"
					:devices-loading="devicesLoading"
					@add-property="onAddProperty"
					@edit-property="onEditProperty"
					@remove-property="onRemoveProperty"
				/>

				<connector-default-connector-settings
					v-else
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-reset="remoteFormReset"
					v-model:remote-form-result="remoteFormResult"
					:connector-data="connectorData"
					:loading="isLoading"
					:devices-loading="devicesLoading"
					@add-property="onAddProperty"
					@edit-property="onEditProperty"
					@remove-property="onRemoveProperty"
				/>
			</el-scrollbar>

			<div
				v-if="isMDDevice"
				class="flex flex-row gap-2 justify-end items-center b-t b-t-solid shadow-top z-10 absolute bottom-0 left-0 w-full h-[3rem]"
				style="background-color: var(--el-drawer-bg-color)"
			>
				<div class="p-2">
					<el-button
						link
						class="mr-2"
						@click="onDiscard"
					>
						{{ t('devicesModule.buttons.discard.title') }}
					</el-button>

					<el-button
						:loading="remoteFormResult === FormResultTypes.WORKING"
						:disabled="isLoading || remoteFormResult !== FormResultTypes.NONE"
						:icon="remoteFormResult === FormResultTypes.OK ? FarCircleCheck : remoteFormResult === FormResultTypes.ERROR ? FarCircleXmark : undefined"
						type="primary"
						class="order-2"
						@click="onSubmit"
					>
						{{ t('devicesModule.buttons.save.title') }}
					</el-button>
				</div>
			</div>
		</template>
	</div>

	<property-default-property-settings-add
		v-if="connectorData !== null && newProperty !== null"
		:property="newProperty"
		:connector="connectorData.connector"
		@close="onCloseAddProperty"
	/>

	<property-default-property-settings-edit
		v-if="connectorData !== null && editProperty !== null"
		:property="editProperty"
		:connector="connectorData.connector"
		@close="onCloseEditProperty"
	/>
</template>

<script setup lang="ts">
import { computed, h, inject, onBeforeMount, onBeforeUnmount, onUnmounted, ref, watch } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRouter } from 'vue-router';

import { ElButton, ElScrollbar, vLoading } from 'element-plus';
import get from 'lodash.get';

import { DataType, ModuleSource } from '@fastybird/metadata-library';
import { useBreakpoints } from '@fastybird/tools';
import { AppBarButtonAlignTypes, FbAppBarButton, FbAppBarHeading } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import {
	ConnectorDefaultConnectorSettings,
	ConnectorsConnectorIcon,
	PropertyDefaultPropertySettingsAdd,
	PropertyDefaultPropertySettingsEdit,
} from '../components';
import { useConnector, useConnectorRoutes, useDevices, usePropertyActions, useRoutesNames, useUuid } from '../composables';
import { connectorPlugins, connectorPropertiesStoreKey, connectorsStoreKey } from '../configuration';
import { ApplicationError } from '../errors';
import { FormResultType, FormResultTypes, IConnector, IConnectorData, IConnectorPlugin, IConnectorProperty, PropertyType } from '../types';

import { IViewConnectorSettingsProps } from './view-connector-settings.types';

const FarCircleCheck = (): VNode => h(Icon, { icon: 'fa6-regular:circle-check' });
const FarCircleXmark = (): VNode => h(Icon, { icon: 'fa6-regular:circle-xmark' });

defineOptions({
	name: 'ViewConnectorSettings',
});

const props = defineProps<IViewConnectorSettingsProps>();

const { t } = useI18n();
const router = useRouter();

const { generate: generateUuid, validate: validateUuid } = useUuid();
const { isMDDevice } = useBreakpoints();
const routeNames = useRoutesNames();
const { meta } = useMeta({});

const connectorsStore = inject(connectorsStoreKey);
const connectorPropertiesStore = inject(connectorPropertiesStoreKey);

if (typeof connectorsStore === 'undefined' || typeof connectorPropertiesStore === 'undefined') {
	throw new ApplicationError('Something went wrong, module is wrongly configured', null);
}

const id = ref<IConnector['id']>(props.id ?? generateUuid());

if (typeof props.id !== 'undefined' && !validateUuid(props.id)) {
	throw new Error('Connector identifier is not valid');
}

const { connector, connectorData, isLoading, fetchConnector } = useConnector(id.value);
const { isSettingsRoute } = useConnectorRoutes();
const { areLoading: devicesLoading, fetchDevices } = useDevices(id.value);
const propertyActions = usePropertyActions({ connector: connector.value ?? undefined });

const connectorsPlugin = computed<IConnectorPlugin | null>((): IConnectorPlugin | null => {
	return connectorPlugins.find((plugin) => plugin.type === props.plugin) ?? null;
});

const remoteFormSubmit = ref<boolean>(false);
const remoteFormReset = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null =>
	newPropertyId.value ? connectorPropertiesStore.findById(newPropertyId.value) : null
);

const editPropertyId = ref<string | null>(null);
const editProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null =>
	editPropertyId.value ? connectorPropertiesStore.findById(editPropertyId.value) : null
);

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onDiscard = (): void => {
	remoteFormReset.value = true;
};

const onClose = (): void => {
	if (connectorData.value!.connector.draft) {
		router.push({
			name: routeNames.pluginDetail,
			params: {
				plugin: props.plugin,
			},
		});
	} else {
		router.push({
			name: routeNames.connectorDetail,
			params: {
				plugin: props.plugin,
				id: id.value,
			},
		});
	}
};

const onAddProperty = async (type: PropertyType): Promise<void> => {
	if (connectorData.value === null) {
		return;
	}

	if (type === PropertyType.VARIABLE) {
		const { id } = await connectorPropertiesStore.add({
			connector: connectorData.value.connector,
			type: { source: ModuleSource.DEVICES, type: PropertyType.VARIABLE, parent: 'connector', entity: 'property' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;

		return;
	} else if (type === PropertyType.DYNAMIC) {
		const { id } = await connectorPropertiesStore.add({
			connector: connectorData.value.connector,
			type: { source: ModuleSource.DEVICES, type: PropertyType.DYNAMIC, parent: 'connector', entity: 'property' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;

		return;
	}

	newPropertyId.value = null;
};

const onCloseAddProperty = async (canceled: boolean): Promise<void> => {
	if (canceled) {
		if (newProperty.value?.draft) {
			await connectorPropertiesStore.remove({ id: newProperty.value.id });
		}
	}

	newPropertyId.value = null;
};

const onEditProperty = (id: string): void => {
	const property = connectorPropertiesStore.findById(id);

	if (property === null) {
		return;
	}

	editPropertyId.value = id;
};

const onCloseEditProperty = (): void => {
	editPropertyId.value = null;
};

const onRemoveProperty = (id: string): void => {
	propertyActions.remove(id);
};

onBeforeMount((): void => {
	fetchConnector()
		.then(() => {
			if (!isLoading.value && connectorData.value === null) {
				throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
			}
		})
		.catch((e): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

	if (connectorData.value === null || !connectorData.value.connector.draft) {
		fetchDevices().catch((e): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});
	}
});

onBeforeUnmount((): void => {
	if (newProperty.value?.draft) {
		connectorPropertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

onUnmounted((): void => {
	if (connectorData.value?.connector.draft) {
		connectorsStore.remove({ id: connectorData.value?.connector.id });
	}
});

watch(
	(): boolean => isLoading.value,
	(val: boolean): void => {
		if (!val && connectorData.value === null) {
			throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
		}
	}
);

watch(
	(): IConnectorData | null => connectorData.value,
	(actual: IConnectorData | null, previous: IConnectorData | null): void => {
		if (actual !== null) {
			meta.title = t('devicesModule.meta.connectors.settings.title', { connector: connectorData.value?.connector.title });
		}

		if (!isLoading.value && actual === null) {
			throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
		}

		if (previous?.connector.draft === true && actual?.connector.draft === false) {
			router.push({
				name: routeNames.connectorSettings,
				params: {
					plugin: props.plugin,
					id: id.value,
				},
			});
		}
	}
);
</script>
