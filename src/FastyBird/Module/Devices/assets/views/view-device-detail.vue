<template>
	<app-bar-heading
		v-if="isDetailRoute"
		teleport
	>
		<template #icon>
			<devices-device-icon
				v-if="deviceData !== null"
				:device="deviceData.device"
			/>
		</template>

		<template #title>
			{{ deviceData?.device.title }}
		</template>

		<template
			v-if="deviceData?.device.hasComment"
			#subtitle
		>
			{{ deviceData?.device.comment }}
		</template>

		<template
			v-else
			#subtitle
		>
			{{ deviceData?.connector?.title }}
		</template>
	</app-bar-heading>

	<app-bar-button
		v-if="!isMDDevice && isDetailRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onClose"
	>
		<template #icon>
			<el-icon>
				<Icon icon="fa6-solid:angle-left" />
			</el-icon>
		</template>
	</app-bar-button>

	<app-bar-button
		v-if="!isMDDevice && isDetailRoute"
		teleport
		:align="AppBarButtonAlignTypes.RIGHT"
		small
		@click="onDeviceEdit"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.edit.title') }}</span>
	</app-bar-button>

	<app-bar-button
		v-if="isMDDevice && isDetailRoute && isConnectorRoute"
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
		v-loading="isLoading || connectorsPlugin === null || deviceData === null"
		:element-loading-text="t('devicesModule.texts.misc.loadingDevice')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorsPlugin !== null && deviceData !== null">
			<view-error
				v-if="isChannelRoute"
				:type="'channel'"
			>
				<router-view :key="props.channelId" />
			</view-error>

			<template v-else>
				<devices-device-control
					v-if="isMDDevice"
					:device-data="deviceData"
					@edit="onDeviceEdit"
					@detail="onDeviceDetail"
					@remove="onDeviceRemove"
				/>

				<fb-expandable-box
					:show="!isSettingsRoute"
					class="flex flex-col"
				>
					<div
						v-loading="isLoading"
						:element-loading-text="t('devicesModule.texts.misc.loadingDevice')"
					>
						<component
							:is="connectorsPlugin.components.deviceDetail"
							v-if="typeof connectorsPlugin.components.deviceDetail !== 'undefined'"
							:loading="isLoading"
							:device-data="deviceData"
							:alerts="[]"
							:bridges="[]"
						/>

						<device-default-device-detail
							:loading="isLoading"
							:device-data="deviceData"
							:alerts="[]"
							:bridges="[]"
						/>
					</div>

					<div
						v-loading="channelsLoading"
						:element-loading-text="t('devicesModule.texts.misc.loadingChannels')"
						class="flex-grow overflow-hidden"
					>
						<component
							:is="connectorsPlugin.components.deviceChannels"
							v-if="typeof connectorsPlugin.components.deviceChannels !== 'undefined'"
							:loading="channelsLoading"
							:device-data="deviceData"
							@detail="onChannelOpen"
							@add="onChannelCreate"
						/>

						<device-default-device-channels
							v-else
							:loading="channelsLoading"
							:device-data="deviceData"
							@detail="onChannelOpen"
							@edit="onChannelEdit"
							@remove="onChannelRemove"
							@add="onChannelCreate"
						/>
					</div>
				</fb-expandable-box>

				<fb-expandable-box :show="isSettingsRoute">
					<suspense>
						<div class="flex-grow overflow-hidden h-full">
							<view-error :type="'device'">
								<router-view />
							</view-error>
						</div>
					</suspense>
				</fb-expandable-box>
			</template>
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';

import { ElIcon, vLoading } from 'element-plus';
import get from 'lodash.get';

