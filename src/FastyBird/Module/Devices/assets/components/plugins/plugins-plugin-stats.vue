<template>
	<el-row
		:justify="'center'"
		:align="'middle'"
	>
		<el-col
			v-if="isSMDevice"
			:xl="6"
			:lg="5"
			class="text-center content-center md:mb-2 xs:mb-2 xl:mb-0"
		>
			<el-avatar
				:icon="PluginsPluginDefaultIcon"
				:src="props.plugin.icon?.small"
				:size="150"
				:alt="props.plugin.name"
				shape="square"
			/>
		</el-col>

		<el-col
			:xl="18"
			:lg="24"
		>
			<el-row
				:gutter="5"
				:justify="'center'"
				:align="'middle'"
			>
				<el-col :md="12">
					<dl
						:class="ns.e('description')"
						class="b-t b-t-solid md:b-l md:b-l-solid md:b-r md:b-r-solid grid m-0"
					>
						<dt
							class="b-b b-b-solid b-r b-r-solid py-2 px-2 text-center"
							style="background: var(--el-fill-color-light)"
						>
							<el-avatar
								:icon="FasEthernet"
								:alt="t('devicesModule.texts.plugins.instancesCountAlt')"
							/>
						</dt>
						<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
							<el-text>
								<i18n-t
									keypath="devicesModule.texts.plugins.instancesCount"
									:plural="connectorsCount"
								>
									<template #count>
										<strong>{{ connectorsCount }}</strong>
									</template>
								</i18n-t>
							</el-text>
						</dd>
						<dt
							class="b-b b-b-solid b-r b-r-solid py-2 px-2 text-center"
							style="background: var(--el-fill-color-light)"
						>
							<el-avatar
								:icon="FasPlug"
								:alt="t('devicesModule.texts.plugins.devicesCountAlt')"
							/>
						</dt>
						<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
							<el-text>
								<i18n-t
									keypath="devicesModule.texts.plugins.devicesCount"
									:plural="devicesCount"
								>
									<template #count>
										<strong>{{ devicesCount }}</strong>
									</template>
								</i18n-t>

								<el-button
									size="small"
									class="ml-2"
									@click="emit('devices', $event)"
								>
									{{ t('devicesModule.buttons.show.title') }}
								</el-button>
							</el-text>
						</dd>
						<dt
							class="md:b-b md:b-b-solid b-r b-r-solid py-2 px-2 text-center"
							style="background: var(--el-fill-color-light)"
						>
							<el-avatar
								:icon="FasBridge"
								:alt="t('devicesModule.texts.plugins.bridgesCountAlt')"
							/>
						</dt>
						<dd class="col-start-2 md:b-b md:b-b-solid m-0 p-2 flex items-center">
							<el-text>
								<i18n-t
									keypath="devicesModule.texts.plugins.bridgesCount"
									:plural="props.bridges.length"
								>
									<template #count>
										<strong>{{ props.bridges.length }}</strong>
									</template>
								</i18n-t>

								<el-popover
									:width="240"
									placement="top-start"
									trigger="hover"
								>
									<template #reference>
										<el-icon class="ml-1">
											<Icon icon="fa6-solid:circle-info" />
										</el-icon>
									</template>

									<template #default>
										<div class="flex flex-row items-center">
											<el-icon class="mr-2">
												<fas-bridge />
											</el-icon>
											<h2 class="font-size-[1rem] font-500 m-0 p-0 flex-grow">
												{{ t('devicesModule.headings.misc.bridges') }}
											</h2>
										</div>
										<p>
											{{ t('devicesModule.texts.plugins.bridgesInfo') }}
										</p>
									</template>
								</el-popover>
								<el-button
									size="small"
									class="ml-2"
									disabled
									@click="emit('bridges', $event)"
								>
									{{ t('devicesModule.buttons.show.title') }}
								</el-button>
							</el-text>
						</dd>
					</dl>
				</el-col>

				<el-col :md="12">
					<dl
						:class="ns.e('description')"
						class="b-t b-t-solid md:b-l md:b-l-solid md:b-r md:b-r-solid grid m-0"
					>
						<dt
							class="b-b b-b-solid b-r b-r-solid py-2 px-2 text-center"
							style="background: var(--el-fill-color-light)"
						>
							<el-avatar
								:icon="FasBookOpen"
								:alt="t('devicesModule.texts.plugins.documentationAlt')"
							/>
						</dt>
						<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
							<el-link target="_blank">
								{{ t('devicesModule.texts.plugins.documentation') }}
								<el-icon class="el-icon--right">
									<Icon icon="fa6-solid:arrow-up-right-from-square" />
								</el-icon>
							</el-link>
						</dd>
						<dt
							class="b-b b-b-solid b-r b-r-solid py-2 px-2 text-center"
							style="background: var(--el-fill-color-light)"
						>
							<el-avatar
								:icon="FasLaptopCode"
								:alt="t('devicesModule.texts.plugins.developersGuidesAlt')"
							/>
						</dt>
						<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
							<el-link target="_blank">
								{{ t('devicesModule.texts.plugins.developersGuides') }}
								<el-icon class="el-icon--right">
									<Icon icon="fa6-solid:arrow-up-right-from-square" />
								</el-icon>
							</el-link>
						</dd>
						<dt
							class="b-b b-b-solid b-r b-r-solid py-2 px-2 text-center"
							style="background: var(--el-fill-color-light)"
						>
							<el-avatar
								:icon="FasBug"
								:alt="t('devicesModule.texts.plugins.knownIssuesAlt')"
							/>
						</dt>
						<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center">
							<el-link target="_blank">
								{{ t('devicesModule.texts.plugins.knownIssues') }}
								<el-icon class="el-icon--right">
									<Icon icon="fa6-solid:arrow-up-right-from-square" />
								</el-icon>
							</el-link>
						</dd>
					</dl>
				</el-col>
			</el-row>
		</el-col>
	</el-row>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';

import { ElAvatar, ElButton, ElCol, ElIcon, ElLink, ElPopover, ElRow, ElText, useNamespace } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import PluginsPluginDefaultIcon from '../plugins/plugins-plugin-default-icon.vue';

import { IPluginsPluginStatsProps } from './plugins-plugin-stats.types';

const FasBookOpen = (): VNode => h(Icon, { icon: 'fa6-solid:book-open' });
const FasBridge = (): VNode => h(Icon, { icon: 'fa6-solid:bridge' });
const FasBug = (): VNode => h(Icon, { icon: 'fa6-solid:bug' });
const FasEthernet = (): VNode => h(Icon, { icon: 'fa6-solid:ethernet' });
const FasLaptopCode = (): VNode => h(Icon, { icon: 'fa6-solid:laptop-code' });
const FasPlug = (): VNode => h(Icon, { icon: 'fa6-solid:plug' });

defineOptions({
	name: 'PluginsPluginStats',
});

const props = defineProps<IPluginsPluginStatsProps>();

const emit = defineEmits<{
	(e: 'devices', event: Event): void;
	(e: 'bridges', event: Event): void;
}>();

const ns = useNamespace('plugins-plugin-stats');
const { t } = useI18n();

const { isSMDevice } = useBreakpoints();

const connectorsCount = computed<number>((): number => {
	return props.connectorsData.length;
});

const devicesCount = computed<number>((): number => {
	let cnt = 0;

	props.connectorsData.forEach((connector) => {
		cnt += connector.devices.length;
	});

	return cnt;
});
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@use 'plugins-plugin-stats';
</style>
