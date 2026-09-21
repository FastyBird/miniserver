<template>
	<el-card v-if="isMDDevice">
		<settings-account-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:account="sessionStore.account()!"
		/>
	</el-card>

	<template v-else>
		<settings-account-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:account="sessionStore.account()!"
			:layout="LayoutTypes.PHONE"
		/>

		<el-button
			:loading="remoteFormResult === FormResultTypes.WORKING"
			:disabled="remoteFormResult === FormResultTypes.WORKING"
			type="primary"
			class="w-full mt-5"
			@click="onSave"
		>
			{{ t('accountsModule.buttons.save.title') }}
		</el-button>
	</template>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElCard } from 'element-plus';

import { injectStoresManager, useBreakpoints } from '@fastybird/miniserver-core';
import { useHead } from '@unhead/vue';

import { SettingsAccountForm } from '../components';
import { sessionStoreKey } from '../configuration';
import { FormResultType, FormResultTypes, LayoutTypes } from '../types';

import { IViewProfileProps } from './view-profile.types';

defineOptions({
	name: 'ViewProfile',
});

const props = withDefaults(defineProps<IViewProfileProps>(), {
	remoteFormSubmit: false,
	remoteFormResult: FormResultTypes.NONE,
	remoteFormReset: false,
});

const emit = defineEmits<{
	(e: 'update:remoteFormSubmit', remoteFormSubmit: boolean): void;
	(e: 'update:remoteFormResult', remoteFormResult: FormResultType): void;
	(e: 'update:remoteFormReset', remoteFormReset: boolean): void;
}>();

const { t } = useI18n();

const { isMDDevice } = useBreakpoints();

const storesManager = injectStoresManager();

const sessionStore = storesManager.getStore(sessionStoreKey);

const remoteFormSubmit = ref<boolean>(props.remoteFormSubmit);
const remoteFormResult = ref<FormResultType>(props.remoteFormResult);
const remoteFormReset = ref<boolean>(props.remoteFormReset);

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

watch(
	(): boolean => props.remoteFormSubmit,
	async (val: boolean): Promise<void> => {
		remoteFormSubmit.value = val;
	}
);

watch(
	(): boolean => props.remoteFormReset,
	async (val: boolean): Promise<void> => {
		remoteFormReset.value = val;
	}
);

watch(
	(): boolean => remoteFormSubmit.value,
	async (val: boolean): Promise<void> => {
		emit('update:remoteFormSubmit', val);
	}
);

watch(
	(): FormResultType => remoteFormResult.value,
	async (val: FormResultType): Promise<void> => {
		emit('update:remoteFormResult', val);
	}
);

watch(
	(): boolean => remoteFormReset.value,
	async (val: boolean): Promise<void> => {
		emit('update:remoteFormReset', val);
	}
);

useHead({
	title: t('accountsModule.meta.profile.profile.title'),
});
</script>
