<template>
	<fb-app-bar-heading
		v-if="isSettingsRoute"
		teleport
	>
		<template #icon>
			<channels-channel-icon
				v-if="channelData !== null && channelData.device !== null"
				:device="channelData.device"
				:channel="channelData.channel"
			/>
		</template>

		<template #title>
			{{ t('devicesModule.headings.channels.configuration') }}
		</template>

		<template #subtitle>
			{{ channelData?.channel.draft ? t('devicesModule.subHeadings.channels.new') : channelData?.channel.title }}
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
		small
		@click="onSubmit"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.save.title') }}</span>
	</fb-app-bar-button>

	<fb-app-bar-button
		v-if="isMDDevice && isSettingsRoute && isConnectorRoute"
		teleport
		:align="AppBarButtonAlignTypes.BACK"
		:classes="['!px-1', 'mr-1']"
		@click="onBack"
	>
		<el-icon>
			<Icon icon="fa6-solid:angle-left" />
		</el-icon>
	</fb-app-bar-button>

	<div
		v-loading="(isLoading || connectorsPlugin === null || channelData === null) && !isSettingsRoute"
		:element-loading-text="t('devicesModule.texts.misc.loadingChannel')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorsPlugin !== null && channelData !== null">
			<el-scrollbar class="flex-1 md:pb-[3rem]">
				<component
					:is="connectorsPlugin.components.editChannel"
					v-if="typeof connectorsPlugin.components.editChannel !== 'undefined'"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-reset="remoteFormReset"
					v-model:remote-form-result="remoteFormResult"
					:channel-data="channelData"
					:loading="isLoading"
					@add-property="onAddProperty"
					@edit-property="onEditProperty"
					@remove-property="onRemoveProperty"
				/>

				<channel-default-channel-settings
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-reset="remoteFormReset"
					v-model:remote-form-result="remoteFormResult"
					:channel-data="channelData"
					:loading="isLoading"
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
		v-if="channelData !== null && newProperty !== null"
		:property="newProperty"
		:channel="channelData.channel"
		@close="onCloseAddProperty"
	/>

	<property-default-property-settings-edit
		v-if="channelData !== null && editProperty !== null"
		:property="editProperty"
		:channel="channelData.channel"
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

import { DataType, ModuleSource } from '@fastybird/metadata-library';
import { useBreakpoints } from '@fastybird/tools';
import { AppBarButtonAlignTypes, FbAppBarButton, FbAppBarHeading } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import {
	ChannelDefaultChannelSettings,
	ChannelsChannelIcon,
	PropertyDefaultPropertySettingsAdd,
	PropertyDefaultPropertySettingsEdit,
} from '../components';
import { useChannel, useChannelRoutes, useConnectorRoutes, usePropertyActions, useRoutesNames, useUuid } from '../composables';
import { channelPropertiesStoreKey, channelsStoreKey, connectorPlugins } from '../configuration';
import { ApplicationError } from '../errors';
import { FormResultType, FormResultTypes, IChannel, IChannelData, IChannelProperty, IConnectorPlugin, PropertyType } from '../types';

import { IViewChannelSettingsProps } from './view-channel-settings.types';

const FarCircleCheck = (): VNode => h(Icon, { icon: 'fa6-regular:circle-check' });
const FarCircleXmark = (): VNode => h(Icon, { icon: 'fa6-regular:circle-xmark' });

defineOptions({
	name: 'ViewChannelSettings',
});

const props = defineProps<IViewChannelSettingsProps>();

const { t } = useI18n();
const router = useRouter();

const { generate: generateUuid, validate: validateUuid } = useUuid();
const { isMDDevice } = useBreakpoints();
const routeNames = useRoutesNames();
const { meta } = useMeta({});

const channelsStore = inject(channelsStoreKey);
const channelPropertiesStore = inject(channelPropertiesStoreKey);

if (typeof channelsStore === 'undefined' || typeof channelPropertiesStore === 'undefined') {
	throw new ApplicationError('Something went wrong, module is wrongly configured', null);
}

const id = ref<IChannel['id']>(props.id ?? generateUuid());

if (typeof props.id !== 'undefined' && !validateUuid(props.id)) {
	throw new Error('Channel identifier is not valid');
}

const { channel, channelData, isLoading, fetchChannel } = useChannel(id.value);
const { isSettingsRoute } = useChannelRoutes();
const propertyActions = usePropertyActions({ channel: channel.value ?? undefined });
const { isConnectorRoute } = useConnectorRoutes();

const connectorsPlugin = computed<IConnectorPlugin | null>((): IConnectorPlugin | null => {
	return connectorPlugins.find((plugin) => plugin.type === channelData.value?.device?.connector.type.type) ?? null;
});

