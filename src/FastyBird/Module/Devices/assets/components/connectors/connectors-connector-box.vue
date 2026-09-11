<template>
	<el-card
		style="--el-card-padding: 0.5rem"
		body-style="--el-card-padding: 0"
		:shadow="isMDDevice ? 'always' : 'never'"
	>
		<template #header>
			<div class="flex flex-row items-center h-[2rem]">
				<connectors-connector-icon
					:connector="props.connectorData.connector"
					:size="28"
					class="mr-2"
				/>

				<h2
					v-if="props.connectorData.connector.hasComment"
					class="m-0 p-0 flex-grow h-full flex flex-col"
				>
					<el-text
						truncate
						class="font-500 w-full"
					>
						{{ props.connectorData.connector.title }}
					</el-text>
					<el-text
						truncate
						size="small"
						class="font-400 w-full"
					>
						{{ props.connectorData.connector.comment }}
					</el-text>
				</h2>
				<h2
					v-else
					class="m-0 p-0 flex-grow h-full flex flex-col justify-center"
				>
					<el-text
						truncate
						size="large"
						class="font-500 w-full"
					>
						{{ props.connectorData.connector.title }}
					</el-text>
				</h2>
			</div>
		</template>

		<dl
			:class="ns.e('description')"
			class="grid m-0"
		>
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t('devicesModule.texts.connectors.devices') }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
				<el-text>
					<i18n-t
						keypath="devicesModule.texts.connectors.devicesCount"
						:plural="props.connectorData.devices.length"
					>
						<template #count>
							<strong>{{ props.connectorData.devices.length }}</strong>
						</template>
					</i18n-t>
				</el-text>
			</dd>
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t('devicesModule.texts.connectors.status') }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
				<el-text>
					<el-tag
						:type="stateColor"
						size="small"
					>
						{{ t(`devicesModule.misc.state.${connectorState.toLowerCase()}`) }}
					</el-tag>
				</el-text>
			</dd>
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t('devicesModule.texts.connectors.service') }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
				<el-text>
					<el-tag
						v-if="props.service === null"
						size="small"
						type="danger"
					>
						{{ t('devicesModule.misc.missing') }}
					</el-tag>
					<el-tag
						v-else
						:type="props.service.running ? 'success' : 'danger'"
						size="small"
					>
						{{ props.service.running ? t('devicesModule.misc.state.running') : t('devicesModule.misc.state.stopped') }}
					</el-tag>
				</el-text>
			</dd>
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t('devicesModule.texts.connectors.bridges') }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
				<el-text>
					<i18n-t
						keypath="devicesModule.texts.connectors.bridgesCount"
						:plural="props.bridges.length"
					>
						<template #count>
							<strong>{{ props.bridges.length }}</strong>
						</template>
					</i18n-t>
				</el-text>
			</dd>
			<dt
				class="b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t('devicesModule.texts.connectors.alerts') }}
			</dt>
			<dd class="col-start-2 m-0 p-2 flex items-center">
				<el-text>
					<el-tag
						size="small"
						:type="props.alerts.length === 0 ? 'success' : 'danger'"
					>
						<i18n-t
							keypath="devicesModule.texts.connectors.alertsCount"
							:plural="props.alerts.length"
						>
							<template #count>
								<strong>{{ props.alerts.length }}</strong>
							</template>
						</i18n-t>
					</el-tag>
				</el-text>
			</dd>
		</dl>

		<template #footer>
			<div class="flex justify-center items-center">
				<div class="flex-1 text-center">
					<el-button
						:icon="FasPlug"
						size="small"
						@click="emit('devices', props.connectorData.connector.id, $event)"
					>
						{{ !isXLDevice ? t('devicesModule.buttons.showDevices.title') : t('devicesModule.buttons.devices.title') }}
					</el-button>
				</div>

				<el-divider direction="vertical" />

				<div class="flex-1 text-center">
					<el-button
						:icon="FasCircleInfo"
						size="small"
						@click="emit('detail', props.connectorData.connector.id, $event)"
					>
						{{ !isXLDevice ? t('devicesModule.buttons.showDetails.title') : t('devicesModule.buttons.details.title') }}
					</el-button>
				</div>

				<el-divider direction="vertical" />

				<div class="flex-1 text-center">
					<el-dropdown
						trigger="click"
						:placement="isMDDevice ? 'bottom' : 'top-end'"
					>
						<el-button size="small">
							{{ t('devicesModule.buttons.more.title') }}
							<el-icon class="el-icon--right">
								<Icon icon="fa6-solid:ellipsis-vertical" />
							</el-icon>
						</el-button>

						<template #dropdown>
							<el-dropdown-menu>
								<el-dropdown-item
									:disabled="!wsStatus"
									:icon="FasArrowsRotate"
								>
									{{ t('devicesModule.buttons.restart.title') }}
								</el-dropdown-item>
								<el-dropdown-item
									:disabled="!wsStatus || isRunning"
									:icon="FasPlay"
									@click="emit('start', props.connectorData.connector.id, $event)"
								>
									{{ t('devicesModule.buttons.start.title') }}
								</el-dropdown-item>
								<el-dropdown-item
									:disabled="!wsStatus || isStopped"
									:icon="FasStop"
									@click="emit('stop', props.connectorData.connector.id, $event)"
								>
									{{ t('devicesModule.buttons.stop.title') }}
								</el-dropdown-item>
								<el-dropdown-item
									:icon="FasGears"
									divided
									@click="emit('edit', props.connectorData.connector.id, $event)"
								>
									{{ t('devicesModule.buttons.configure.title') }}
								</el-dropdown-item>
								<el-dropdown-item
									:icon="FasTrash"
									divided
									@click="emit('remove', props.connectorData.connector.id, $event)"
								>
									{{ t('devicesModule.buttons.remove.title') }}
								</el-dropdown-item>
							</el-dropdown-menu>
						</template>
					</el-dropdown>
				</div>
			</div>
		</template>
	</el-card>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';

