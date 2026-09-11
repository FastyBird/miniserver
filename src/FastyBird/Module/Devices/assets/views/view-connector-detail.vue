<template>
	<app-bar-heading
		v-if="isDetailRoute"
		teleport
	>
		<template #icon>
			<connectors-connector-icon
				v-if="connectorData !== null"
				:connector="connectorData.connector"
			/>
		</template>

		<template #title>
			{{ connectorData?.connector.title }}
		</template>

		<template #subtitle>
			{{ connectorData?.connector.comment }}
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
		@click="onConnectorEdit"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.edit.title') }}</span>
	</app-bar-button>

	<div
		v-loading="isLoading || connectorsPlugin === null || connectorData === null"
		:element-loading-text="t('devicesModule.texts.misc.loadingConnector')"
		class="flex flex-col overflow-hidden h-full"
	>
		<template v-if="connectorsPlugin !== null && connectorData !== null">
			<view-error
				v-if="isDeviceRoute"
				:type="'device'"
			>
				<router-view :key="props.deviceId" />
			</view-error>

			<template v-else>
				<connectors-connector-control
					v-if="isMDDevice"
					v-loading="isLoading"
					:connector-data="connectorData"
					@detail="onConnectorDetail"
					@edit="onConnectorEdit"
					@remove="onConnectorRemove"
				/>

				<el-collapse-transition>
					<div
						v-if="!isSettingsRoute"
						class="flex flex-col"
					>
						<div
							v-loading="isLoading"
							:element-loading-text="t('devicesModule.texts.misc.loadingConnector')"
						>
							<connectors-connector-control
								v-if="!isMDDevice"
								:connector-data="connectorData"
								@detail="onConnectorDetail"
								@edit="onConnectorEdit"
								@remove="onConnectorRemove"
							/>

							<component
								:is="connectorsPlugin.components.connectorDetail"
								v-if="typeof connectorsPlugin.components.connectorDetail !== 'undefined'"
								:loading="isLoading"
								:connector-data="connectorData"
								:alerts="[]"
								:bridges="[]"
								:service="null"
							/>

							<connector-default-connector-detail
								v-else
								:loading="isLoading"
								:connector-data="connectorData"
								:alerts="[]"
								:bridges="[]"
								:service="null"
							/>
						</div>

						<div
							v-loading="devicesLoading"
							:element-loading-text="t('devicesModule.texts.misc.loadingDevices')"
							class="flex-grow overflow-hidden"
						>
							<component
								:is="connectorsPlugin.components.connectorDevices"
								v-if="typeof connectorsPlugin.components.connectorDevices !== 'undefined'"
								:loading="devicesLoading"
								:connector-data="connectorData"
								@detail="onDeviceOpen"
								@edit="onDeviceEdit"
								@remove="onDeviceRemove"
								@add="onDeviceCreate"
							/>

							<connector-default-connector-devices
								v-else
								:loading="devicesLoading"
								:connector-data="connectorData"
								@detail="onDeviceOpen"
								@edit="onDeviceEdit"
								@remove="onDeviceRemove"
								@add="onDeviceCreate"
							/>
						</div>
					</div>
				</el-collapse-transition>

				<el-collapse-transition>
					<div v-if="isSettingsRoute">
						<suspense>
							<div class="flex-grow overflow-hidden h-full">
								<view-error :type="'connector'">
									<router-view />
								</view-error>
							</div>
						</suspense>
					</div>
				</el-collapse-transition>
			</template>
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

import {
	ConnectorDefaultConnectorDetail,
	ConnectorDefaultConnectorDevices,
	ConnectorsConnectorControl,
	ConnectorsConnectorIcon,
	ViewError,
} from '../components';
import {
	useConnector,
	useConnectorActions,
	useConnectorRoutes,
	useDeviceActions,
	useDeviceRoutes,
	useDevices,
	useRoutesNames,
	useUuid,
} from '../composables';
import { connectorPlugins } from '../configuration';
import { ApplicationError } from '../errors';
import { IConnectorData, IConnectorPlugin, IDevice } from '../types';

import { IViewConnectorDetailProps } from './view-connector-detail.types';

defineOptions({
	name: 'ViewConnectorDetail',
});

const props = defineProps<IViewConnectorDetailProps>();

const { t } = useI18n();
const router = useRouter();
const { meta } = useMeta({});

const { isMDDevice } = useBreakpoints();
const routeNames = useRoutesNames();
const { validate: validateUuid } = useUuid();

const { connectorData, isLoading, fetchConnector } = useConnector(props.id);
const connectorActions = useConnectorActions();
const { isDetailRoute, isSettingsRoute } = useConnectorRoutes();
const { areLoading: devicesLoading, fetchDevices } = useDevices(props.id);
const deviceActions = useDeviceActions();
const { isDeviceRoute } = useDeviceRoutes();

if (!validateUuid(props.id)) {
	throw new Error('Connector identifier is not valid');
}

const connectorsPlugin = computed<IConnectorPlugin | null>((): IConnectorPlugin | null => {
	return connectorPlugins.find((plugin) => plugin.type === props.plugin) ?? null;
});

const onClose = (): void => {
	router.push({
		name: routeNames.pluginDetail,
		params: {
			plugin: props.plugin,
		},
	});
};

const onConnectorDetail = (): void => {
	router.push({
		name: routeNames.connectorDetail,
		params: {
			plugin: props.plugin,
			id: props.id,
		},
	});
};

const onConnectorEdit = (): void => {
	router.push({
		name: routeNames.connectorSettings,
		params: {
			plugin: props.plugin,
			id: props.id,
		},
	});
};

const onConnectorRemove = (): void => {
	connectorActions.remove(props.id);
};

const onDeviceCreate = (): void => {
	router.push({
		name: routeNames.connectorDetailDeviceCreate,
		params: {
			plugin: props.plugin,
			id: props.id,
		},
	});
};

const onDeviceOpen = (id: IDevice['id']): void => {
	router.push({
		name: routeNames.connectorDetailDeviceDetail,
		params: {
			plugin: props.plugin,
			id: props.id,
			deviceId: id,
		},
	});
};

const onDeviceEdit = (id: IDevice['id']): void => {
	router.push({
		name: routeNames.connectorDetailDeviceSettings,
		params: {
			plugin: props.plugin,
			id: props.id,
			deviceId: id,
		},
	});
};

const onDeviceRemove = (id: IDevice['id']): void => {
	deviceActions.remove(id);
};

onBeforeMount((): void => {
	fetchConnector()
		.then((): void => {
			if (!isLoading.value && connectorData.value === null) {
				throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
			}
		})
		.catch((e: any): void => {
			if (get(e, 'exception.response.status', 0) === 404) {
				throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
			} else {
				throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
			}
		});

	fetchDevices().catch((e: any): void => {
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});
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
	(val: IConnectorData | null): void => {
		if (val !== null) {
			meta.title = t('devicesModule.meta.connectors.detail.title', { connector: connectorData.value?.connector.title });
		}

		if (!isLoading.value && val === null) {
			throw new ApplicationError('Connector Not Found', null, { statusCode: 404, message: 'Connector Not Found' });
		}
	}
);
</script>
