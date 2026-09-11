<template>
	<component :is="categoryIcon" />
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { Component, VNode } from 'vue';

import { Icon } from '@iconify/vue';

import { DeviceCategory } from '../types';

import { DeviceIconProps } from './device-icon.types';

const FabApple = (): VNode => h(Icon, { icon: 'fa6-brands:apple' });
const FasBell = (): VNode => h(Icon, { icon: 'fa6-solid:bell' });
const FasBridge = (): VNode => h(Icon, { icon: 'fa6-solid:bridge' });
const FasDoorOpen = (): VNode => h(Icon, { icon: 'fa6-solid:door-open' });
const FasDroplet = (): VNode => h(Icon, { icon: 'fa6-solid:droplet' });
const FasFan = (): VNode => h(Icon, { icon: 'fa6-solid:fan' });
const FasFaucet = (): VNode => h(Icon, { icon: 'fa6-solid:faucet' });
const FasFire = (): VNode => h(Icon, { icon: 'fa6-solid:fire' });
const FasGamepad = (): VNode => h(Icon, { icon: 'fa6-solid:gamepad' });
const FasGauge = (): VNode => h(Icon, { icon: 'fa6-solid:gauge' });
const FasKey = (): VNode => h(Icon, { icon: 'fa6-solid:key' });
const FasLightbulb = (): VNode => h(Icon, { icon: 'fa6-solid:lightbulb' });
const FasMicrochip = (): VNode => h(Icon, { icon: 'fa6-solid:microchip' });
const FasPersonBooth = (): VNode => h(Icon, { icon: 'fa6-solid:person-booth' });
const FasPlaneDeparture = (): VNode => h(Icon, { icon: 'fa6-solid:plane-departure' });
const FasPlug = (): VNode => h(Icon, { icon: 'fa6-solid:plug' });
const FasTemperatureLow = (): VNode => h(Icon, { icon: 'fa6-solid:temperature-low' });
const FasTv = (): VNode => h(Icon, { icon: 'fa6-solid:tv' });
const FasVideo = (): VNode => h(Icon, { icon: 'fa6-solid:video' });
const FasVolumeLow = (): VNode => h(Icon, { icon: 'fa6-solid:volume-low' });
const FasWarehouse = (): VNode => h(Icon, { icon: 'fa6-solid:warehouse' });
const FasWind = (): VNode => h(Icon, { icon: 'fa6-solid:wind' });

defineOptions({
	name: 'DeviceIcon',
});

const props = defineProps<DeviceIconProps>();

const categoryIcon = computed<Component>((): Component => {
	if (typeof props.category === 'undefined') {
		return FasMicrochip;
	}

	if ([DeviceCategory.BRIDGE].includes(props.category)) {
		return FasBridge;
	}
	if ([DeviceCategory.FAN].includes(props.category)) {
		return FasFan;
	}
	if ([DeviceCategory.GARAGE_DOOR_OPENER].includes(props.category)) {
		return FasWarehouse;
	}
	if ([DeviceCategory.LIGHT_BULB].includes(props.category)) {
		return FasLightbulb;
	}
	if ([DeviceCategory.DOOR_LOCK].includes(props.category)) {
		return FasKey;
	}
	if ([DeviceCategory.OUTLET, DeviceCategory.SWITCH, DeviceCategory.PROGRAMMABLE_SWITCH].includes(props.category)) {
		return FasPlug;
	}
	if ([DeviceCategory.THERMOSTAT].includes(props.category)) {
		return FasTemperatureLow;
	}
	if ([DeviceCategory.SENSOR].includes(props.category)) {
		return FasGauge;
	}
	if ([DeviceCategory.ALARM_SYSTEM].includes(props.category)) {
		return FasBell;
	}
	if ([DeviceCategory.DOOR, DeviceCategory.WINDOW].includes(props.category)) {
		return FasDoorOpen;
	}
	if ([DeviceCategory.WINDOW_COVERING].includes(props.category)) {
		return FasPersonBooth;
	}
	if ([DeviceCategory.CAMERA, DeviceCategory.VIDEO_DOOR_BELL].includes(props.category)) {
		return FasVideo;
	}
	if ([DeviceCategory.AIR_PURIFIER, DeviceCategory.AIR_CONDITIONER].includes(props.category)) {
		return FasWind;
	}
	if ([DeviceCategory.HEATER].includes(props.category)) {
		return FasFire;
	}
	if ([DeviceCategory.HUMIDIFIER, DeviceCategory.DEHUMIDIFIER].includes(props.category)) {
		return FasDroplet;
	}
	if ([DeviceCategory.APPLE_TV, DeviceCategory.HOMEPOD].includes(props.category)) {
		return FabApple;
	}
	if ([DeviceCategory.SPEAKER, DeviceCategory.AUDIO_RECEIVER].includes(props.category)) {
		return FasVolumeLow;
	}
	if ([DeviceCategory.AIRPORT].includes(props.category)) {
		return FasPlaneDeparture;
	}
	if ([DeviceCategory.SPRINKLER, DeviceCategory.FAUCET, DeviceCategory.SHOWER_HEAD].includes(props.category)) {
		return FasFaucet;
	}
	if ([DeviceCategory.TELEVISION, DeviceCategory.TV_SET_TOP_BOX, DeviceCategory.TV_STREAMING_STICK].includes(props.category)) {
		return FasTv;
	}
	if ([DeviceCategory.TARGET_CONTROLLER].includes(props.category)) {
		return FasGamepad;
	}

	return FasMicrochip;
});
</script>