import { AppBarButton, AppBarButtonAlignTypes, AppBarHeading } from '@fastybird/application';
import { useBreakpoints } from '@fastybird/tools';
import { FbExpandableBox } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { DeviceDefaultDeviceChannels, DeviceDefaultDeviceDetail, DevicesDeviceControl, DevicesDeviceIcon, ViewError } from '../components';
import {
	useChannelActions,
	useChannelRoutes,
	useChannels,
	useConnectorRoutes,
	useDevice,
	useDeviceActions,
	useDeviceRoutes,
	useRoutesNames,
	useUuid,
} from '../composables';
import { connectorPlugins } from '../configuration';
import { ApplicationError } from '../errors';
import { IChannel, IConnectorPlugin, IDeviceData } from '../types';

import { IViewDeviceDetailProps } from './view-device-detail.types';

defineOptions({
	name: 'ViewDeviceDetail',
});

const props = defineProps<IViewDeviceDetailProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { meta } = useMeta({});

const { isMDDevice } = useBreakpoints();
const routeNames = useRoutesNames();
const { validate: validateUuid } = useUuid();

const { deviceData, isLoading, fetchDevice } = useDevice(props.id);
const deviceActions = useDeviceActions();
const { isDetailRoute, isSettingsRoute } = useDeviceRoutes();
const { areLoading: channelsLoading, fetchChannels } = useChannels(props.id);
const channelActions = useChannelActions();
const { isChannelRoute } = useChannelRoutes();
const { isConnectorRoute } = useConnectorRoutes();

if (!validateUuid(props.id)) {
	throw new Error('Device identifier is not valid');
}

const connectorsPlugin = computed<IConnectorPlugin | null>((): IConnectorPlugin | null => {
	return connectorPlugins.find((plugin) => plugin.type === deviceData.value?.connector?.type?.type) ?? null;
});

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
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetail,
			params: {
				plugin: route.params.plugin,
				id: route.params.connectorId,
			},
		});
	} else {
		router.push({ name: routeNames.devices });
	}
};

const onDeviceDetail = (): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetailDeviceDetail,
			params: {
				deviceId: props.id,
				plugin: route.params.plugin,
				id: route.params.connectorId,
			},
		});
	} else {
		router.push({ name: routeNames.deviceDetail, params: { id: props.id } });
	}
};

const onDeviceEdit = (): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetailDeviceSettings,
			params: {
				deviceId: props.id,
				plugin: route.params.plugin,
				id: route.params.connectorId,
			},
		});
	} else {
		router.push({ name: routeNames.deviceSettings, params: { id: props.id } });
	}
};

const onDeviceRemove = (): void => {
	deviceActions.remove(props.id);
};

const onChannelCreate = (): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetailDeviceDetailChannelCreate,
			params: {
				deviceId: props.id,
				plugin: route.params.plugin,
				id: route.params.connectorId,
			},
		});
	} else {
		router.push({
			name: routeNames.channelCreate,
			params: {
				id: props.id,
			},
		});
	}
};

const onChannelOpen = (id: IChannel['id']): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetailDeviceDetailChannelDetail,
			params: {
				channelId: id,
				deviceId: props.id,
				plugin: route.params.plugin,
				id: route.params.connectorId,
			},
		});
	} else {
		router.push({
			name: routeNames.channelDetail,
			params: {
				id: props.id,
				channelId: id,
			},
		});
	}
};

const onChannelEdit = (id: IChannel['id']): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetailDeviceDetailChannelSettings,
			params: {
				channelId: id,
				deviceId: props.id,
				plugin: route.params.plugin,
				id: route.params.connectorId,
			},
		});
	} else {
		router.push({
			name: routeNames.channelSettings,
			params: {
				id: props.id,
				channelId: id,
			},
		});
	}
};

const onChannelRemove = (id: IChannel['id']): void => {
	channelActions.remove(id);
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

	fetchChannels().catch((e): void => {
		console.log(e);
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Device Not Found', e, { statusCode: 404, message: 'Device Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});
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
	(val: IDeviceData | null): void => {
		if (val !== null) {
			meta.title = t('devicesModule.meta.devices.detail.title', { device: deviceData.value?.device.title });
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Device Not Found', null, { statusCode: 404, message: 'Device Not Found' });
		}
	}
);
</script>
