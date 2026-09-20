<template>
	<el-form
		ref="propertyFormEl"
		:model="propertyForm"
		:rules="rules"
		:label-width="180"
		:label-position="'right'"
		status-icon
	>
		<el-form-item
			:label="t('devicesModule.fields.properties.identifier.title')"
			prop="identifier"
		>
			<el-input
				v-model="propertyForm.identifier"
				name="identifier"
				:readonly="!props.property.draft"
				:disabled="!props.property.draft"
			/>
		</el-form-item>

		<el-form-item
			:label="t('devicesModule.fields.properties.name.title')"
			prop="name"
		>
			<el-input
				v-model="propertyForm.name"
				name="name"
			/>
		</el-form-item>

		<template v-if="props.property.type.type !== PropertyType.MAPPED">
			<el-form-item
				:label="t('devicesModule.fields.properties.dataType.title')"
				prop="dataType"
			>
				<el-select
					v-model="propertyForm.dataType"
					name="dataType"
					:readonly="!props.property.draft"
				>
					<el-option
						v-for="item in dataTypeOptions"
						:key="item.value"
						:label="item.name"
						:value="item.value"
					/>
				</el-select>
			</el-form-item>

			<el-form-item
				:label="t('devicesModule.fields.properties.unit.title')"
				prop="unit"
			>
				<el-input
					v-model="propertyForm.unit"
					name="unit"
				/>
			</el-form-item>

			<template v-if="props.property.type.type === PropertyType.DYNAMIC">
				<el-form-item
					:label="t('devicesModule.fields.properties.settable.title')"
					prop="settable"
				>
					<el-switch
						v-model="propertyForm.settable"
						name="settable"
					/>
				</el-form-item>

				<el-form-item
					:label="t('devicesModule.fields.properties.queryable.title')"
					prop="queryable"
				>
					<el-switch
						v-model="propertyForm.queryable"
						name="queryable"
					/>
				</el-form-item>
			</template>

			<el-form-item
				:label="t('devicesModule.fields.properties.invalid.title')"
				prop="invalid"
			>
				<el-input
					v-model="propertyForm.invalid"
					name="invalid"
				/>
			</el-form-item>

			<el-form-item
				:label="t('devicesModule.fields.properties.scale.title')"
				prop="scale"
			>
				<el-input
					v-model="propertyForm.scale"
					name="scale"
				/>
			</el-form-item>

			<el-form-item
				:label="t('devicesModule.fields.properties.format.title')"
				prop="format"
			>
				<el-input
					v-model="propertyForm.format"
					name="format"
				/>
			</el-form-item>
		</template>
	</el-form>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElForm, ElFormItem, ElInput, ElOption, ElSelect, ElSwitch, FormInstance, FormRules } from 'element-plus';

import { DataType } from '@fastybird/miniserver-core';

import { usePropertyForm } from '../../composables';
import { FormResultType, FormResultTypes, IPropertyForm, PropertyType } from '../../types';

import { IPropertyDefaultPropertySettingsFormProps } from './property-default-property-settings-form.types';

defineOptions({
	name: 'PropertyDefaultPropertySettingsForm',
});

const props = withDefaults(defineProps<IPropertyDefaultPropertySettingsFormProps>(), {
	connector: undefined,
	device: undefined,
	channel: undefined,
	remoteFormSubmit: false,
	remoteFormReset: false,
	remoteFormResult: FormResultTypes.NONE,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultType): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
	(e: 'added'): void;
	(e: 'saved'): void;
}>();

const { t } = useI18n();

const { submit, formResult } = usePropertyForm({
	connector: props.connector,
	device: props.device,
	channel: props.channel,
	property: props.property,
});

const propertyFormEl = ref<FormInstance | undefined>(undefined);

const rules = reactive<FormRules<IPropertyForm>>({
	identifier: [{ type: 'string', required: true, message: t('devicesModule.fields.properties.identifier.validation.required'), trigger: 'change' }],
	name: [{ type: 'string', required: false }],
	settable: [{ type: 'boolean', required: false }],
	queryable: [{ type: 'boolean', required: false }],
	dataType: [
		{ type: 'string', required: true, enum: Object.values(DataType), message: t('devicesModule.fields.properties.dataType.validation.required') },
	],
	unit: [{ type: 'string', required: false }],
	invalid: [{ type: 'string', required: false }],
	scale: [{ type: 'number', required: false }],
	format: [{ type: 'string', required: false }],
});

const propertyForm = reactive<IPropertyForm>({
	identifier: props.property.identifier,
	name: props.property.name,
	settable: props.property.settable,
	queryable: props.property.queryable,
	dataType: props.property.dataType,
	unit: props.property.unit,
	invalid: props.property.invalid as string,
	scale: props.property.scale,
	format: props.property.format ? JSON.stringify(props.property.format) : null,
});

const dataTypeOptions: { name: string; value: string }[] = [
	{
		name: t('devicesModule.fields.properties.dataType.values.unknown'),
		value: DataType.UNKNOWN,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.char'),
		value: DataType.CHAR,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.uchar'),
		value: DataType.UCHAR,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.short'),
		value: DataType.SHORT,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.ushort'),
		value: DataType.USHORT,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.int'),
		value: DataType.INT,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.uint'),
		value: DataType.UINT,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.float'),
		value: DataType.FLOAT,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.bool'),
		value: DataType.BOOLEAN,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.string'),
		value: DataType.STRING,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.enum'),
		value: DataType.ENUM,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.date'),
		value: DataType.DATE,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.time'),
		value: DataType.TIME,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.datetime'),
		value: DataType.DATETIME,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.color'),
		value: DataType.COLOR,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.button'),
		value: DataType.BUTTON,
	},
	{
		name: t('devicesModule.fields.properties.dataType.values.switch'),
		value: DataType.SWITCH,
	},
];

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			await propertyFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				const data = {
					identifier: propertyForm.identifier,
					name: propertyForm.name,
					settable: propertyForm.settable,
					queryable: propertyForm.queryable,
					dataType: propertyForm.dataType,
					unit: propertyForm.unit,
					invalid: propertyForm.invalid,
					scale: propertyForm.scale,
					format: propertyForm.format ? JSON.parse(propertyForm.format) : null,
				};

				submit(data)
					.then((result): void => {
						if (result === 'added') {
							emit('added');
						} else {
							emit('saved');
						}
					})
					.catch((): void => {
						// Something went wrong, property could not be saved
					});
			});
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			propertyForm.identifier = props.property.identifier;
			propertyForm.name = props.property.name ?? null;
			propertyForm.settable = props.property.settable;
			propertyForm.queryable = props.property.queryable;
			propertyForm.dataType = props.property.dataType;
			propertyForm.unit = props.property.unit ?? null;
			propertyForm.invalid = props.property.invalid as null;
			propertyForm.scale = props.property.scale ?? null;
			propertyForm.format = props.property.format ? JSON.stringify(props.property.format) : null;
		}
	}
);

watch(
	(): FormResultType => formResult.value,
	(val: FormResultType): void => {
		emit('update:remoteFormResult', val);
	}
);
</script>
