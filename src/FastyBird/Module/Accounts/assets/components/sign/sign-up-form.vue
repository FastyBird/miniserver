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
		<div class="flex flex-row flex-nowrap gap-5">
			<div class="mb-5">
				<el-form-item
					ref="firstNameEl"
					:label="t('accountsModule.fields.firstName.title')"
					prop="firstName"
					class="mb-2"
				>
					<el-input
						v-model="signForm.firstName"
						name="firstName"
					/>
				</el-form-item>

				<div
					:class="[{ 'opacity-0': firstNameEl?.validateState === 'error' }]"
					class="text-3 pl-1"
				>
					{{ t('accountsModule.fields.firstName.help') }}
				</div>
			</div>

			<div class="mb-5">
				<el-form-item
					ref="lastNameEl"
					:label="t('accountsModule.fields.lastName.title')"
					prop="lastName"
					class="mb-2"
				>
					<el-input
						v-model="signForm.lastName"
						name="lastName"
					/>
				</el-form-item>

				<div
					:class="[{ 'opacity-0': lastNameEl?.validateState === 'error' }]"
					class="text-3 pl-1"
				>
					{{ t('accountsModule.fields.lastName.help') }}
				</div>
			</div>
		</div>

		<div class="mb-5">
			<el-form-item
				ref="emailAddressEl"
				:label="t('accountsModule.fields.emailAddress.title')"
				prop="emailAddress"
				class="mb-2"
			>
				<el-input
					v-model="signForm.emailAddress"
					name="emailAddress"
				/>
			</el-form-item>

			<div
				:class="[{ 'opacity-0': emailAddressEl?.validateState === 'error' }]"
				class="text-3 pl-1"
			>
				{{ t('accountsModule.fields.emailAddress.help') }}
			</div>
		</div>

		<div class="mb-10">
			<el-form-item
				ref="passwordEl"
				:label="t('accountsModule.fields.password.register.title')"
				prop="password"
				class="mb-2"
			>
				<el-input
					v-model="signForm.password"
					show-password
					name="password"
					type="password"
				/>
			</el-form-item>
		</div>

		<el-button
			type="primary"
			size="large"
			class="block w-full"
			@click="onSubmit(signFormEl)"
		>
			{{ t('accountsModule.buttons.signUp.title') }}
		</el-button>
	</el-form>

	<div class="mt-5 px-5">
		<el-button
			class="block w-full"
			@click="onBackToSignIn()"
		>
			{{ t('accountsModule.buttons.backToSignIn.title') }}
		</el-button>
	</div>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';

import { ElButton, ElForm, ElFormItem, ElInput, FormInstance, FormItemInstance, FormRules } from 'element-plus';
import get from 'lodash.get';

import { useFlashMessage } from '@fastybird/miniserver-core';

import { useRoutesNames } from '../../composables';
import { FormResultType, FormResultTypes } from '../../types';

import { ISignUpForm, ISignUpProps } from './sign-up-form.types';

defineOptions({
	name: 'SignUpForm',
});

const props = withDefaults(defineProps<ISignUpProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormResult', remoteFormResult: FormResultType): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();
const router = useRouter();

const { routeNames } = useRoutesNames();

const signFormEl = ref<FormInstance | undefined>(undefined);
const firstNameEl = ref<FormItemInstance | undefined>(undefined);
const lastNameEl = ref<FormItemInstance | undefined>(undefined);
const emailAddressEl = ref<FormItemInstance | undefined>(undefined);
const passwordEl = ref<FormItemInstance | undefined>(undefined);

const rules = reactive<FormRules<ISignUpForm>>({
	emailAddress: [
		{ required: true, message: t('accountsModule.fields.emailAddress.validation.required'), trigger: 'change' },
		{ type: 'email', message: t('accountsModule.fields.emailAddress.validation.email'), trigger: 'change' },
	],
	firstName: [{ required: true, message: t('accountsModule.fields.firstName.validation.required'), trigger: 'change' }],
	lastName: [{ required: true, message: t('accountsModule.fields.lastName.validation.required'), trigger: 'change' }],
	password: [{ required: true, message: t('accountsModule.fields.password.register.validation.required'), trigger: 'change' }],
});

const signForm = reactive<ISignUpForm>({
	emailAddress: '',
	firstName: '',
	lastName: '',
	password: '',
});

const onSubmit = async (formEl: FormInstance | undefined): Promise<void> => {
	if (!formEl) return;

	await formEl.validate(async (valid: boolean): Promise<void> => {
		if (valid) {
			emit('update:remoteFormResult', FormResultTypes.WORKING);

			try {
				// TODO: Implement registration

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

const onBackToSignIn = (): void => {
	router.push({ name: routeNames.signIn });
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
