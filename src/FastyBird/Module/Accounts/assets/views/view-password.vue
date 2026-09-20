<template>
	<el-card v-if="isMDDevice">
		<settings-password-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:identity="identity!"
		/>
	</el-card>

	<template v-else>
		<settings-password-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			v-model:remote-form-reset="remoteFormReset"
			:identity="identity!"
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
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElCard } from 'element-plus';

import { injectStoresManager, useBreakpoints } from '@fastybird/miniserver-core';
import { useHead } from '@unhead/vue';

import { SettingsPasswordForm } from '../components';
import { identitiesStoreKey, sessionStoreKey } from '../configuration';
import { FormResultType, FormResultTypes, IIdentity, LayoutTypes } from '../types';

import { IViewPasswordProps } from './view-password.types';

defineOptions({
	name: 'ViewPassword',
});

const props = withDefaults(defineProps<IViewPasswordProps>(), {
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
const identitiesStore = storesManager.getStore(identitiesStoreKey);

const remoteFormSubmit = ref<boolean>(props.remoteFormSubmit);
const remoteFormResult = ref<FormResultType>(props.remoteFormResult);
const remoteFormReset = ref<boolean>(props.remoteFormReset);

const identity = computed<IIdentity | null>((): IIdentity | null => {
	if (sessionStore.account() === null || sessionStore.account()!.email === null) {
		return null;
	}

	const identity = identitiesStore
		.findForAccount(sessionStore.account()!.id)
		.find((identity) => identity.uid === sessionStore.account()?.email?.address);

	return identity ?? null;
});

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
	title: t('accountsModule.meta.profile.password.title'),
});
</script>
