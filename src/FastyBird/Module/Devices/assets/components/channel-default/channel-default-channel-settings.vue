<template>
	<el-form
		ref="channelFormEl"
		:model="channelForm"
		label-position="top"
		status-icon
		class="b-b b-b-solid"
	>
		<h3 class="b-b b-b-solid p-2">
			{{ t('devicesModule.headings.channels.aboutChannel') }}
		</h3>

		<div class="px-2 md:px-4">
			<channel-default-channel-settings-rename
				v-model="channelForm.details"
				:channel="props.channelData.channel"
			/>

			<property-default-variable-properties-edit
				v-if="channelForm.properties && channelForm.properties.variable"
				v-model="channelForm.properties.variable"
				:properties="variableProperties"
				:labels="variablePropertiesLabels"
				@change="onPropertiesChanged"
			/>
		</div>
	</el-form>

	<fb-list>
		<template #title>
			{{ t('devicesModule.headings.channels.variableProperties') }}
		</template>

		<template
			v-if="variableProperties.length > 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('addProperty', PropertyType.VARIABLE, $event)"
			>
				{{ t('devicesModule.buttons.addProperty.title') }}
			</el-button>
		</template>

		<div
			v-if="variableProperties.length === 0"
			class="p-2"
		>
			<el-button
				:icon="FasPlus"
				size="large"
				class="w-full"
				@click="emit('addProperty', PropertyType.VARIABLE, $event)"
			>
				<span>{{ t('devicesModule.buttons.addProperty.title') }}</span>
			</el-button>
		</div>

		<property-default-property-settings
			v-for="property in variableProperties"
			:key="property.id"
			:channel="props.channelData.channel"
			:property="property"
			:title="get(variablePropertiesLabels, property.id)"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>

	<fb-list>
		<template #title>
			{{ t('devicesModule.headings.channels.dynamicProperties') }}
		</template>

		<template
			v-if="dynamicProperties.length > 0"
			#buttons
		>
			<el-button
				:icon="FasPlus"
				type="primary"
				size="small"
				plain
				@click="emit('addProperty', PropertyType.DYNAMIC, $event)"
			>
				{{ t('devicesModule.buttons.addProperty.title') }}
			</el-button>
		</template>

		<div
			v-if="dynamicProperties.length === 0"
			class="p-2"
		>
			<el-button
				:icon="FasPlus"
				size="large"
				class="w-full"
				@click="emit('addProperty', PropertyType.DYNAMIC, $event)"
			>
				<span>{{ t('devicesModule.buttons.addProperty.title') }}</span>
			</el-button>
		</div>

		<property-default-property-settings
			v-for="property in dynamicProperties"
			:key="property.id"
			:channel="props.channelData.channel"
			:property="property"
			:title="get(dynamicPropertiesLabels, property.id)"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>
</template>

<script setup lang="ts">
import { computed, h, reactive, ref, watch } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElForm, FormInstance } from 'element-plus';
import get from 'lodash.get';

import { flattenValue } from '@fastybird/tools';
import { FbList } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { useChannelForm } from '../../composables';
import { FormResultType, FormResultTypes, IChannelForm, IChannelProperty, IEditChannelEmits, IEditChannelProps, PropertyType } from '../../types';
import ChannelDefaultChannelSettingsRename from '../channel-default/channel-default-channel-settings-rename.vue';
import PropertyDefaultPropertySettings from '../property-default/property-default-property-settings.vue';
import PropertyDefaultVariablePropertiesEdit from '../property-default/property-default-variable-properties-edit.vue';

const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'ChannelDefaultChannelSettings',
});

const props = withDefaults(defineProps<IEditChannelProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<IEditChannelEmits>();

const { t } = useI18n();

const { submit, formResult } = useChannelForm(props.channelData.channel);

const channelFormEl = ref<FormInstance | undefined>(undefined);

const variableProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IChannelProperty[]>((): IChannelProperty[] => {
	return props.channelData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const variablePropertiesLabels = computed<{ [key: IChannelProperty['id']]: string }>((): { [key: IChannelProperty['id']]: string } => {
	const labels: { [key: IChannelProperty['id']]: string } = {};

	for (const property of variableProperties.value) {
		labels[property.id] = t(`devicesModule.misc.property.channel.${property.identifier}`, {}, property.title);
	}

	return labels;
});

const dynamicPropertiesLabels = computed<{ [key: IChannelProperty['id']]: string }>((): { [key: IChannelProperty['id']]: string } => {
	const labels: { [key: IChannelProperty['id']]: string } = {};

	for (const property of dynamicProperties.value) {
		labels[property.id] = t(`devicesModule.misc.property.channel.${property.identifier}`, {}, property.title);
	}

	return labels;
});

const channelForm = reactive<IChannelForm>({
	details: {
		identifier: props.channelData.channel.identifier,
		name: props.channelData.channel.name,
		comment: props.channelData.channel.comment,
	},
	properties: {
		variable: Object.fromEntries(variableProperties.value.map((property) => [property.id, flattenValue(property.value)])),
	},
});

const changed = ref<boolean>(false);

const onPropertiesChanged = (): void => {
	changed.value = true;
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			channelFormEl.value!.clearValidate();

			await channelFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				submit(channelForm)
					.then((): void => {
						// Channel was saved
					})
					.catch((): void => {
						// Something went wrong, channel could not be saved
					});
			});
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val: boolean): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			channelForm.details.identifier = props.channelData.channel.identifier;
			channelForm.details.name = props.channelData.channel.name;
			channelForm.details.comment = props.channelData.channel.comment;

			channelForm.properties!.variable = Object.fromEntries(variableProperties.value.map((property) => [property.id, flattenValue(property.value)]));
		}
	}
);

watch(
	(): IChannelProperty[] => variableProperties.value,
	(val: IChannelProperty[]): void => {
		if (!changed.value) {
			channelForm.properties!.variable = Object.fromEntries(val.map((property) => [property.id, flattenValue(property.value)]));
		}

		channelFormEl.value!.clearValidate();
	}
);

watch(
	(): FormResultType => formResult.value,
	(val: FormResultType): void => {
		emit('update:remoteFormResult', val);
	}
);
</script>
