<template>
	<fb-app-bar-heading
		v-if="!isMDDevice && isPluginDetailRoute"
		teleport
	>
		<template #icon>
			<Icon icon="fa6-solid:plug-circle-bolt" />
		</template>

		<template #title>
			{{ connectorsPlugin?.name }}
		</template>

		<template #subtitle>
			{{ connectorsPlugin?.description }}
		</template>
	</fb-app-bar-heading>

	<fb-app-bar-button
		v-if="!isMDDevice && isPluginDetailRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onClose"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.close.title') }}</span>
	</fb-app-bar-button>

	<fb-app-bar-button
		v-if="!isMDDevice && isPluginDetailRoute"
		teleport
		:align="AppBarButtonAlignTypes.RIGHT"
		small
		@click="onConnectorCreate"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.add.title') }}</span>
	</fb-app-bar-button>

	<div
		v-loading="connectorsPlugin === null"
		:element-loading-text="t('devicesModule.texts.misc.loadingPlugin')"
		class="flex flex-col h-full overflow-hidden"
	>
		<view-error
			v-if="!isLGDevice && !isPluginDetailRoute"
			type="connector"
		>
			<router-view :key="props.id" />
		</view-error>

		<div
			v-else-if="connectorsPlugin !== null"
			class="flex flex-col overflow-hidden h-full"
		>
			<plugins-plugin-header
				v-if="isMDDevice"
				:plugin="connectorsPlugin"
				:connectors-data="connectorsData"
				:alerts="alerts"
				:bridges="bridges"
				@back="onBack"
				@add-connector="onConnectorCreate"
				@remove="onPluginRemove"
				@devices="onDevices"
				@bridges="onBridges"
			/>

			<plugins-plugin-stats
				v-if="!isMDDevice"
				:plugin="connectorsPlugin"
				:connectors-data="connectorsData"
				:alerts="alerts"
				:bridges="bridges"
				class="mb-2"
				@devices="onDevices"
				@bridges="onBridges"
			/>

			<el-scrollbar
				v-loading="connectorsLoading"
				:element-loading-text="t('devicesModule.texts.misc.loadingConnectors')"
				class="flex-grow overflow-hidden b-t b-t-solid px-1"
			>
				<div class="px-1">
					<el-row :gutter="isMDDevice ? 8 : 0">
						<el-col
							v-for="connectorData in connectorsData"
							:key="connectorData.connector.id"
							:xl="isXXLDevice ? 8 : 12"
							:lg="24"
							class="pt-2 last:pb-2"
						>
							<connectors-connector-box
								:connector-data="connectorData"
								:alerts="connectorAlerts"
								:bridges="connectorBridges"
								:service="connectorService"
								@devices="() => onConnectorDevices(connectorData.connector.id)"
								@detail="() => onConnectorOpen(connectorData.connector.id)"
								@edit="() => onConnectorEdit(connectorData.connector.id)"
								@remove="() => onConnectorRemove(connectorData.connector.id)"
								@restart="() => onConnectorRestart(connectorData.connector.id)"
								@start="() => onConnectorStart(connectorData.connector.id)"
								@stop="() => onConnectorStop(connectorData.connector.id)"
							/>
						</el-col>
					</el-row>
				</div>
			</el-scrollbar>
		</div>
	</div>

	<el-drawer
		v-if="isLGDevice"
		v-model="showDrawer"
		:show-close="false"
		:size="'40%'"
		:with-header="false"
		@closed="onCloseDrawer"
	>
		<div class="flex flex-col h-full">
			<fb-app-bar menu-button-hidden>
				<template #button-right>
					<fb-app-bar-button
						:align="AppBarButtonAlignTypes.RIGHT"
						@click="onCloseDrawer"
					>
						<template #icon>
							<el-icon>
								<Icon icon="fa6-solid:xmark" />
							</el-icon>
						</template>
					</fb-app-bar-button>
				</template>
			</fb-app-bar>

			<view-error type="connector">
				<suspense>
					<router-view :key="props.id" />
				</suspense>
			</view-error>
		</div>
	</el-drawer>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';

import { ElCol, ElDrawer, ElIcon, ElRow, ElScrollbar, vLoading } from 'element-plus';
import get from 'lodash.get';

import { useBreakpoints } from '@fastybird/tools';
import { AppBarButtonAlignTypes, FbAppBar, FbAppBarButton, FbAppBarHeading } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { ConnectorsConnectorBox, PluginsPluginHeader, PluginsPluginStats, ViewError } from '../components';
import { useConnectorActions, useConnectors, useDevices, usePluginActions, useRoutesNames } from '../composables';
import { connectorPlugins } from '../configuration';
import { ApplicationError } from '../errors';
import { IBridge, IConnector, IConnectorPlugin, IDebugLog, IService } from '../types';

