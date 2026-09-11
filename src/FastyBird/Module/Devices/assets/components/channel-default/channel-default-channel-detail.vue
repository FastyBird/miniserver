<template>
	<dl class="grid m-0">
		<template
			v-for="property in infoProperties"
			:key="property.id"
		>
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t(`devicesModule.misc.property.channel.${property.identifier}`) }}
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
			{{ t('devicesModule.texts.channels.alerts') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<el-tag
					size="small"
					:type="props.alerts.length === 0 ? 'success' : 'danger'"
				>
					<i18n-t
						keypath="devicesModule.texts.channels.alertsCount"
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

	<fb-list class="flex-grow h-full w-full overflow-hidden">
		<template #title>
			{{ t('devicesModule.headings.channels.properties') }}
		</template>

		<el-scrollbar class="w-full">
			<div
				v-if="noResults"
				class="flex flex-col justify-center h-full w-full"
			>
				<el-result>
					<template #icon>
						<fb-icon-with-child
							:size="50"
							type="primary"
						>
							<template #primary>
								<Icon icon="fa6-solid:lightbulb" />
							</template>
							<template #secondary>
								<Icon icon="fa6-solid:info" />
							</template>
						</fb-icon-with-child>
					</template>

					<template #title>
						<el-text class="block">
							{{ t('devicesModule.texts.devices.noProperties') }}
						</el-text>
					</template>
				</el-result>
			</div>

			<property-default-property
				v-for="property of dynamicProperties"
				:key="property.id"
				:property="property"
				:channel="props.channelData.channel"
			/>
		</el-scrollbar>
	</fb-list>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';

import { ElResult, ElScrollbar, ElTag, ElText } from 'element-plus';

import { FbIconWithChild, FbList } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { ChannelPropertyIdentifier, IChannelDetailProps, IChannelProperty, PropertyType } from '../../types';
import { PropertyDefaultProperty } from '../property-default';

defineOptions({
	name: 'ChannelDefaultChannelDetail',
});

const props = defineProps<IChannelDetailProps>();

const { t } = useI18n();

const dynamicProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property: IChannelProperty): boolean => property.type.type === PropertyType.DYNAMIC);
});

const infoProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property: IChannelProperty): boolean =>
		[ChannelPropertyIdentifier.ADDRESS].includes(property.identifier as ChannelPropertyIdentifier)
	);
});

const noResults = computed<boolean>((): boolean => dynamicProperties.value.length === 0);
</script>
