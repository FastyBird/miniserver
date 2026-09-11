<template>
	<el-dialog
		ref="dialogRef"
		v-model="dialogVisible"
		:show-close="false"
		:fullscreen="isXSDevice"
		:draggable="!isXSDevice"
		class="p-0"
		@close="onClose"
	>
		<template #header>
			<fb-dialog-header
				:layout="isXSDevice ? 'phone' : 'default'"
				:left-btn-label="t('accountsModule.buttons.close.title')"
				:right-btn-label="t('accountsModule.buttons.save.title')"
				@close="onClose"
			>
				<template #title>
					{{ t('accountsModule.headings.passwordChange') }}
				</template>

				<template #icon>
					<Icon icon="fa6-solid:key" />
				</template>
			</fb-dialog-header>
		</template>

		<settings-password-form
			v-if="identity"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:identity="identity"
		/>

		<template #footer>
			<fb-dialog-footer
				:layout="isXSDevice ? 'phone' : 'default'"
				:left-btn-label="t('accountsModule.buttons.close.title')"
				:right-btn-label="t('accountsModule.buttons.save.title')"
				@left-click="onClose"
				@right-click="onSave"
			/>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElDialog, ElLoading } from 'element-plus';

import { injectStoresManager, useBreakpoints } from '@fastybird/tools';
import { FbDialogFooter, FbDialogHeader } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { identitiesStoreKey, sessionStoreKey } from '../../configuration';
import { FormResultType, FormResultTypes, IIdentity } from '../../types';

import { ISettingsPasswordDialogProps } from './settings-password-dialog.types';
import SettingsPasswordForm from './settings-password-form.vue';

defineOptions({
	name: 'SettingsPasswordDialog',
});

const props = defineProps<ISettingsPasswordDialogProps>();

const emit = defineEmits<{
	(e: 'update:visible', visibility: boolean): void;
	(e: 'close'): void;
}>();

const { t } = useI18n();
const { isXSDevice } = useBreakpoints();

const storesManager = injectStoresManager();

const sessionStore = storesManager.getStore(sessionStoreKey);
const identitiesStore = storesManager.getStore(identitiesStoreKey);

const dialogRef = ref();

const dialogVisible = ref<boolean>(props.visible);
const loading = ref<any | undefined>(undefined);

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const isMounted = ref<boolean>(false);

const identity = computed<IIdentity | null>((): IIdentity | null => {
	if (sessionStore.account() === null || sessionStore.account()!.email === null) {
		return null;
	}

	const identity = identitiesStore
		.findForAccount(sessionStore.account()!.id)
		.find((identity) => identity.uid === sessionStore.account()?.email?.address);

	return identity ?? null;
});

const onClose = (): void => {
	emit('update:visible', false);
	emit('close');
};

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

onMounted((): void => {
	isMounted.value = true;
});

watch(
	(): FormResultType => remoteFormResult.value,
	(actual: FormResultType, previous: FormResultType): void => {
		if (actual === FormResultTypes.WORKING) {
			loading.value = ElLoading.service({
				target: dialogRef.value.$el.nextElementSibling.querySelector('.el-dialog'),
			});
		} else {
			(loading.value as any)?.close();
		}

		if (actual === FormResultTypes.NONE && previous === FormResultTypes.OK) {
			emit('close');
		}
	}
);
</script>
