<template>
	<fb-list class="flex-grow h-full w-full overflow-hidden">
		<template #title>
			{{ t('devicesModule.headings.devices.channels') }}
		</template>

		<template
			v-if="channelsData.length !== 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('add', $event)"
			>
				{{ t('devicesModule.buttons.addChannel.title') }}
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
						<fb-icon-with-child
							:size="50"
							type="primary"
						>
							<template #primary>
								<Icon icon="fa6-solid:layer-group" />
							</template>
							<template #secondary>
								<Icon icon="fa6-solid:info" />
							</template>
						</fb-icon-with-child>
					</template>

					<template #title>
						<el-text class="block">
							{{ t('devicesModule.texts.devices.noChannels') }}
						</el-text>
						<el-button
							:icon="FasPlus"
							type="primary"
							class="mt-4"
							@click="emit('add', $event)"
						>
							{{ t('devicesModule.buttons.addChannel.title') }}
						</el-button>
					</template>
				</el-result>
			</div>

			<device-default-device-channel
				v-for="channelData in channelsData"
				v-else
				:key="channelData.channel.id"
				:loading="props.loading"
				:device-data="props.deviceData"
				:channel-data="channelData"
				@detail="emit('detail', channelData.channel.id, $event)"
				@edit="emit('edit', channelData.channel.id, $event)"
				@remove="emit('remove', channelData.channel.id, $event)"
			/>
		</el-scrollbar>
	</fb-list>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElResult, ElScrollbar, ElSkeleton, ElSkeletonItem, ElText } from 'element-plus';
import { orderBy } from 'natural-orderby';

import { FbIconWithChild, FbList } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { IChannelData, IDeviceChannelsEmits, IDeviceChannelsProps } from '../../types';
import DeviceDefaultDeviceChannel from '../device-default/device-default-device-channel.vue';

const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'DeviceDefaultDeviceChannels',
});

const props = defineProps<IDeviceChannelsProps>();

const emit = defineEmits<IDeviceChannelsEmits>();

const { t } = useI18n();

const noResults = computed<boolean>((): boolean => props.deviceData.channels.length === 0);

const channelsData = computed<IChannelData[]>((): IChannelData[] => {
	return orderBy<IChannelData>(
		props.deviceData.channels,
		[(v): string => v.channel.name ?? v.channel.identifier, (v): string => v.channel.identifier],
		['asc']
	);
});
</script>
