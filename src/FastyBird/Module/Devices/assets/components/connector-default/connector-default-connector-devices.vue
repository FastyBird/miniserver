<template>
	<app-list class="flex-grow h-full w-full overflow-hidden">
		<template #title>
			{{ t('devicesModule.headings.connectors.devices') }}
		</template>

		<template
			v-if="devicesData.length !== 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('add', $event)"
			>
				{{ t('devicesModule.buttons.addDevice.title') }}
			</el-button>
		</template>

		<el-scrollbar class="w-full">
			<div
				v-if="props.loading"
				class="p-2"
			>
				<el-skeleton
					animated
					style="--el-skeleton-circle-size: 32px"
				>
					<template #template>
						<div class="p-2">
							<div class="flex items-center h-[2rem] w-full overflow-hidden">
								<div class="flex-basis-[3rem]">
									<el-skeleton-item variant="circle" />
								</div>
								<div class="flex-basis-[30%]">
									<el-skeleton-item variant="text" />
									<el-skeleton-item
										variant="text"
										style="width: 30%"
									/>
								</div>
							</div>
						</div>
					</template>
				</el-skeleton>
			</div>

			<div
				v-else-if="noResults"
				class="flex flex-col justify-center h-full w-full"
			>
				<el-result>
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
							{{ t('devicesModule.texts.connectors.noDevices') }}
						</el-text>
						<el-button
							:icon="FasPlus"
							type="primary"
							class="mt-4"
							@click="emit('add', $event)"
						>
							{{ t('devicesModule.buttons.addDevice.title') }}
						</el-button>
					</template>
				</el-result>
			</div>

			<connector-default-connector-device
				v-for="deviceData in devicesData"
				v-else
				:key="deviceData.device.id"
				:loading="props.loading"
				:connector-data="props.connectorData"
				:device-data="deviceData"
				@detail="emit('detail', deviceData.device.id, $event)"
				@edit="emit('edit', deviceData.device.id, $event)"
				@remove="emit('remove', deviceData.device.id, $event)"
			/>
		</el-scrollbar>
	</app-list>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElResult, ElScrollbar, ElSkeleton, ElSkeletonItem, ElText } from 'element-plus';
import { orderBy } from 'natural-orderby';

import { AppIconWithChild, AppList } from '@fastybird/application';
import { Icon } from '@iconify/vue';

import { IConnectorDevicesEmits, IConnectorDevicesProps, IDeviceData } from '../../types';
import ConnectorDefaultConnectorDevice from '../connector-default/connector-default-connector-device.vue';

const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'ConnectorDefaultConnectorDevices',
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
