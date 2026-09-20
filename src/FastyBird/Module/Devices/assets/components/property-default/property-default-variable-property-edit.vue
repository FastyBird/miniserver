<template>
	<el-form-item
		:label="props.label ?? props.property.title"
		:prop="['properties', 'variable', property.id]"
		:rules="props.rules ?? rules"
	>
		<el-switch
			v-if="property.dataType === DataType.BOOLEAN"
			v-model="model as boolean"
			:name="property.id"
			:disabled="props.disabled ?? false"
			:readonly="props.readonly ?? false"
			@change="onValueChange"
		/>

		<el-select
			v-else-if="property.dataType === DataType.ENUM"
			v-model="model"
			:name="property.id"
			:disabled="props.disabled ?? false"
			:readonly="props.readonly ?? false"
			@change="onValueChange"
		>
			<el-option
				v-for="item in props.options ?? options"
				:key="item.value"
				:label="item.label"
				:value="item.value"
			/>
		</el-select>

		<el-date-picker
			v-else-if="property.dataType === DataType.DATE || property.dataType === DataType.DATETIME"
			v-model="model as string"
			:type="property.dataType === DataType.DATETIME ? 'datetime' : 'date'"
			:name="property.id"
			:disabled="props.disabled ?? false"
			:readonly="props.readonly ?? false"
			@change="onValueChange"
		/>

		<el-time-picker
			v-else-if="property.dataType === DataType.TIME"
			v-model="model as string"
			:name="property.id"
			:disabled="props.disabled ?? false"
			:readonly="props.readonly ?? false"
			@change="onValueChange"
		/>

		<el-input-number
			v-else-if="
				[DataType.CHAR, DataType.UCHAR, DataType.SHORT, DataType.USHORT, DataType.INT, DataType.UINT, DataType.FLOAT].includes(
					props.property.dataType
				)
			"
			v-model="model as number"
			:name="property.id"
			:min="minValue ?? undefined"
			:max="maxValue ?? undefined"
			:precision="props.property.scale ?? undefined"
			:step="props.property.step ?? undefined"
			:disabled="props.disabled ?? false"
			:readonly="props.readonly ?? false"
			@change="onValueChange"
		/>

		<el-input
			v-else
			v-model="model as string | number"
			:name="property.id"
			:disabled="props.disabled ?? false"
			:readonly="props.readonly ?? false"
			@change="onValueChange"
		/>
	</el-form-item>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { RuleType } from 'async-validator';
import { ElDatePicker, ElFormItem, ElInput, ElInputNumber, ElOption, ElSelect, ElSwitch, ElTimePicker, FormItemRule } from 'element-plus';

import { DataType } from '@fastybird/miniserver-core';

import { IPropertyDefaultVariablePropertyEditProps } from './property-default-variable-property-edit.types';

defineOptions({
	name: 'PropertyDefaultVariablePropertyEdit',
});

const props = defineProps<IPropertyDefaultVariablePropertyEditProps>();

const emit = defineEmits<{
	(e: 'update:modelValue', model: string | number | boolean | Date): void;
	(e: 'change', value: string | number | boolean | Date): void;
}>();

const { t } = useI18n();

const isNumber = ref<boolean>(
	[DataType.CHAR, DataType.UCHAR, DataType.SHORT, DataType.USHORT, DataType.INT, DataType.UINT, DataType.FLOAT].includes(props.property.dataType)
);

const model = ref<string | number | boolean | Date>(
	props.modelValue !== null && typeof props.modelValue !== 'undefined' ? props.modelValue : isNumber.value ? 0 : ''
);

const minValue = computed<number | null>((): number | null => {
	if (
		![DataType.CHAR, DataType.UCHAR, DataType.SHORT, DataType.USHORT, DataType.INT, DataType.UINT, DataType.FLOAT].includes(props.property.dataType)
	) {
		return null;
	}

	let min: number | null = null;

	if ([DataType.UCHAR, DataType.USHORT, DataType.UINT].includes(props.property.dataType)) {
		min = 0;
	}

	if (Array.isArray(props.property.format) && props.property.format.length === 2) {
		min = Number.isFinite(props.property.format[0]) ? (props.property.format[0] as number) : null;
	}

	return min;
});

const maxValue = computed<number | null>((): number | null => {
	if (
		![DataType.CHAR, DataType.UCHAR, DataType.SHORT, DataType.USHORT, DataType.INT, DataType.UINT, DataType.FLOAT].includes(props.property.dataType)
	) {
		return null;
	}

	let max: number | null = null;

	if (Array.isArray(props.property.format) && props.property.format.length === 2) {
		max = Number.isFinite(props.property.format[1]) ? (props.property.format[1] as number) : null;
	}

	return max;
});

const rules = computed<FormItemRule[]>((): FormItemRule[] => {
	const items = [];

	const isRequired = props.property.default === null;

	if (
		[DataType.CHAR, DataType.UCHAR, DataType.SHORT, DataType.USHORT, DataType.INT, DataType.UINT, DataType.FLOAT].includes(props.property.dataType)
	) {
		items.push({
			type: (props.property.dataType === DataType.FLOAT ? 'float' : 'integer') as RuleType,
			required: false,
			min: minValue.value ?? undefined,
			max: maxValue.value ?? undefined,
			message: t('devicesModule.fields.properties.value.validation.number'),
			trigger: 'change',
		});
	}

	if ([DataType.ENUM].includes(props.property.dataType)) {
		items.push({
			type: 'enum' as RuleType,
			required: isRequired,
			enum: options.value.map((item) => item.value),
			message: t('devicesModule.fields.properties.value.validation.enum'),
		});
	}

	if ([DataType.DATETIME, DataType.DATE].includes(props.property.dataType)) {
		items.push({
			type: 'date' as RuleType,
			required: isRequired,
			message:
				props.property.dataType === DataType.DATETIME
					? t('devicesModule.fields.properties.value.validation.date')
					: t('devicesModule.fields.properties.value.validation.datetime'),
			trigger: 'change',
		});
	}

	if ([DataType.BUTTON, DataType.SWITCH, DataType.COVER].includes(props.property.dataType)) {
		items.push({
			type: 'enum' as RuleType,
			required: isRequired,
			enum: options.value.map((item) => item.value),
			message: t('devicesModule.fields.properties.value.validation.enum'),
		});
	}

	if ([DataType.STRING].includes(props.property.dataType)) {
		items.push({
			type: 'string' as RuleType,
			required: isRequired,
			message: t('devicesModule.fields.properties.value.validation.required'),
			trigger: 'change',
		});
	}

	return items;
});

const options = computed<{ label: string; value: string }[]>((): { label: string; value: string }[] => {
	if (!Array.isArray(props.property.format)) {
		return [];
	}

	return props.property.format.map((row) => {
		const item = Array.isArray(row) ? row[0] : row;

		return {
			label: `${item}`,
			value: `${item}`,
		};
	});
});

const onValueChange = (): void => {
	emit('change', model.value);
};

watch(
	(): string | number | boolean | Date => model.value,
	(val: string | number | boolean | Date): void => {
		emit('update:modelValue', val);
	},
	{ deep: true }
);
</script>