import { IViewPluginDetailProps } from './view-plugin-detail.types';

defineOptions({
	name: 'ViewPluginDetail',
});

const props = defineProps<IViewPluginDetailProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const { meta } = useMeta({});

const { isMDDevice, isLGDevice, isXXLDevice } = useBreakpoints();
const routeNames = useRoutesNames();

const pluginActions = usePluginActions();
const connectorActions = useConnectorActions();
const { fetchDevices } = useDevices();

const connectorsPlugin = computed<IConnectorPlugin | null>((): IConnectorPlugin | null => {
	return connectorPlugins.find((plugin) => plugin.type === props.plugin) ?? null;
});

const { connectorsData, areLoading: connectorsLoading, fetchConnectors } = useConnectors(props.plugin);

const isPluginDetailRoute = computed<boolean>((): boolean => {
	return route.name === routeNames.pluginDetail;
});

const bridges = computed<IBridge[]>((): IBridge[] => {
	return [];
});

const alerts = computed<IDebugLog[]>((): IDebugLog[] => {
	return [];
});

const connectorBridges = computed<IBridge[]>((): IBridge[] => {
	return [];
});

const connectorService = computed<IService | null>((): IService | null => {
	return null;
});

const connectorAlerts = computed<IDebugLog[]>((): IDebugLog[] => {
	return [];
});

const showDrawer = ref<boolean>(false);

const onBack = (): void => {
	router.back();
};

const onClose = (): void => {
	router.push({
		name: routeNames.plugins,
	});
};

const onCloseDrawer = (): void => {
	router.push({
		name: routeNames.pluginDetail,
		params: {
			plugin: props.plugin,
		},
	});
};

const onPluginRemove = (): void => {
	pluginActions.remove(props.plugin);
};

const onConnectorCreate = (): void => {
	router.push({
		name: routeNames.connectorCreate,
		params: {
			plugin: props.plugin,
		},
	});
};

const onConnectorOpen = (id: IConnector['id']): void => {
	router.push({
		name: routeNames.connectorDetail,
		params: {
			plugin: props.plugin,
			id,
		},
	});
};

const onConnectorEdit = (id: IConnector['id']): void => {
	router.push({
		name: routeNames.connectorSettings,
		params: {
			plugin: props.plugin,
			id,
		},
	});
};

const onConnectorRemove = (id: IConnector['id']): void => {
	connectorActions.remove(id);
};

const onConnectorRestart = (id: IConnector['id']): void => {
	connectorActions.restart(id);
};

const onConnectorStart = (id: IConnector['id']): void => {
	connectorActions.start(id);
};

const onConnectorStop = (id: IConnector['id']): void => {
	connectorActions.stop(id);
};

const onConnectorDevices = (id: IConnector['id']): void => {
	router.push({
		name: routeNames.devices,
		query: {
			plugin: props.plugin,
			connector: id,
		},
	});
};

const onDevices = (): void => {
	router.push({
		name: routeNames.devices,
		query: {
			plugin: props.plugin,
		},
	});
};

const onBridges = (): void => {
	// TODO: Handle show connector bridges
};

onBeforeMount((): void => {
	fetchConnectors()
		.then((): void => {
			for (const connectorData of connectorsData.value) {
				fetchDevices(connectorData.connector.id).catch((e: any): void => {
					if (get(e, 'exception.response.status', 0) === 404) {
						throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
					} else {
						throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
					}
				});
			}
		})
		.catch((e: any): void => {
			console.log(e);
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		});

	showDrawer.value =
		route.matched.find((matched) => matched.name === routeNames.connectorCreate || matched.name === routeNames.connectorDetail) !== undefined;

	if (connectorsPlugin.value !== null) {
		meta.title = t('devicesModule.meta.plugins.detail.title', { plugin: connectorsPlugin.value.name });
	}
});

watch(
	(): string => route.path,
	(): void => {
		showDrawer.value =
			route.matched.find((matched) => matched.name === routeNames.connectorCreate || matched.name === routeNames.connectorDetail) !== undefined;
	}
);

watch(
	(): IConnectorPlugin | null => connectorsPlugin.value,
	(val: IConnectorPlugin | null): void => {
		if (val !== null) {
			meta.title = t('devicesModule.meta.plugins.detail.title', { plugin: val.name });
		}
	}
);
</script>
