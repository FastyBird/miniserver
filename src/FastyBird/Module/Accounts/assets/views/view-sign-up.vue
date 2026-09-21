<template>
	<layout-sign-box>
		<layout-sign-header :heading="t('accountsModule.headings.signUp')" />

		<sign-up-form v-model:remote-form-result="remoteFormResult" />
	</layout-sign-box>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { useEventBus } from '@fastybird/miniserver-core';
import { useHead } from '@unhead/vue';

import { LayoutSignBox, LayoutSignHeader, SignUpForm } from '../components';
import { FormResultType, FormResultTypes } from '../types';

defineOptions({
	name: 'ViewSignUp',
});

const { t } = useI18n();

const eventsBus = useEventBus();

const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

watch(
	(): FormResultType => remoteFormResult.value,
	(state: FormResultType): void => {
		if (state === FormResultTypes.WORKING) {
			eventsBus.emit('loadingOverlay', 10);
		} else {
			eventsBus.emit('loadingOverlay', false);
		}
	}
);

useHead({
	title: t('accountsModule.meta.sign.up.title'),
});
</script>
