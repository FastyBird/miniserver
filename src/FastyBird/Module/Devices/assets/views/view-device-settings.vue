<template>
	<app-bar-heading
		v-if="isSettingsRoute"
		teleport
	>
		<template #icon>
			<devices-device-icon
				v-if="deviceData !== null"
				:device="deviceData.device"
			/>
		</template>

		<template #title>
			{{ t('devicesModule.headings.devices.configuration') }}
		</template>

		<template #subtitle>
			{{ deviceData?.device.draft ? t('devicesModule.subHeadings.devices.new') : deviceData?.device.title }}
		</template>
	</app-bar-heading>

	<app-bar-button
		v-if="!isMDDevice && isSettingsRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onClose"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.close.title') }}</span>
	</app-bar-button>

	<app-bar-button
		v-if="!isMDDevice && isSettingsRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onSubmit"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.save.title') }}</span>
	</app-bar-button>

	<app-bar-button
		v-if="isMDDevice && isSettingsRoute && isConnectorRoute"
		teleport
		:align="AppBarButtonAlignTypes.BACK"
		:classes="['!px-1', 'mr-1']"
		@click="onBack"
	>
		<el-icon>
			<Icon icon="fa6-solid:angle-left" />
		</el-icon>
	</app-bar-button>

	<div
		v-loading="(isLoading || connectorsPlugin === null || deviceData === null) && !isSettingsRoute"
		:element-loading-text="t('devicesModule.texts.misc.loadingDevice')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorsPlugin !== null && deviceData !== null">
			<el-scrollbar class="flex-1 md:pb-[3rem]">
				<component
					:is="connectorsPlugin.components.editDevice"
					v-if="typeof connectorsPlugin.components.editDevice !== 'undefined'"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-reset="remoteFormReset"
					v-model:remote-form-result="remoteFormResult"
					:device-data="deviceData"
					:loading="isLoading"
					:channels-loading="channelsLoading"
					@add-property="onAddProperty"
					@edit-property="onEditProperty"
					@remove-property="onRemoveProperty"
				/>

				<device-default-device-settings
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-reset="remoteFormReset"
					v-model:remote-form-result="remoteFormResult"
					:device-data="deviceData"
					:loading="isLoading"
					:channels-loading="channelsLoading"
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
		v-if="deviceData !== null && newProperty !== null"
		:property="newProperty"
		:device="deviceData.device"
		@close="onCloseAddProperty"
	/>

	<property-default-property-settings-edit
		v-if="deviceData !== null && editProperty !== null"
		:property="editProperty"
		:device="deviceData.device"
		@close="onCloseEditProperty"
	/>
</template>

<script setup lang="ts">
import { computed, h, inject, onBeforeMount, onBeforeUnmount, onUnmounted, ref, watch } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRouter } from 'vue-router';

import { ElButton, ElIcon, ElScrollbar, vLoading } from 'element-plus';
import get from 'lodash.get';

import { AppBarButton, AppBarButtonAlignTypes, AppBarHeading } from '@fastybird/application';
import { DataType, ModuleSource } from '@fastybird/metadata-library';
import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import {
	DeviceDefaultDeviceSettings,
	DevicesDeviceIcon,
	PropertyDefaultPropertySettingsAdd,
	PropertyDefaultPropertySettingsEdit,
} from '../components';
import { useChannels, useConnectorRoutes, useDevice, useDeviceRoutes, usePropertyActions, useRoutesNames, useUuid } from '../composables';
import { connectorPlugins, devicePropertiesStoreKey, devicesStoreKey } from '../configuration';
import { ApplicationError } from '../errors';
import { FormResultType, FormResultTypes, IConnectorPlugin, IDevice, IDeviceData, IDeviceProperty, PropertyType } from '../types';

import { IViewDeviceSettingsProps } from './view-device-settings.types';

const FarCircleCheck = (): VNode => h(Icon, { icon: 'fa6-regular:circle-check' });
const FarCircleXmark = (): VNode => h(Icon, { icon: 'fa6-regular:circle-xmark' });

defineOptions({
	name: 'ViewDeviceSettings',
});

const props = defineProps<IViewDeviceSettingsProps>();

const { t } = useI18n();
const router = useRouter();

const { generate: generateUuid, validate: validateUuid } = useUuid();
const { isMDDevice } = useBreakpoints();
const routeNames = useRoutesNames();
const { meta } = useMeta({});

const devicesStore = inject(devicesStoreKey);
const devicePropertiesStore = inject(devicePropertiesStoreKey);

if (typeof devicesStore === 'undefined' || typeof devicePropertiesStore === 'undefined') {
	throw new ApplicationError('Something went wrong, module is wrongly configured', null);
}

