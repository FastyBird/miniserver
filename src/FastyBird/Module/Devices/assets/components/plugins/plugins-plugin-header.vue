<template>
	<el-page-header
		:class="ns.e('header')"
		class="pt-4"
		@back="emit('back')"
	>
		<template #content>
			<div class="flex items-center">
				<el-avatar
					:icon="FasPlugCircleBolt"
					:src="props.plugin.icon?.small"
					:size="32"
					:alt="props.plugin.name"
					class="mr-3"
				/>

				<span class="text-large font-600 mr-3">
					{{ props.plugin.name }}
					<small
						v-if="!isXXLDevice"
						class="text-sm block font-400 font-size-[65%]"
						style="color: var(--el-text-color-regular)"
					>
						{{ props.plugin.description }}
					</small>
				</span>

				<span
					class="text-sm mr-2 hidden xxl:block"
					style="color: var(--el-text-color-regular)"
				>
					{{ props.plugin.description }}
				</span>

				<el-tag v-if="props.plugin.core">
					{{ t('devicesModule.misc.core') }}
				</el-tag>
			</div>
		</template>

		<template #extra>
			<div class="flex items-center">
				<el-button
					v-if="isXLDevice"
					:icon="FasPlus"
					:class="[{ '!px-4': !isXLDevice }]"
					type="primary"
					class="!px-4"
					@click="emit('addConnector', $event)"
				>
					{{ t('devicesModule.buttons.addInstance.title') }}
				</el-button>
				<el-button
					v-else
					:icon="FasPlus"
					:circle="!isMDDevice"
					:class="[{ '!px-4': isMDDevice }]"
					type="primary"
					@click="emit('addConnector', $event)"
				/>

				<el-button
					v-if="isXXLDevice"
					:icon="FasTrash"
					type="warning"
					class="!px-4"
					plain
					@click="emit('remove', $event)"
				>
					{{ t('devicesModule.buttons.remove.title') }}
				</el-button>
				<el-button
					v-else
					:icon="FasTrash"
					:circle="!isMDDevice"
					:class="[{ '!px-4': isMDDevice }]"
					type="warning"
					plain
					@click="emit('remove', $event)"
				/>
			</div>
		</template>

		<plugins-plugin-stats
			:plugin="props.plugin"
			:connectors-data="props.connectorsData"
			:alerts="props.alerts"
			:bridges="props.bridges"
			class="m-2"
			@devices="emit('devices', $event)"
			@bridges="emit('bridges', $event)"
		/>
	</el-page-header>
</template>

<script setup lang="ts">
import { h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElAvatar, ElButton, ElPageHeader, ElTag, useNamespace } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import PluginsPluginStats from '../plugins/plugins-plugin-stats.vue';

import { IPluginsPluginHeaderProps } from './plugins-plugin-header.types';

const FasPlugCircleBolt = (): VNode => h(Icon, { icon: 'fa6-solid:plug-circle-bolt' });
const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });
const FasTrash = (): VNode => h(Icon, { icon: 'fa6-solid:trash' });

defineOptions({
	name: 'PluginsPluginHeader',
});

const props = defineProps<IPluginsPluginHeaderProps>();

const emit = defineEmits<{
	(e: 'back'): void;
	(e: 'remove', event: Event): void;
	(e: 'devices', event: Event): void;
	(e: 'bridges', event: Event): void;
	(e: 'addConnector', event: Event): void;
}>();

const ns = useNamespace('plugins-plugin-header');
const { t } = useI18n();

const { isMDDevice, isXLDevice, isXXLDevice } = useBreakpoints();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@use 'plugins-plugin-header.scss';
</style>
