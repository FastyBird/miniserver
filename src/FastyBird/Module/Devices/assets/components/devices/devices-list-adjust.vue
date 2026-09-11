<template>
	<app-bar-heading teleport>
		<template #icon>
			<Icon icon="fa6-solid:filter" />
		</template>

		<template #title>
			{{ t('devicesModule.headings.devices.adjustFilters') }}
		</template>

		<template #subtitle>
			{{ t('devicesModule.subHeadings.devices.adjustFilters') }}
		</template>
	</app-bar-heading>

	<div class="flex flex-col h-full w-full overflow-hidden">
		<el-scrollbar class="flex-grow">
			<el-collapse v-model="activeBoxes">
				<el-collapse-item name="plugins">
					<template #title>
						<el-text class="!px-2">
							{{ t('devicesModule.filters.plugins.title') }}
						</el-text>
					</template>
					<el-checkbox-group
						v-model="filters.plugins"
						class="flex flex-col px-4"
					>
						<el-checkbox
							v-for="plugin of props.plugins"
							:key="plugin.type"
							:label="plugin.name"
							:value="plugin.type"
						/>
					</el-checkbox-group>
				</el-collapse-item>
				<el-collapse-item name="connectors">
					<template #title>
						<el-text class="!px-2">
							{{ t('devicesModule.filters.connectors.title') }}
						</el-text>
					</template>
					<el-checkbox-group
						v-model="filters.connectors"
						class="flex flex-col px-4"
					>
						<el-checkbox
							v-for="(connector, index) of props.connectors"
							:key="index"
							:label="connector.title"
							:value="connector.id"
							:disabled="filters.plugins.length > 0 && !filters.plugins.includes(connector.type.type)"
						/>
					</el-checkbox-group>
				</el-collapse-item>
				<el-collapse-item name="state">
					<template #title>
						<el-text class="!px-2">
							{{ t('devicesModule.filters.states.title') }}
						</el-text>
					</template>
					<el-checkbox-group
						v-model="filters.states"
						class="flex flex-col px-4"
					>
						<el-checkbox
							v-for="(state, index) of states"
							:key="index"
							:label="t(`devicesModule.misc.state.${state}`)"
							:value="state"
						/>
					</el-checkbox-group>
				</el-collapse-item>
			</el-collapse>
		</el-scrollbar>

		<div class="px-5 py-2 text-center">
			<el-button
				:icon="FasFilterCircleXmark"
				:disabled="!allowReset"
				@click="onResetFilters"
			>
				{{ t('devicesModule.buttons.reset.title') }}
			</el-button>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, h, reactive, ref, watch } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElCheckbox, ElCheckboxGroup, ElCollapse, ElCollapseItem, ElScrollbar, ElText } from 'element-plus';
import isEqual from 'lodash.isequal';

import { AppBarHeading } from '@fastybird/application';
import { Icon } from '@iconify/vue';

import { ConnectionState, DevicesFilter, IConnector, IDevicesFilter } from '../../types';
import { defaultDevicesFilter } from '../../utilities';

import { IDevicesListAdjustProps } from './devices-list-adjust.types';

const FasFilterCircleXmark = (): VNode => h(Icon, { icon: 'fa6-solid:filter-circle-xmark' });

defineOptions({
	name: 'DevicesListAdjust',
});

const props = defineProps<IDevicesListAdjustProps>();

const emit = defineEmits<{
	(e: 'update:filters', filters: DevicesFilter): void;
}>();

const { t } = useI18n();

const states: ConnectionState[] = [
	ConnectionState.CONNECTED,
	ConnectionState.DISCONNECTED,
	ConnectionState.INIT,
	ConnectionState.READY,
	ConnectionState.RUNNING,
	ConnectionState.SLEEPING,
	ConnectionState.STOPPED,
	ConnectionState.LOST,
	ConnectionState.ALERT,
	ConnectionState.UNKNOWN,
];

const activeBoxes = ref<string[]>(['plugins', 'connectors', 'state']);

const filters = reactive<DevicesFilter>(props.filters);

const allowReset = computed<boolean>((): boolean => {
	return (
		!isEqual(filters.plugins, defaultDevicesFilter.plugins) ||
		!isEqual(filters.connectors, defaultDevicesFilter.connectors) ||
		!isEqual(filters.states, defaultDevicesFilter.states)
	);
});

const onResetFilters = (): void => {
	filters.plugins = defaultDevicesFilter.plugins;
	filters.connectors = defaultDevicesFilter.connectors;
	filters.states = defaultDevicesFilter.states;
};

watch(
	(): IDevicesFilter['plugins'] => filters.plugins,
	(val: IDevicesFilter['plugins']): void => {
		if (val.length > 0) {
			let connectorIds: IConnector['id'][] = [];

			for (const plugin of val) {
				connectorIds = [
					...connectorIds,
					...props.connectors
						.filter((connector: IConnector): boolean => connector.type.type === plugin)
						.map((connector: IConnector): IConnector['id'] => connector.id),
				];
			}

			filters.connectors = filters.connectors.filter((id) => connectorIds.includes(id));
		}

		emit('update:filters', filters);
	}
);

watch(
	(): [IDevicesFilter['connectors'], IDevicesFilter['states']] => [filters.connectors, filters.states],
	(): void => {
		emit('update:filters', filters);
	}
);
</script>
