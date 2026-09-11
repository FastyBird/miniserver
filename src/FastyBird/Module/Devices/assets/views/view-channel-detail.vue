<template>
	<app-bar-heading
		v-if="isDetailRoute"
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
			{{ channelData?.channel.title }}
		</template>

		<template
			v-if="channelData?.channel.hasComment"
			#subtitle
		>
			{{ channelData?.channel.comment }}
		</template>

		<template
			v-else
			#subtitle
		>
			{{ channelData?.device?.title }}
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
		@click="onChannelEdit"
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
		v-loading="isLoading || connectorsPlugin === null || channelData === null"
		:element-loading-text="t('devicesModule.texts.misc.loadingChannel')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorsPlugin !== null && channelData !== null">
			<channels-channel-control
				:channel-data="channelData"
				@edit="onChannelEdit"
				@detail="onChannelDetail"
				@remove="onChannelRemove"
			/>

			<el-collapse-transition>
				<div
					v-if="!isSettingsRoute"
					class="flex flex-col"
				>
					<div
						v-loading="isLoading"
						:element-loading-text="t('devicesModule.texts.misc.loadingChannel')"
					>
						<component
							:is="connectorsPlugin.components.channelDetail"
							v-if="typeof connectorsPlugin.components.channelDetail !== 'undefined'"
							:loading="isLoading"
							:channel-data="channelData"
							:alerts="[]"
						/>

						<channel-default-channel-detail
							:loading="isLoading"
							:channel-data="channelData"
							:alerts="[]"
						/>
					</div>
				</div>
			</el-collapse-transition>

			<el-collapse-transition>
				<div v-if="isSettingsRoute">
					<suspense>
						<div class="flex-grow overflow-hidden h-full">
							<view-error :type="'channel'">
								<router-view />
							</view-error>
						</div>
					</suspense>
				</div>
			</el-collapse-transition>
		</template>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRouter } from 'vue-router';

import { ElCollapseTransition, ElIcon, vLoading } from 'element-plus';
import get from 'lodash.get';

import { AppBarButton, AppBarButtonAlignTypes, AppBarHeading } from '@fastybird/application';
import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import { ChannelDefaultChannelDetail, ChannelsChannelControl, ChannelsChannelIcon, ViewError } from '../components';
import { useChannel, useChannelActions, useChannelRoutes, useConnectorRoutes, useRoutesNames, useUuid } from '../composables';
import { connectorPlugins } from '../configuration';
import { ApplicationError } from '../errors';
import { IChannelData, IConnectorPlugin } from '../types';

import { IViewChannelDetailProps } from './view-channel-detail.types';

defineOptions({
	name: 'ViewChannelDetail',
});

const props = defineProps<IViewChannelDetailProps>();

const { t } = useI18n();
const router = useRouter();
const { meta } = useMeta({});

const { isMDDevice } = useBreakpoints();
const routeNames = useRoutesNames();
const { validate: validateUuid } = useUuid();

const { channelData, isLoading, fetchChannel } = useChannel(props.id);
const channelActions = useChannelActions();
const { isDetailRoute, isSettingsRoute } = useChannelRoutes();
const { isConnectorRoute } = useConnectorRoutes();

if (!validateUuid(props.id)) {
	throw new Error('Channel identifier is not valid');
}

const connectorsPlugin = computed<IConnectorPlugin | null>((): IConnectorPlugin | null => {
	return connectorPlugins.find((plugin) => plugin.type === channelData.value?.device?.type?.type) ?? null;
});

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

const onChannelDetail = (): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetailDeviceDetailChannelDetail,
			params: {
				channelId: props.id,
				deviceId: props.deviceId,
				plugin: props.plugin,
				id: props.connectorId,
			},
		});
	} else {
		router.push({
			name: routeNames.channelDetail,
			params: {
				channelId: props.id,
				id: props.deviceId,
			},
		});
	}
};

const onChannelEdit = (): void => {
	if (isConnectorRoute.value) {
		router.push({
			name: routeNames.connectorDetailDeviceDetailChannelSettings,
			params: {
				channelId: props.id,
				deviceId: props.deviceId,
				plugin: props.plugin,
				id: props.connectorId,
			},
		});
	} else {
		router.push({
			name: routeNames.channelSettings,
			params: {
				channelId: props.id,
				id: props.deviceId,
			},
		});
	}
};

const onChannelRemove = (): void => {
	channelActions.remove(props.id);
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
	(val: IChannelData | null): void => {
		if (val !== null) {
			meta.title = t('devicesModule.meta.channels.detail.title', { channel: channelData.value?.channel.title });
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Channel Not Found', null, { statusCode: 404, message: 'Channel Not Found' });
		}
	}
);
</script>
