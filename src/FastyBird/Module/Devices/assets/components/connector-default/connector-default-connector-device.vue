<template>
	<app-list-item :variant="ListItemVariantTypes.LIST">
		<template #icon>
			<devices-device-icon :device="props.deviceData.device" />
		</template>

		<template #title>
			<el-text :line-clamp="2">
				{{ props.deviceData.device.title }}
			</el-text>
		</template>

		<template #detail>
			<el-button-group>
				<el-button
					:icon="FasCircleInfo"
					size="small"
					plain
					@click="emit('detail', $event)"
				/>

				<el-button
					:icon="FasPencil"
					size="small"
					plain
					@click="emit('edit', $event)"
				/>

				<el-button
					:icon="FasTrash"
					type="warning"
					size="small"
					plain
					@click="emit('remove', $event)"
				/>
			</el-button-group>
		</template>
	</app-list-item>
</template>

<script setup lang="ts">
import { h } from 'vue';
import type { VNode } from 'vue';

import { ElButton, ElButtonGroup, ElText } from 'element-plus';

import { AppListItem, ListItemVariantTypes } from '@fastybird/application';
import { Icon } from '@iconify/vue';

import { IConnectorDeviceEmits, IConnectorDeviceProps } from '../../types';
import DevicesDeviceIcon from '../devices/devices-device-icon.vue';

const FasCircleInfo = (): VNode => h(Icon, { icon: 'fa6-solid:circle-info' });
const FasPencil = (): VNode => h(Icon, { icon: 'fa6-solid:pencil' });
const FasTrash = (): VNode => h(Icon, { icon: 'fa6-solid:trash' });

defineOptions({
	name: 'ConnectorDefaultConnectorDevice',
});

const props = defineProps<IConnectorDeviceProps>();

const emit = defineEmits<IConnectorDeviceEmits>();
</script>
