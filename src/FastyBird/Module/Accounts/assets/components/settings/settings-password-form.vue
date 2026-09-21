<template>
	<el-form
		ref="passwordFormEl"
		:model="passwordForm"
		:rules="rules"
		:label-position="props.layout === LayoutTypes.PHONE ? 'top' : 'right'"
		:label-width="180"
		status-icon
		class="sm:px-5"
	>
		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.password.current.title')"
				prop="currentPassword"
				class="mb-2"
			>
				<el-input
					v-model="passwordForm.currentPassword"
					name="currentPassword"
					type="password"
					show-password
				/>
			</el-form-item>
		</div>

		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.password.new.title')"
				prop="newPassword"
				class="mb-2"
			>
				<el-input
					v-model="passwordForm.newPassword"
					name="newPassword"
					type="password"
					show-password
				/>
			</el-form-item>
		</div>

		<div class="mb-5">
			<el-form-item
				:label="t('accountsModule.fields.password.repeat.title')"
				prop="repeatPassword"
				class="mb-2"
			>
				<el-input
					v-model="passwordForm.repeatPassword"
					name="repeatPassword"
					type="password"
					show-password
				/>
			</el-form-item>
		</div>
	</el-form>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { InternalRuleItem, SyncValidateResult } from 'async-validator';
import { ElForm, ElFormItem, ElInput, FormInstance, FormRules } from 'element-plus';
import get from 'lodash.get';

import { injectStoresManager, useFlashMessage } from '@fastybird/miniserver-core';

import { accountStoreKey } from '../../configuration';
import { FormResultType, FormResultTypes, LayoutTypes } from '../../types';

import { ISettingsPasswordForm, ISettingsPasswordProps } from './settings-password-form.types';

defineOptions({
	name: 'SettingsPasswordForm',
});

const props = withDefaults(defineProps<ISettingsPasswordProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
	layout: LayoutTypes.DEFAULT,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultType): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const storesManager = injectStoresManager();

const accountStore = storesManager.getStore(accountStoreKey);

const passwordFormEl = ref<FormInstance | undefined>(undefined);

const rules = reactive<FormRules<ISettingsPasswordForm>>({
	currentPassword: [{ required: true, message: t('accountsModule.fields.password.current.validation.required'), trigger: 'change' }],
	newPassword: [{ required: true, message: t('accountsModule.fields.password.new.validation.required'), trigger: 'change' }],
	repeatPassword: [
		{ required: true, message: t('accountsModule.fields.password.repeat.validation.required'), trigger: 'change' },
		{
			validator: (_rule: InternalRuleItem, value: any): SyncValidateResult | void => {
				return value === passwordForm.newPassword;
			},
			message: t('accountsModule.fields.password.repeat.validation.different'),
			trigger: 'change',
		},
	],
});

const passwordForm = reactive<ISettingsPasswordForm>({
	currentPassword: '',
	newPassword: '',
	repeatPassword: '',
});

let timer: number;

const clearResult = (): void => {
	window.clearTimeout(timer);

	emit('update:remoteFormResult', FormResultTypes.NONE);
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val): Promise<void> => {
		if (val) {
			emit('update:remoteFormSubmit', false);

			passwordFormEl.value!.clearValidate();

			await passwordFormEl.value!.validate(async (valid: boolean): Promise<void> => {
				if (!valid) {
					return;
				}

				emit('update:remoteFormResult', FormResultTypes.WORKING);

				const errorMessage = t('accountsModule.messages.passwordNotEdited');

				try {
					await accountStore.editIdentity({
						id: props.identity.id,
						data: {
							password: {
								current: passwordForm.currentPassword,
								new: passwordForm.newPassword,
							},
						},
					});

					emit('update:remoteFormResult', FormResultTypes.OK);

					flashMessage.success('Your password has been updated successfully.');

					timer = window.setTimeout(clearResult, 2000);
				} catch (e: any) {
					if (get(e, 'exception', null) !== null) {
						flashMessage.exception(e.exception, errorMessage);
					} else {
						flashMessage.error(errorMessage);
					}

					emit('update:remoteFormResult', FormResultTypes.ERROR);

					timer = window.setTimeout(clearResult, 2000);
				}
			});
		}
	}
);

watch(
	(): boolean => props.remoteFormReset,
	(val: boolean): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			passwordFormEl.value?.resetFields();

			passwordForm.currentPassword = '';
			passwordForm.newPassword = '';
			passwordForm.repeatPassword = '';
		}
	}
);
</script>