import { ElButton, ElCard, ElDivider, ElDropdown, ElDropdownItem, ElDropdownMenu, ElIcon, ElTag, ElText, useNamespace } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';
import { Icon } from '@iconify/vue';

import { useConnectorState } from '../../composables';
import { ConnectionState, IConnector, StateColor } from '../../types';
import ConnectorsConnectorIcon from '../connectors/connectors-connector-icon.vue';

import { IConnectorsConnectorBoxProps } from './connectors-connector-box.types';

const FasArrowsRotate = (): VNode => h(Icon, { icon: 'fa6-solid:arrows-rotate' });
const FasCircleInfo = (): VNode => h(Icon, { icon: 'fa6-solid:circle-info' });
const FasGears = (): VNode => h(Icon, { icon: 'fa6-solid:gears' });
const FasPlay = (): VNode => h(Icon, { icon: 'fa6-solid:play' });
const FasPlug = (): VNode => h(Icon, { icon: 'fa6-solid:plug' });
const FasStop = (): VNode => h(Icon, { icon: 'fa6-solid:stop' });
const FasTrash = (): VNode => h(Icon, { icon: 'fa6-solid:trash' });

defineOptions({
	name: 'ConnectorsConnectorBox',
});

const props = defineProps<IConnectorsConnectorBoxProps>();

const emit = defineEmits<{
	(e: 'devices', id: IConnector['id'], event: Event): void;
	(e: 'detail', id: IConnector['id'], event: Event): void;
	(e: 'edit', id: IConnector['id'], event: Event): void;
	(e: 'restart', id: IConnector['id'], event: Event): void;
	(e: 'stop', id: IConnector['id'], event: Event): void;
	(e: 'start', id: IConnector['id'], event: Event): void;
	(e: 'remove', id: IConnector['id'], event: Event): void;
}>();

const ns = useNamespace('connectors-connector-box');
const { t } = useI18n();

const { isMDDevice, isXLDevice } = useBreakpoints();

const { status: wsStatus } = useWampV1Client();

const { state: connectorState } = useConnectorState(props.connectorData.connector);

const isRunning = computed<boolean>((): boolean => {
	return [ConnectionState.RUNNING, ConnectionState.CONNECTED].includes(connectorState.value);
});

const isStopped = computed<boolean>((): boolean => {
	return [ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(connectorState.value);
});

const stateColor = computed<StateColor>((): StateColor => {
	if (!wsStatus || [ConnectionState.UNKNOWN].includes(connectorState.value)) {
		return undefined;
	}

	if ([ConnectionState.CONNECTED, ConnectionState.READY, ConnectionState.RUNNING].includes(connectorState.value)) {
		return 'success';
	} else if ([ConnectionState.INIT].includes(connectorState.value)) {
		return 'info';
	} else if ([ConnectionState.DISCONNECTED, ConnectionState.STOPPED, ConnectionState.SLEEPING].includes(connectorState.value)) {
		return 'warning';
	}

	return 'danger';
});
</script>
