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
			<el-button
				:disabled="!wsStatus"
				:icon="FasArrowsRotate"
				size="small"
				@click="emit('restart', $event)"
			>
				{{ t('devicesModule.buttons.restart.title') }}
			</el-button>
			<el-button
				:disabled="!wsStatus || isRunning"
				:icon="FasPlay"
				size="small"
				type="success"
				plain
				@click="emit('stop', $event)"
			>
				{{ t('devicesModule.buttons.start.title') }}
			</el-button>
			<el-button
				:disabled="!wsStatus || isStopped"
				:icon="FasStop"
				size="small"
				type="danger"
				plain
				@click="emit('start', $event)"
			>
				{{ t('devicesModule.buttons.stop.title') }}
			</el-button>

			<el-divider direction="vertical" />

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
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';

import { ElButton, ElDivider } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';
import { Icon } from '@iconify/vue';

import { useConnectorState, useRoutesNames } from '../../composables';
import { ConnectionState } from '../../types';

import { IConnectorsConnectorControlProps } from './connectors-connector-control.types';

const FasArrowsRotate = (): VNode => h(Icon, { icon: 'fa6-solid:arrows-rotate' });
const FasCircleInfo = (): VNode => h(Icon, { icon: 'fa6-solid:circle-info' });
const FasGears = (): VNode => h(Icon, { icon: 'fa6-solid:gears' });
const FasPlay = (): VNode => h(Icon, { icon: 'fa6-solid:play' });
const FasStop = (): VNode => h(Icon, { icon: 'fa6-solid:stop' });
const FasTrash = (): VNode => h(Icon, { icon: 'fa6-solid:trash' });

defineOptions({
	name: 'ConnectorsConnectorControl',
});

const props = defineProps<IConnectorsConnectorControlProps>();

const emit = defineEmits<{
	(e: 'edit', event: Event): void;
	(e: 'detail', event: Event): void;
	(e: 'restart', event: Event): void;
	(e: 'stop', event: Event): void;
	(e: 'start', event: Event): void;
	(e: 'remove', event: Event): void;
}>();

const { t } = useI18n();
const route = useRoute();

const { isMDDevice } = useBreakpoints();
const routeNames = useRoutesNames();

const { status: wsStatus } = useWampV1Client();

const { state: connectorState } = useConnectorState(props.connectorData.connector);

const isDetailRoute = computed<boolean>((): boolean => route.name === routeNames.connectorDetail);
const isSettingsRoute = computed<boolean>((): boolean => route.name === routeNames.connectorSettings);

const isRunning = computed<boolean>((): boolean => {
	return [ConnectionState.RUNNING, ConnectionState.CONNECTED].includes(connectorState.value);
});

const isStopped = computed<boolean>((): boolean => {
	return [ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(connectorState.value);
});
</script>
