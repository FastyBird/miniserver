<template>
	<div
		v-if="props.loading"
		class="flex flex-row flex-wrap justify-between gap-y-2 px-2 mt-2"
	>
		<el-skeleton
			animated
			class="!w-[125px] !h-[125px] border-1 border-style-solid rounded-lg box-border p-2 flex flex-col overflow-hidden"
			style="--el-skeleton-circle-size: 45px"
		>
			<template #template>
				<div class="flex-grow flex flex-row justify-between">
					<el-skeleton-item variant="circle" />
				</div>
				<el-skeleton-item variant="text" />
				<el-skeleton-item
					variant="text"
					class="mt-1 !w-[30%]"
				/>
			</template>
		</el-skeleton>
	</div>

	<div
		v-else-if="noResults"
		class="flex flex-col justify-center items-center h-full w-full"
	>
		<el-result class="w-[70%]">
			<template #icon>
				<app-icon-with-child
					:size="50"
					type="primary"
				>
					<template #primary>
						<Icon icon="fa6-solid:plug" />
					</template>
					<template #secondary>
						<Icon icon="fa6-solid:info" />
					</template>
				</app-icon-with-child>
			</template>

			<template #title>
				<el-text class="block">
					{{ t('homeKitConnector.texts.connectors.noDevices') }}
				</el-text>
				<el-button
					:icon="FasPlus"
					type="primary"
					class="mt-4"
					@click="emit('add', $event)"
				>
					{{ t('homeKitConnector.buttons.addDevice.title') }}
				</el-button>
			</template>
		</el-result>
	</div>

	<div
		v-else
		class="flex flex-row flex-wrap justify-between gap-y-2 px-2 mt-2"
	>
		<connector-device
			v-for="deviceData in devicesData"
			:key="deviceData.device.id"
			:loading="props.loading"
			:connector-data="props.connectorData"
			:device-data="deviceData"
			class="shrink-0"
			@detail="emit('detail', deviceData.device.id, $event)"
		/>
	</div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElResult, ElSkeleton, ElSkeletonItem, ElText } from 'element-plus';
import { orderBy } from 'natural-orderby';

import { AppIconWithChild } from '@fastybird/application';
import { IConnectorDevicesEmits, IConnectorDevicesProps, IDeviceData } from '@fastybird/devices-module';
import { Icon } from '@iconify/vue';

import { ConnectorDevice } from '../components';

const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'ConnectorDevices',
});

const props = defineProps<IConnectorDevicesProps>();

const emit = defineEmits<IConnectorDevicesEmits>();

const { t } = useI18n();

const noResults = computed<boolean>((): boolean => props.connectorData.devices.length === 0);

const devicesData = computed<IDeviceData[]>((): IDeviceData[] => {
	return orderBy<IDeviceData>(
		props.connectorData.devices,
		[(v): string => v.device.name ?? v.device.identifier, (v): string => v.device.identifier],
		['asc']
	);
});
</script>
