<template>
	<layout-sign-box>
		<layout-sign-header
			v-if="isSubmitted"
			:heading="t('accountsModule.headings.instructionEmailed')"
		/>
		<layout-sign-header
			v-else
			:heading="t('accountsModule.headings.passwordReset')"
		/>

		<div ref="signBoxEl">
			<el-text
				v-if="isSubmitted"
				size="small"
				tag="p"
				class="text-center"
			>
				{{ t('accountsModule.texts.resetPasswordInstructionsEmailed') }}
			</el-text>

			<reset-password-form
				v-if="!isSubmitted"
				v-model:remote-form-result="remoteFormResult"
			/>
		</div>
	</layout-sign-box>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElLoading, ElText } from 'element-plus';

import { useHead } from '@unhead/vue';

import { LayoutSignBox, LayoutSignHeader, ResetPasswordForm } from '../components';
import { FormResultType, FormResultTypes } from '../types';

defineOptions({
	name: 'ViewResetPassword',
});

const { t } = useI18n();

const signBoxEl = ref<HTMLElement>();

const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const isSubmitted = ref<boolean>(false);

const loader = ref<{ close: () => void } | null>(null);

watch(
	(): FormResultType => remoteFormResult.value,
	(state: FormResultType): void => {
		if (state === FormResultTypes.WORKING) {
			loader.value = ElLoading.service({
				target: signBoxEl.value,
				lock: true,
				text: t('accountsModule.texts.processing'),
			});
		} else if (state === FormResultTypes.OK) {
			loader.value?.close();

			isSubmitted.value = true;
		} else if (state === FormResultTypes.ERROR) {
			loader.value?.close();
		}
	}
);

useHead({
	title: t('accountsModule.meta.sign.passwordReset.title'),
});
</script>
