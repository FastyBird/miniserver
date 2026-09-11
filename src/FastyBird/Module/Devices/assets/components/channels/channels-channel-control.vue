<template>
	<div class="flex flex-row justify-center md:justify-between px-2 py-2 b-b b-b-solid">
		<el-button
			v-if="isMDDevice && isDetailRoute"
			:icon="FasGears"
			size="small"
			@click="emit('edit', $event)"
		>
			{{ t('devicesModule.buttons.configure.title') }}
		</el-button>
		<el-button
			v-if="isMDDevice && isSettingsRoute"
			:icon="FasCircleInfo"
			size="small"
			@click="emit('detail', $event)"
		>
			{{ t('devicesModule.buttons.detail.title') }}
		</el-button>

		<div>
			<el-dropdown-menu v-if="props.channelData.controls.length > 0">
				<el-dropdown-item
					v-for="control of props.channelData.controls"
					:key="control.id"
					:disabled="!wsStatus"
					:icon="FasArrowsRotate"
				>
					{{ control.name }}
				</el-dropdown-item>
			</el-dropdown-menu>

			<el-button
				:icon="FasTrash"
				size="small"
				type="warning"
				plain
				@click="emit('remove', $event)"
			>
				{{ t('devicesModule.buttons.remove.title') }}
			</el-button>
		</div>
	</div>
</template>

<script setup lang="ts">
import { h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElDropdownItem, ElDropdownMenu } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';
import { Icon } from '@iconify/vue';

import { useChannelRoutes } from '../../composables';

import { IChannelsChannelControlProps } from './channels-channel-control.types';

const FasArrowsRotate = (): VNode => h(Icon, { icon: 'fa6-solid:arrows-rotate' });
const FasCircleInfo = (): VNode => h(Icon, { icon: 'fa6-solid:circle-info' });
const FasGears = (): VNode => h(Icon, { icon: 'fa6-solid:gears' });
const FasTrash = (): VNode => h(Icon, { icon: 'fa6-solid:trash' });

defineOptions({
	name: 'ChannelsChannelControl',
});

const props = defineProps<IChannelsChannelControlProps>();

const emit = defineEmits<{
	(e: 'edit', event: Event): void;
	(e: 'detail', event: Event): void;
	(e: 'remove', event: Event): void;
	(e: 'action', action: string, event: Event): void;
}>();

const { t } = useI18n();

const { isMDDevice } = useBreakpoints();

const { isDetailRoute, isSettingsRoute } = useChannelRoutes();

const { status: wsStatus } = useWampV1Client();
</script>
