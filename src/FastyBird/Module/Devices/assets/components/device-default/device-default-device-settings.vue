<template>
	<el-form
		ref="deviceFormEl"
		:model="deviceForm"
		label-position="top"
		status-icon
		class="b-b b-b-solid"
	>
		<h3 class="b-b b-b-solid p-2">
			{{ t('devicesModule.headings.devices.aboutDevice') }}
		</h3>

		<div class="px-2 md:px-4">
			<device-default-device-settings-rename
				v-model="deviceForm.details"
				:device="props.deviceData.device"
			/>

			<property-default-variable-properties-edit
				v-if="deviceForm.properties && deviceForm.properties.variable"
				v-model="deviceForm.properties.variable"
				:properties="variableProperties"
				:labels="variablePropertiesLabels"
				@change="onPropertiesChanged"
			/>
		</div>
	</el-form>

	<fb-list>
		<template #title>
			{{ t('devicesModule.headings.devices.variableProperties') }}
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
			:device="props.deviceData.device"
			:property="property"
			:title="get(variablePropertiesLabels, property.id)"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>

	<fb-list>
		<template #title>
			{{ t('devicesModule.headings.devices.dynamicProperties') }}
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
			:device="props.deviceData.device"
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

import { useDeviceForm } from '../../composables';
import { FormResultType, FormResultTypes, IDeviceForm, IDeviceProperty, IEditDeviceEmits, IEditDeviceProps, PropertyType } from '../../types';
import DeviceDefaultDeviceSettingsRename from '../device-default/device-default-device-settings-rename.vue';
import PropertyDefaultPropertySettings from '../property-default/property-default-property-settings.vue';
import PropertyDefaultVariablePropertiesEdit from '../property-default/property-default-variable-properties-edit.vue';

const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'DeviceDefaultDeviceSettings',
});

const props = withDefaults(defineProps<IEditDeviceProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<IEditDeviceEmits>();

const { t } = useI18n();

const { submit, formResult } = useDeviceForm(props.deviceData.device);

const deviceFormEl = ref<FormInstance | undefined>(undefined);

const variableProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return props.deviceData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IDeviceProperty[]>((): IDeviceProperty[] => {
	return props.deviceData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const variablePropertiesLabels = computed<{ [key: IDeviceProperty['id']]: string }>((): { [key: IDeviceProperty['id']]: string } => {
	const labels: { [key: IDeviceProperty['id']]: string } = {};

	for (const property of variableProperties.value) {
		labels[property.id] = t(`devicesModule.misc.property.device.${property.identifier}`, {}, property.title);
	}

	return labels;
});

const dynamicPropertiesLabels = computed<{ [key: IDeviceProperty['id']]: string }>((): { [key: IDeviceProperty['id']]: string } => {
	const labels: { [key: IDeviceProperty['id']]: string } = {};

	for (const property of dynamicProperties.value) {
		labels[property.id] = t(`devicesModule.misc.property.device.${property.identifier}`, {}, property.title);
	}

	return labels;
});

const deviceForm = reactive<IDeviceForm>({
	details: {
		identifier: props.deviceData.device.identifier,
		name: props.deviceData.device.name,
		comment: props.deviceData.device.comment,
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

			deviceFormEl.value!.clearValidate();

			await deviceFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				submit(deviceForm)
					.then((): void => {
						// Device was saved
					})
					.catch((): void => {
						// Something went wrong, device could not be saved
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
			deviceForm.details.identifier = props.deviceData.device.identifier;
			deviceForm.details.name = props.deviceData.device.name;
			deviceForm.details.comment = props.deviceData.device.comment;

			deviceForm.properties!.variable = Object.fromEntries(variableProperties.value.map((property) => [property.id, flattenValue(property.value)]));
		}
	}
);

watch(
	(): IDeviceProperty[] => variableProperties.value,
	(val: IDeviceProperty[]): void => {
		if (!changed.value) {
			deviceForm.properties!.variable = Object.fromEntries(val.map((property) => [property.id, flattenValue(property.value)]));
		}

		deviceFormEl.value!.clearValidate();
	}
);

watch(
	(): FormResultType => formResult.value,
	(val: FormResultType): void => {
		emit('update:remoteFormResult', val);
	}
);
</script>
