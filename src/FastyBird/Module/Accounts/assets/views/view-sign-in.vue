<template>
	<layout-sign-box>
		<layout-sign-header :heading="t('accountsModule.headings.signIn')" />

		<sign-in-form v-model:remote-form-result="remoteFormResult" />

		<div class="mt-5 text-center">
			<el-button
				link
				@click="router.push({ name: routeNames.resetPassword })"
			>
				{{ t('accountsModule.buttons.forgotPassword.title') }}
			</el-button>
		</div>
	</layout-sign-box>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';

import { ElButton } from 'element-plus';

import { useEventBus } from '@fastybird/tools';
import { useHead } from '@unhead/vue';

import { LayoutSignBox, LayoutSignHeader, SignInForm } from '../components';
import { useRoutesNames } from '../composables';
import { FormResultType, FormResultTypes } from '../types';

defineOptions({
	name: 'ViewSignIn',
});

const { t } = useI18n();
const { routeNames } = useRoutesNames();
const router = useRouter();

const eventsBus = useEventBus();

const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

watch(
	(): FormResultType => remoteFormResult.value,
	(state: FormResultType): void => {
		if (state === FormResultTypes.WORKING) {
			eventsBus.emit('loadingOverlay', 10);
		} else {
			eventsBus.emit('loadingOverlay', false);

			if (state === FormResultTypes.OK) {
				eventsBus.emit('userSigned', 'in');
			}
		}
	}
);

useHead({
	title: t('accountsModule.meta.sign.in.title'),
});
</script>
