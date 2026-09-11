<template>
	<el-form
		ref="connectorFormEl"
		:model="connectorForm"
		label-position="top"
		status-icon
		class="b-b b-b-solid"
	>
		<h3 class="b-b b-b-solid p-2">
			{{ t('devicesModule.headings.connectors.aboutConnector') }}
		</h3>

		<div class="px-2 md:px-4">
			<connector-default-connector-settings-rename
				v-model="connectorForm.details"
				:connector="props.connectorData.connector"
			/>

			<property-default-variable-properties-edit
				v-if="connectorForm.properties && connectorForm.properties.variable"
				v-model="connectorForm.properties.variable"
				:properties="variableProperties"
				:labels="variablePropertiesLabels"
				@change="onPropertiesChanged"
			/>
		</div>
	</el-form>

	<fb-list>
		<template #title>
			{{ t('devicesModule.headings.connectors.variableProperties') }}
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
			:connector="props.connectorData.connector"
			:property="property"
			:title="get(variablePropertiesLabels, property.id)"
			@edit="emit('editProperty', property.id, $event)"
			@remove="emit('removeProperty', property.id, $event)"
		/>
	</fb-list>

	<fb-list>
		<template #title>
			{{ t('devicesModule.headings.connectors.dynamicProperties') }}
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
			:connector="props.connectorData.connector"
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

import { useConnectorForm } from '../../composables';
import {
	FormResultType,
	FormResultTypes,
	IConnectorForm,
	IConnectorProperty,
	IEditConnectorEmits,
	IEditConnectorProps,
	PropertyType,
} from '../../types';
import ConnectorDefaultConnectorSettingsRename from '../connector-default/connector-default-connector-settings-rename.vue';
import PropertyDefaultPropertySettings from '../property-default/property-default-property-settings.vue';
import PropertyDefaultVariablePropertiesEdit from '../property-default/property-default-variable-properties-edit.vue';

const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'ConnectorDefaultConnectorSettings',
});

const props = withDefaults(defineProps<IEditConnectorProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<IEditConnectorEmits>();

const { t } = useI18n();

const { submit, formResult } = useConnectorForm(props.connectorData.connector);

const connectorFormEl = ref<FormInstance | undefined>(undefined);

const variableProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return props.connectorData.properties.filter((property) => property.type.type === PropertyType.VARIABLE);
});

const dynamicProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return props.connectorData.properties.filter((property) => property.type.type === PropertyType.DYNAMIC);
});

const variablePropertiesLabels = computed<{ [key: IConnectorProperty['id']]: string }>((): { [key: IConnectorProperty['id']]: string } => {
	const labels: { [key: IConnectorProperty['id']]: string } = {};

	for (const property of variableProperties.value) {
		labels[property.id] = t(`devicesModule.misc.property.connector.${property.identifier}`, {}, property.title);
	}

	return labels;
});

const dynamicPropertiesLabels = computed<{ [key: IConnectorProperty['id']]: string }>((): { [key: IConnectorProperty['id']]: string } => {
	const labels: { [key: IConnectorProperty['id']]: string } = {};

	for (const property of dynamicProperties.value) {
		labels[property.id] = t(`devicesModule.misc.property.connector.${property.identifier}`, {}, property.title);
	}

	return labels;
});

const connectorForm = reactive<IConnectorForm>({
	details: {
		identifier: props.connectorData.connector.identifier,
		name: props.connectorData.connector.name,
		comment: props.connectorData.connector.comment,
	},
	properties: {
		variable: Object.fromEntries(variableProperties.value.map((property) => [property.id, property.value])),
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

			connectorFormEl.value!.clearValidate();

			await connectorFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				submit(connectorForm)
					.then((): void => {
						// Connector was saved
					})
					.catch((): void => {
						// Something went wrong, connector could not be saved
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
			connectorForm.details.identifier = props.connectorData.connector.identifier;
			connectorForm.details.name = props.connectorData.connector.name;
			connectorForm.details.comment = props.connectorData.connector.comment;

			connectorForm.properties!.variable = Object.fromEntries(
				variableProperties.value.map((property) => [property.id, flattenValue(property.value)])
			);
		}
	}
);

watch(
	(): IConnectorProperty[] => variableProperties.value,
	(val: IConnectorProperty[]): void => {
		if (!changed.value) {
			connectorForm.properties!.variable = Object.fromEntries(val.map((property) => [property.id, flattenValue(property.value)]));
		}

		connectorFormEl.value!.clearValidate();
	}
);

watch(
	(): FormResultType => formResult.value,
	(val: FormResultType): void => {
		emit('update:remoteFormResult', val);
	}
);
</script>