const id = ref<IDevice['id']>(props.id ?? generateUuid());

if (typeof props.id !== 'undefined' && !validateUuid(props.id)) {
	throw new Error('Device identifier is not valid');
}

const { device, deviceData, isLoading, fetchDevice } = useDevice(id.value);
const { areLoading: channelsLoading, fetchChannels } = useChannels(id.value);
const { isSettingsRoute } = useDeviceRoutes();
const propertyActions = usePropertyActions({ device: device.value ?? undefined });
const { isConnectorRoute } = useConnectorRoutes();

const connectorsPlugin = computed<IConnectorPlugin | null>((): IConnectorPlugin | null => {
	return connectorPlugins.find((plugin) => plugin.type === deviceData.value?.connector?.type?.type) ?? null;
});

const remoteFormSubmit = ref<boolean>(false);
const remoteFormReset = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IDeviceProperty | null>((): IDeviceProperty | null =>
	newPropertyId.value ? devicePropertiesStore.findById(newPropertyId.value) : null
);

const editPropertyId = ref<string | null>(null);
const editProperty = computed<IDeviceProperty | null>((): IDeviceProperty | null =>
	editPropertyId.value ? devicePropertiesStore.findById(editPropertyId.value) : null
);

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onDiscard = (): void => {
	remoteFormReset.value = true;
};

const onBack = (): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetail,
			params: {
				plugin: props.plugin,
				id: props.connectorId,
			},
		});
	} else {
		router.push({
			name: routeNames.devices,
		});
	}
};

const onClose = (): void => {
	if (deviceData.value!.device.draft) {
		if (isConnectorRoute.value) {
			router.push({
				name: routeNames.connectorDetail,
				params: {
					plugin: props.plugin,
					id: props.connectorId,
				},
			});
		} else {
			router.push({
				name: routeNames.devices,
			});
		}
	} else {
		if (isConnectorRoute.value) {
			router.push({
				name: routeNames.connectorDetailDeviceDetail,
				params: {
					deviceId: id.value,
					plugin: props.plugin,
					id: props.connectorId,
				},
			});
		} else {
			router.push({
				name: routeNames.deviceDetail,
				params: {
					id: id.value,
				},
			});
		}
	}
};

const onAddProperty = async (type: PropertyType): Promise<void> => {
	if (deviceData.value === null) {
		return;
	}

	if (type === PropertyType.VARIABLE) {
		const { id } = await devicePropertiesStore.add({
			device: deviceData.value.device,
			type: { source: ModuleSource.DEVICES, type: PropertyType.VARIABLE, parent: 'device', entity: 'property' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;

		return;
	} else if (type === PropertyType.DYNAMIC) {
		const { id } = await devicePropertiesStore.add({
			device: deviceData.value.device,
			type: { source: ModuleSource.DEVICES, type: PropertyType.DYNAMIC, parent: 'device', entity: 'property' },
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
			await devicePropertiesStore.remove({ id: newProperty.value.id });
		}
	}

	newPropertyId.value = null;
};

const onEditProperty = (id: string): void => {
	const property = devicePropertiesStore.findById(id);

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

onBeforeMount(async (): Promise<void> => {
	fetchDevice()
		.then((): void => {
			if (!isLoading.value && deviceData.value === null) {
				throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
			}
		})
		.catch((e): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

	if (deviceData.value === null || !deviceData.value.device.draft) {
		fetchChannels().catch((e): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});
	}
});

onBeforeUnmount((): void => {
	if (newProperty.value?.draft) {
		devicePropertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

onUnmounted((): void => {
	if (deviceData.value?.device.draft) {
		devicesStore.remove({ id: deviceData.value?.device.id });
	}
});

watch(
	(): boolean => isLoading.value,
	(val: boolean): void => {
		if (!val && deviceData.value === null) {
			throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
		}
	}
);

watch(
	(): IDeviceData | null => deviceData.value,
	(actual: IDeviceData | null, previous: IDeviceData | null): void => {
		if (actual !== null) {
			meta.title = t('devicesModule.meta.devices.settings.title', { device: deviceData.value?.device.title });
		}

		if (!isLoading.value && actual === null) {
			throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
		}

		if (previous?.device?.draft === true && actual?.device.draft === false) {
			if (isConnectorRoute.value) {
				router.push({
					name: routeNames.connectorDetailDeviceSettings,
					params: {
						deviceId: props.id,
						plugin: props.plugin,
						id: props.connectorId,
					},
				});
			} else {
				router.push({
					name: routeNames.deviceSettings,
					params: {
						id: id.value,
					},
				});
			}
		}
	}
);
</script>
