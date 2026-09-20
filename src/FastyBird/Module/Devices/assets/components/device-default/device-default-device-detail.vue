<template>
	<dl class="grid m-0">
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.devices.channels') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<i18n-t
					keypath="devicesModule.texts.devices.channelsCount"
					:plural="props.deviceData.channels.length"
				>
					<template #count>
						<strong>{{ props.deviceData.channels.length }}</strong>
					</template>
				</i18n-t>
			</el-text>
		</dd>
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.devices.status') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<el-tag
					:type="stateColor"
					size="small"
				>
					{{ t(`devicesModule.misc.state.${deviceState.toLowerCase()}`) }}
				</el-tag>
			</el-text>
		</dd>
		<template
			v-for="property in infoProperties"
			:key="property.id"
		>
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t(`devicesModule.misc.property.device.${property.identifier}`) }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
				<el-text>
					{{ property.value }}
				</el-text>
			</dd>
		</template>
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.devices.bridges') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<i18n-t
					keypath="devicesModule.texts.devices.bridgesCount"
					:plural="props.bridges.length"
				>
					<template #count>
						<strong>{{ props.bridges.length }}</strong>
					</template>
				</i18n-t>
			</el-text>
		</dd>
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.devices.alerts') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<el-tag
					size="small"
					:type="props.alerts.length === 0 ? 'success' : 'danger'"
				>
					<i18n-t
						keypath="devicesModule.texts.devices.alertsCount"
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
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';

import { ElTag, ElText } from 'element-plus';

import { useWampV1Client } from '@fastybird/miniserver-core';

import { useDeviceState } from '../../composables';
import { ConnectionState, DevicePropertyIdentifier, IDeviceDetailProps, IDeviceProperty, PropertyType, StateColor } from '../../types';

defineOptions({
	name: 'DeviceDefaultDeviceDetail',
});

const props = defineProps<IDeviceDetailProps>();

const { t } = useI18n();

const { status: wsStatus } = useWampV1Client();

const { state: deviceState } = useDeviceState(props.deviceData.device);

const dynamicProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return props.deviceData.properties.filter((property: IDeviceProperty): boolean => property.type.type === PropertyType.DYNAMIC);
});

const infoProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return dynamicProperties.value.filter((property: IDeviceProperty): boolean =>
		[
			DevicePropertyIdentifier.BATTERY,
			DevicePropertyIdentifier.WIFI,
			DevicePropertyIdentifier.SIGNAL,
			DevicePropertyIdentifier.RSSI,
			DevicePropertyIdentifier.SSID,
			DevicePropertyIdentifier.VCC,
			DevicePropertyIdentifier.UPTIME,
			DevicePropertyIdentifier.ADDRESS,
			DevicePropertyIdentifier.IP_ADDRESS,
			DevicePropertyIdentifier.DOMAIN,
			DevicePropertyIdentifier.HARDWARE_MANUFACTURER,
			DevicePropertyIdentifier.HARDWARE_MODEL,
			DevicePropertyIdentifier.HARDWARE_VERSION,
			DevicePropertyIdentifier.HARDWARE_MAC_ADDRESS,
			DevicePropertyIdentifier.FIRMWARE_MANUFACTURER,
			DevicePropertyIdentifier.FIRMWARE_NAME,
			DevicePropertyIdentifier.FIRMWARE_VERSION,
			DevicePropertyIdentifier.SERIAL_NUMBER,
		].includes(property.identifier as DevicePropertyIdentifier)
	);
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
