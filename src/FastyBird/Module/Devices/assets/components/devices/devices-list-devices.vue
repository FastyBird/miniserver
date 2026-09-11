<template>
	<div class="h-full w-full flex flex-col">
		<div
			:class="[ns.e('header')]"
			class="px-2 py-4 flex justify-between b-b b-b-solid"
		>
			<el-input
				v-model="filters.search"
				:placeholder="t('devicesModule.fields.devices.search.placeholder')"
				class="max-w[280px]"
				clearable
				@blur="onFilterSearch"
			>
				<template #suffix>
					<el-icon><Icon icon="fa6-solid:magnifying-glass" /></el-icon>
				</template>
			</el-input>

			<el-space>
				<el-radio-group
					v-model="filters.state"
					@change="onFilterState"
				>
					<el-radio-button
						:label="t('devicesModule.states.online')"
						value="online"
					/>
					<el-radio-button
						:label="t('devicesModule.states.offline')"
						value="offline"
					/>
					<el-radio-button
						:label="t('devicesModule.states.all')"
						value="all"
					/>
				</el-radio-group>

				<el-button
					:icon="FasSliders"
					link
					@click="emit('adjust', $event)"
				/>
			</el-space>
		</div>

		<el-table
			:data="props.items"
			:class="[ns.e('table')]"
			:default-sort="{ prop: 'title', order: sortDir }"
			table-layout="fixed"
			class="flex-grow b-b-none"
			@sort-change="onSortData"
		>
			<template #empty>
				<div
					v-if="noResults && !props.loading"
					class="h-full w-full"
				>
					<el-result class="h-full w-full">
						<template #icon>
							<fb-icon-with-child
								type="primary"
								:size="50"
							>
								<template #primary>
									<Icon icon="fa6-solid:plug" />
								</template>
								<template #secondary>
									<Icon icon="fa6-solid:info" />
								</template>
							</fb-icon-with-child>
						</template>

						<template #title>
							{{ t('devicesModule.texts.misc.noDevices') }}
						</template>
					</el-result>
				</div>

				<div
					v-else-if="noFilteredResults && !props.loading"
					class="h-full w-full"
				>
					<el-result class="h-full w-full">
						<template #icon>
							<fb-icon-with-child
								type="primary"
								:size="50"
							>
								<template #primary>
									<Icon icon="fa6-solid:plug" />
								</template>
								<template #secondary>
									<Icon icon="fa6-solid:filter" />
								</template>
							</fb-icon-with-child>
						</template>

						<template #title>
							<el-text class="block">
								{{ t('devicesModule.texts.misc.noFilteredDevices') }}
							</el-text>

							<el-button
								:icon="FasFilterCircleXmark"
								type="primary"
								class="mt-4"
								@click="emit('resetFilters', $event)"
							>
								{{ t('devicesModule.buttons.resetFilters.title') }}
							</el-button>
						</template>
					</el-result>
				</div>
			</template>

			<el-table-column
				label="Connector"
				width="100"
				align="center"
			>
				<template #default="scope">
					<el-avatar v-if="scope.row.connector">
						<connectors-connector-icon
							:connector="scope.row.connector"
							:size="28"
						/>
					</el-avatar>
				</template>
			</el-table-column>

			<el-table-column
				label="Name"
				prop="title"
				sortable="custom"
				:sort-orders="['ascending', 'descending']"
			>
				<template #default="scope">
					<template v-if="scope.row.device.hasComment">
						<strong>{{ scope.row.device.title }}</strong>
						<el-text
							v-if="scope.row.device.hasComment"
							size="small"
							class="block"
						>
							{{ scope.row.device.comment }}
						</el-text>
					</template>
					<template v-else>
						{{ scope.row.device.title }}
					</template>
				</template>
			</el-table-column>

			<el-table-column label="State">
				<template #default="scope">
					<devices-list-devices-column-state :device="scope.row.device" />
				</template>
			</el-table-column>

			<el-table-column
				width="200"
				align="center"
			>
				<template #default="scope">
					<el-button-group>
						<el-button
							size="small"
							@click="emit('detail', scope.row.device.id, $event)"
						>
							{{ t('devicesModule.buttons.detail.title') }}
						</el-button>
						<el-button
							size="small"
							@click="emit('edit', scope.row.device.id, $event)"
						>
							{{ t('devicesModule.buttons.edit.title') }}
						</el-button>
						<el-button
							size="small"
							type="danger"
							@click="emit('remove', scope.row.device.id, $event)"
						>
							{{ t('devicesModule.buttons.remove.title') }}
						</el-button>
					</el-button-group>
				</template>
			</el-table-column>
		</el-table>

		<div class="px-2 py-4 flex justify-center b-t b-t-solid">
			<el-pagination
				v-model:current-page="paginatePage"
				v-model:page-size="paginateSize"
				layout="total, sizes, prev, pager, next, jumper"
				:total="props.allItems.length"
				@size-change="onPaginatePageSize"
				@current-change="onPaginatePage"
			/>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, h, reactive, ref } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import {
	ElAvatar,
	ElButton,
	ElButtonGroup,
	ElIcon,
	ElInput,
	ElPagination,
	ElRadioButton,
	ElRadioGroup,
	ElResult,
	ElSpace,
	ElTable,
	ElTableColumn,
	ElText,
	useNamespace,
} from 'element-plus';

import { FbIconWithChild } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { DevicesFilter } from '../../types';
import ConnectorsConnectorIcon from '../connectors/connectors-connector-icon.vue';
import DevicesListDevicesColumnState from '../devices/devices-list-devices-column-state.vue';

import { IDevicesListDevicesProps } from './devices-list-devices.types';

const FasFilterCircleXmark = (): VNode => h(Icon, { icon: 'fa6-solid:filter-circle-xmark' });
const FasSliders = (): VNode => h(Icon, { icon: 'fa6-solid:sliders' });

defineOptions({
	name: 'DevicesListDevices',
});

const props = defineProps<IDevicesListDevicesProps>();

const emit = defineEmits<{
	(e: 'detail', id: string, event: Event): void;
	(e: 'edit', id: string, event: Event): void;
	(e: 'remove', id: string, event: Event): void;
	(e: 'adjust', event: Event): void;
	(e: 'resetFilters', event: Event): void;
	(e: 'update:filters', filters: DevicesFilter): void;
	(e: 'update:paginateSize', size: number): void;
	(e: 'update:paginatePage', page: number): void;
	(e: 'update:sortDir', dir: 'asc' | 'desc'): void;
}>();

const ns = useNamespace('devices-list-devices');
const { t } = useI18n();

const noResults = computed<boolean>((): boolean => props.totalRows === 0);

const noFilteredResults = computed<boolean>((): boolean => props.totalRows > 0 && props.items.length === 0);

const filters = reactive<DevicesFilter>(props.filters);

const paginatePage = ref<number>(props.paginatePage);

const paginateSize = ref<number>(props.paginateSize);

const sortDir = ref<'ascending' | 'descending'>(props.sortDir === 'asc' ? 'ascending' : 'descending');

const onFilterSearch = (): void => {
	emit('update:filters', filters);
};

const onFilterState = (): void => {
	emit('update:filters', filters);
};

const onPaginatePageSize = (size: number): void => {
	emit('update:paginateSize', size);
};

const onPaginatePage = (page: number): void => {
	emit('update:paginatePage', page);
};

const onSortData = ({ order }: { order: 'ascending' | 'descending' | null }): void => {
	emit('update:sortDir', order === 'descending' ? 'desc' : 'asc');
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@use 'devices-list-devices.scss';
</style>
