<template>
	<app-icon-with-child
		v-if="props.withState"
		:type="stateColor"
		:size="props.size"
		:data-connector-state="stateName"
	>
		<template #primary>
			<Icon icon="fa6-solid:layer-group" />
		</template>
		<template #secondary>
			<component :is="stateIcon" />
		</template>
	</app-icon-with-child>

	<el-icon
		v-else
		:size="props.size"
	>
		<Icon icon="fa6-solid:layer-group" />
	</el-icon>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { Component, VNode } from 'vue';

import { ElIcon } from 'element-plus';

import { AppIconWithChild } from '@fastybird/application';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';
import { Icon } from '@iconify/vue';

import { useDeviceState } from '../../composables';
import { ConnectionState, StateColor } from '../../types';

import { IChannelsChannelIconProps } from './channels-channel-icon.types';

const FarCircle = (): VNode => h(Icon, { icon: 'fa6-regular:circle' });
const FarCircleCheck = (): VNode => h(Icon, { icon: 'fa6-regular:circle-check' });
const FarCirclePause = (): VNode => h(Icon, { icon: 'fa6-regular:circle-pause' });
const FarCirclePlay = (): VNode => h(Icon, { icon: 'fa6-regular:circle-play' });
const FarCircleQuestion = (): VNode => h(Icon, { icon: 'fa6-regular:circle-question' });
const FarCircleStop = (): VNode => h(Icon, { icon: 'fa6-regular:circle-stop' });
const FarCircleUser = (): VNode => h(Icon, { icon: 'fa6-regular:circle-user' });
const FasCircleExclamation = (): VNode => h(Icon, { icon: 'fa6-solid:circle-exclamation' });

defineOptions({
	name: 'ChannelsChannelIcon',
});

const props = withDefaults(defineProps<IChannelsChannelIconProps>(), {
	withState: false,
});

const { status: wsStatus } = useWampV1Client();

const { state: deviceState } = useDeviceState(props.device);

const stateIcon = computed<Component>((): Component => {
	if (!wsStatus || deviceState.value === ConnectionState.SLEEPING) {
		return FarCirclePause;
	} else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(deviceState.value)) {
		return FarCircleStop;
	} else if (deviceState.value === ConnectionState.ALERT) {
		return FasCircleExclamation;
	} else if (deviceState.value === ConnectionState.LOST) {
		return FarCircleQuestion;
	} else if (deviceState.value === ConnectionState.INIT) {
		return FarCircleUser;
	} else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(deviceState.value)) {
		return FarCirclePlay;
	} else if (deviceState.value === ConnectionState.CONNECTED) {
		return FarCircleCheck;
	}

	return FarCircle;
});

const stateName = computed<string>((): string => {
	if (!wsStatus || deviceState.value === ConnectionState.SLEEPING) {
		return 'pause';
	} else if ([ConnectionState.STOPPED, ConnectionState.DISCONNECTED].includes(deviceState.value)) {
		return 'stop';
	} else if (deviceState.value === ConnectionState.ALERT) {
		return 'alert';
	} else if (deviceState.value === ConnectionState.LOST) {
		return 'lost';
	} else if (deviceState.value === ConnectionState.INIT) {
		return 'init';
	} else if ([ConnectionState.RUNNING, ConnectionState.READY].includes(deviceState.value)) {
		return 'ready';
	} else if (deviceState.value === ConnectionState.CONNECTED) {
		return 'connected';
	}

	return 'unknown';
});

const stateColor = computed<StateColor>((): StateColor => {
	if (!wsStatus || [ConnectionState.UNKNOWN].includes(deviceState.value)) {
		return undefined;
	}

	if ([ConnectionState.CONNECTED, ConnectionState.READY, ConnectionState.RUNNING].includes(deviceState.value)) {
		return 'success';
	} else if ([ConnectionState.INIT].includes(deviceState.value)) {
		return 'info';
	} else if ([ConnectionState.DISCONNECTED, ConnectionState.STOPPED, ConnectionState.SLEEPING].includes(deviceState.value)) {
		return 'warning';
	}

	return 'danger';
});
</script>
