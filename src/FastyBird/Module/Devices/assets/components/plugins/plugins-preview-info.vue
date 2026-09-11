<template>
	<div class="flex flex-col justify-center h-full">
		<div class="my-5 flex flex-row">
			<div class="flex-none w-20 text-center">
				<el-icon :size="32">
					<Icon icon="fa6-solid:plug-circle-bolt" />
				</el-icon>
			</div>

			<div class="flex-auto">
				<h3 class="font-light">
					{{ t('devicesModule.headings.plugins.allPlugins') }}
				</h3>

				<p>{{ t('devicesModule.subHeadings.plugins.allPlugins', items.length) }}</p>
			</div>
		</div>

		<div class="my-5 flex flex-row">
			<div class="flex-none w-20 text-center">
				<el-icon :size="32">
					<Icon icon="fa6-solid:ethernet" />
				</el-icon>
			</div>

			<div class="flex-auto">
				<h3 class="font-light">
					{{ t('devicesModule.headings.connectors.allConnectors') }}
				</h3>

				<p>{{ t('devicesModule.subHeadings.connectors.allConnectors', connectors) }}</p>
			</div>
		</div>

		<div class="my-5 flex flex-row">
			<div class="flex-none w-20 text-center">
				<el-icon :size="32">
					<fas-plus />
				</el-icon>
			</div>

			<div class="flex-auto">
				<h3 class="font-light">
					{{ t('devicesModule.headings.connectors.new') }}
				</h3>

				<p>{{ t('devicesModule.subHeadings.connectors.new') }}</p>

				<p>
					<el-button
						:icon="FasPlus"
						type="primary"
						plain
						@click="emit('addConnector', $event)"
					>
						{{ t('devicesModule.buttons.addInstance.title') }}
					</el-button>
				</p>
			</div>
		</div>

		<div class="my-5 flex flex-row">
			<div class="flex-none w-20 text-center">
				<el-icon :size="32">
					<Icon icon="fa6-solid:store" />
				</el-icon>
			</div>

			<div class="flex-auto">
				<h3 class="font-light">
					{{ t('devicesModule.headings.plugins.new') }}
				</h3>

				<p>{{ t('devicesModule.subHeadings.plugins.new') }}</p>

				<p>
					<el-button
						:icon="FasPlus"
						type="info"
						plain
						@click="emit('installPlugin', $event)"
					>
						{{ t('devicesModule.buttons.addPlugin.title') }}
					</el-button>
				</p>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElIcon } from 'element-plus';

import { Icon } from '@iconify/vue';

import { IPluginsPreviewInfoProps } from './plugins-preview-info.types';

const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'PluginsPreviewInfo',
});

const props = defineProps<IPluginsPreviewInfoProps>();

const emit = defineEmits<{
	(e: 'installPlugin', event: Event): void;
	(e: 'addConnector', event: Event): void;
}>();

const { t } = useI18n();

const connectors = computed<number>((): number => {
	let cnt = 0;

	for (const item of props.items) {
		cnt += item.connectors.length;
	}

	return cnt;
});
</script>
