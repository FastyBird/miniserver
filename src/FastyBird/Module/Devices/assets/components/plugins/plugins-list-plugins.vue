<template>
	<el-result
		v-if="noResults"
		class="h-full w-full"
	>
		<template #icon>
			<app-icon-with-child
				type="primary"
				:size="50"
			>
				<template #primary>
					<fas-plug-circle-bolt />
				</template>
				<template #secondary>
					<Icon icon="fa6-solid:info" />
				</template>
			</app-icon-with-child>
		</template>

		<template #title>
			{{ t('devicesModule.texts.misc.noPlugins') }}
		</template>
	</el-result>

	<el-scrollbar v-else>
		<fb-swipe :items="plugins">
			<template #default="{ item }">
				<app-list-item
					:variant="ListItemVariantTypes.LIST"
					class="b-r b-r-solid cursor-pointer mr-[-1px]"
					@click="emit('detail', item.type, $event)"
				>
					<template #icon>
						<el-avatar
							:icon="FasPlugCircleBolt"
							:src="item.icon?.small"
							:alt="item.name"
						/>
					</template>

					<template #title>
						{{ item.name }}
					</template>

					<template #subtitle>
						{{ item.description }}
					</template>
				</app-list-item>
			</template>

			<template #right="{ item, close }">
				<div
					:class="ns.e('button')"
					class="flex flex-col items-center justify-center p-5"
					@click="
						close();
						emit('remove', item.type, $event);
					"
				>
					<el-icon>
						<Icon icon="fa6-solid:trash" />
					</el-icon>
				</div>
			</template>
		</fb-swipe>
	</el-scrollbar>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElAvatar, ElIcon, ElResult, ElScrollbar, useNamespace } from 'element-plus';

import { AppIconWithChild, AppListItem, ListItemVariantTypes } from '@fastybird/application';
import { FbSwipe } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { IConnectorPlugin } from '../../types';

import { IPluginsListPluginsProps } from './plugins-list-plugins.types';

const FasPlugCircleBolt = (): VNode => h(Icon, { icon: 'fa6-solid:plug-circle-bolt' });

defineOptions({
	name: 'PluginsListPlugins',
});

const props = defineProps<IPluginsListPluginsProps>();

const emit = defineEmits<{
	(e: 'detail', type: string, event: Event): void;
	(e: 'remove', type: string, event: Event): void;
}>();

const ns = useNamespace('plugins-list-plugins');
const { t } = useI18n();

const plugins = computed<IConnectorPlugin[]>((): IConnectorPlugin[] => {
	return props.items.filter((item) => item.connectors.length > 0).map((item) => item.plugin);
});

const noResults = computed<boolean>((): boolean => plugins.value.length === 0);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@use 'plugins-list-plugins.scss';
</style>
