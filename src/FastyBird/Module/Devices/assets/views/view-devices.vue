<template>
	<app-bar-heading
		v-if="!isMDDevice && isDevicesListRoute"
		teleport
	>
		<template #icon>
			<Icon icon="fa6-solid:plug" />
		</template>

		<template #title>
			{{ t('devicesModule.headings.devices.allDevices') }}
		</template>

		<template #subtitle>
			{{ t('devicesModule.subHeadings.devices.allDevices', devicesData.length) }}
		</template>
	</app-bar-heading>

	<app-bar-button
		v-if="!isMDDevice && isDevicesListRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onDeviceCreate"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.new.title') }}</span>
	</app-bar-button>

	<div class="flex flex-row h-full w-full">
		<devices-list-devices
			v-if="isDevicesListRoute || isLGDevice"
			v-model:filters="filters"
			v-model:paginate-size="paginateSize"
			v-model:paginate-page="paginatePage"
			v-model:sort-dir="sortDir"
			v-loading="areLoading || !loaded"
			:element-loading-text="t('devicesModule.texts.misc.loadingDevices')"
			:all-items="devicesData"
			:items="devicesDataPaginated"
			:total-rows="totalRows"
			:loading="areLoading || !loaded"
			@detail="onDeviceOpen"
			@edit="onDeviceEdit"
			@remove="onDeviceRemove"
			@reset-filters="onResetFilters"
			@adjust="onAdjustList"
		/>

		<router-view v-else />
	</div>

	<el-drawer
		v-if="isLGDevice"
		v-model="showDrawer"
		:show-close="false"
		:size="adjustList ? '300px' : '40%'"
		:with-header="false"
		@closed="onCloseDrawer"
	>
		<div class="flex flex-col h-full">
			<app-bar menu-button-hidden>
				<template #button-right>
					<app-bar-button
						:align="AppBarButtonAlignTypes.RIGHT"
						@click="onCloseDrawer"
					>
						<template #icon>
							<el-icon>
								<Icon icon="fa6-solid:xmark" />
							</el-icon>
						</template>
					</app-bar-button>
				</template>
			</app-bar>

			<template v-if="showDrawer">
				<devices-list-adjust
					v-if="adjustList"
					:plugins="connectorPlugins"
					:connectors="connectors"
					:filters="filters"
				/>

				<view-error
					v-else
					type="device"
				>
					<suspense>
						<router-view
							:key="props.id"
							v-slot="{ Component }"
						>
							<component :is="Component" />
						</router-view>
					</suspense>
				</view-error>
			</template>
		</div>
	</el-drawer>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';

import { ElDrawer, ElIcon, vLoading } from 'element-plus';
import get from 'lodash.get';

import { AppBar, AppBarButton, AppBarButtonAlignTypes, AppBarHeading } from '@fastybird/application';
import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import { DevicesListAdjust, DevicesListDevices, ViewError } from '../components';
import { useConnectors, useDeviceActions, useDevices, useRoutesNames } from '../composables';
import { connectorPlugins } from '../configuration';
import { ApplicationError } from '../errors';

import { IViewDevicesProps } from './view-devices.types';

defineOptions({
	name: 'ViewDevices',
});

const props = defineProps<IViewDevicesProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
useMeta({
	title: t('devicesModule.meta.devices.list.title'),
});

const { isMDDevice, isLGDevice } = useBreakpoints();
const routeNames = useRoutesNames();

const { connectors, fetchConnectors } = useConnectors();
const { areLoading, loaded, fetchDevices, devicesData, devicesDataPaginated, totalRows, filters, paginateSize, paginatePage, sortDir, resetFilter } =
	useDevices();
const deviceActions = useDeviceActions();

const isDevicesListRoute = computed<boolean>((): boolean => {
	return route.name === routeNames.devices;
});

const showDrawer = ref<boolean>(false);
const adjustList = ref<boolean>(false);

const onCloseDrawer = (): void => {
	if (adjustList.value) {
		showDrawer.value = false;
		adjustList.value = false;
	} else {
		router.push({
			name: routeNames.devices,
		});
	}
};

const onDeviceOpen = (id: string): void => {
	router.push({
		name: routeNames.deviceDetail,
		params: {
			id,
		},
	});
};

const onDeviceEdit = (id: string): void => {
	router.push({
		name: routeNames.deviceSettings,
		params: {
			id,
		},
	});
};

const onDeviceCreate = (): void => {
	router.push({
		name: routeNames.deviceCreate,
	});
};

const onDeviceRemove = (id: string): void => {
	deviceActions.remove(id);
};

const onResetFilters = (): void => {
	resetFilter();
};

const onAdjustList = (): void => {
	showDrawer.value = true;
	adjustList.value = true;
};

onBeforeMount((): void => {
	fetchConnectors().catch((e: any): void => {
		throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
	});

	fetchDevices().catch((e: any): void => {
		if (get(e, 'exception.response.status', 0) === 404) {
			throw new ApplicationError('Connector Not Found', e, { statusCode: 404, message: 'Connector Not Found' });
		} else {
			throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
		}
	});

	showDrawer.value =
		route.matched.find((matched) => matched.name === routeNames.deviceCreate || matched.name === routeNames.deviceDetail) !== undefined;
});

watch(
	(): string => route.path,
	(): void => {
		showDrawer.value =
			route.matched.find((matched) => matched.name === routeNames.deviceCreate || matched.name === routeNames.deviceDetail) !== undefined;
	}
);
</script>