const remoteFormSubmit = ref<boolean>(false);
const remoteFormReset = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const newPropertyId = ref<string | null>(null);
const newProperty = computed<IChannelProperty | null>((): IChannelProperty | null =>
	newPropertyId.value ? channelPropertiesStore.findById(newPropertyId.value) : null
);

const editPropertyId = ref<string | null>(null);
const editProperty = computed<IChannelProperty | null>((): IChannelProperty | null =>
	editPropertyId.value ? channelPropertiesStore.findById(editPropertyId.value) : null
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
			name: routeNames.connectorDetailDeviceDetail,
			params: {
				deviceId: props.deviceId,
				plugin: props.plugin,
				id: props.connectorId,
			},
		});
	} else {
		router.push({
			name: routeNames.deviceDetail,
			params: {
				id: props.deviceId,
			},
		});
	}
};

const onClose = (): void => {
	if (channelData.value!.channel.draft) {
		if (isConnectorRoute.value) {
			router.push({
				name: routeNames.connectorDetailDeviceDetail,
				params: {
					deviceId: props.deviceId,
					plugin: props.plugin,
					id: props.connectorId,
				},
			});
		} else {
			router.push({
				name: routeNames.deviceDetail,
				params: {
					id: props.deviceId,
				},
			});
		}
	} else {
		if (isConnectorRoute.value) {
			router.push({
				name: routeNames.connectorDetailDeviceDetailChannelDetail,
				params: {
					channelId: id.value,
					deviceId: props.deviceId,
					plugin: props.plugin,
					id: props.connectorId,
				},
			});
		} else {
			router.push({
				name: routeNames.channelDetail,
				params: {
					channelId: id.value,
					id: props.deviceId,
				},
			});
		}
	}
};

const onAddProperty = async (type: PropertyType): Promise<void> => {
	if (channelData.value === null) {
		return;
	}

	if (type === PropertyType.VARIABLE) {
		const { id } = await channelPropertiesStore.add({
			channel: channelData.value.channel,
			type: { source: ModuleSource.DEVICES, type: PropertyType.VARIABLE, parent: 'channel', entity: 'property' },
			draft: true,
			data: {
				identifier: generateUuid(),
				dataType: DataType.UNKNOWN,
			},
		});

		newPropertyId.value = id;

		return;
	} else if (type === PropertyType.DYNAMIC) {
		const { id } = await channelPropertiesStore.add({
			channel: channelData.value.channel,
			type: { source: ModuleSource.DEVICES, type: PropertyType.DYNAMIC, parent: 'channel', entity: 'property' },
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
			await channelPropertiesStore.remove({ id: newProperty.value.id });
		}
	}

	newPropertyId.value = null;
};

const onEditProperty = (id: string): void => {
	const property = channelPropertiesStore.findById(id);

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
	fetchChannel()
		.then((): void => {
			if (!isLoading.value && channelData.value === null) {
				throw new ApplicationError('Channel Not Found', null, { statusCode: 404, message: 'Channel Not Found' });
			}
		})
		.catch((e): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Channel Not Found', e, { statusCode: 404, message: 'Channel Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});
});

onBeforeUnmount((): void => {
	if (newProperty.value?.draft) {
		channelPropertiesStore.remove({ id: newProperty.value.id });
		newPropertyId.value = null;
	}
});

onUnmounted((): void => {
	if (channelData.value?.channel.draft) {
		channelsStore.remove({ id: channelData.value?.channel.id });
	}
});

watch(
	(): boolean => isLoading.value,
	(val: boolean): void => {
		if (!val && channelData.value === null) {
			throw new ApplicationError('Channel Not Found', null, { statusCode: 404, message: 'Channel Not Found' });
		}
	}
);

watch(
	(): IChannelData | null => channelData.value,
	(actual: IChannelData | null, previous: IChannelData | null): void => {
		if (actual !== null) {
			meta.title = t('devicesModule.meta.channels.settings.title', { channel: channelData.value?.channel.title });
		}

		if (!isLoading.value && actual === null) {
			throw new ApplicationError('Channel Not Found', null, { statusCode: 404, message: 'Channel Not Found' });
		}

		if (previous?.channel.draft === true && actual?.channel.draft === false) {
			if (isConnectorRoute.value) {
				router.push({
					name: routeNames.connectorDetailDeviceDetailChannelSettings,
					params: {
						channelId: id.value,
						deviceId: props.deviceId,
						plugin: props.plugin,
						id: props.connectorId,
					},
				});
			} else {
				router.push({
					name: routeNames.channelSettings,
					params: {
						channelId: id.value,
						id: props.deviceId,
					},
				});
			}
		}
	}
);
</script>
