<template>
	<el-form
		ref="signFormEl"
		:model="signForm"
		:rules="rules"
		label-position="top"
		status-icon
		class="px-5"
		@submit.prevent="onSubmit"
	>
		<el-form-item
			:label="t('accountsModule.fields.identity.uid.title')"
			prop="uid"
			class="mb-5"
		>
			<el-input
				v-model="signForm.uid"
				name="uid"
			/>
		</el-form-item>

		<el-form-item
			:label="t('accountsModule.fields.identity.password.title')"
			prop="password"
			class="mb-5"
		>
			<el-input
				v-model="signForm.password"
				type="password"
				name="password"
				show-password
			/>
		</el-form-item>

		<el-checkbox
			v-model="signForm.persistent"
			:label="t('accountsModule.fields.persistent.title')"
			name="persistent"
			class="mb-10"
		/>

		<el-button
			type="primary"
			size="large"
			class="block w-full"
			@click="onSubmit(signFormEl)"
		>
			{{ t('accountsModule.buttons.signIn.title') }}
		</el-button>
	</el-form>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElCheckbox, ElForm, ElFormItem, ElInput, FormInstance, FormRules } from 'element-plus';
import get from 'lodash.get';

import { injectStoresManager, useFlashMessage } from '@fastybird/miniserver-core';

import { sessionStoreKey } from '../../configuration';
import { FormResultType, FormResultTypes } from '../../types';

import { ISignInForm, ISignInProps } from './sign-in-form.types';

defineOptions({
	name: 'SignInForm',
});

const props = withDefaults(defineProps<ISignInProps>(), {
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormResult', remoteFormResult: FormResultType): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const storesManager = injectStoresManager();

const sessionStore = storesManager.getStore(sessionStoreKey);

const signFormEl = ref<FormInstance | undefined>(undefined);

const rules = reactive<FormRules<ISignInForm>>({
	uid: [{ required: true, message: t('accountsModule.fields.identity.uid.validation.required'), trigger: 'change' }],
	password: [{ required: true, message: t('accountsModule.fields.identity.password.validation.required'), trigger: 'change' }],
});

const signForm = reactive<ISignInForm>({
	uid: '',
	password: '',
	persistent: false,
});

const onSubmit = async (formEl: FormInstance | undefined): Promise<void> => {
	if (!formEl) return;

	await formEl.validate(async (valid: boolean): Promise<void> => {
		if (valid) {
			emit('update:remoteFormResult', FormResultTypes.WORKING);

			try {
				await sessionStore.create({ uid: signForm.uid, password: signForm.password });

				emit('update:remoteFormResult', FormResultTypes.OK);
			} catch (e: any) {
				emit('update:remoteFormResult', FormResultTypes.ERROR);

				const errorMessage = t('accountsModule.messages.requestError');

				if (get(e, 'exception', null) !== null) {
					flashMessage.exception(e.exception, errorMessage);
				} else if (get(e, 'response', null) !== null) {
					flashMessage.requestError(e.response, errorMessage);
				} else {
					flashMessage.error(errorMessage);
				}
			}
		}
	});
};

watch(
	(): boolean => props.remoteFormReset,
	(val): void => {
		emit('update:remoteFormReset', false);

		if (val) {
			if (!signFormEl.value) return;

			signFormEl.value.resetFields();
		}
	}
);
</script>
