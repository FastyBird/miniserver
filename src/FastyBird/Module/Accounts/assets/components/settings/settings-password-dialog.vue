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
			<div :class="[headerNs.b(), headerNs.m('type-primary'), headerNs.m('layout-' + (isXSDevice ? 'phone' : 'default'))]">
				<div :class="headerNs.e('inner')">
					<template v-if="isXSDevice">
						<div :class="headerNs.e('heading')">
							<div
								:class="headerNs.e('title')"
								role="heading"
							>
								{{ t('accountsModule.headings.passwordChange') }}
							</div>
						</div>

						<div :class="headerNs.e('left-button')">
							<el-button
								type="default"
								size="default"
								text
								@click.prevent="onClose"
							>
								{{ t('accountsModule.buttons.close.title') }}
							</el-button>
						</div>

						<div :class="headerNs.e('right-button')">
							<el-button
								type="default"
								size="default"
								text
								@click.prevent="onSave"
							>
								{{ t('accountsModule.buttons.save.title') }}
							</el-button>
						</div>
					</template>

					<template v-else>
						<div :class="headerNs.e('heading')">
							<el-icon :class="headerNs.e('icon')">
								<Icon icon="fa6-solid:key" />
							</el-icon>

							<div
								:class="headerNs.e('title')"
								role="heading"
							>
								{{ t('accountsModule.headings.passwordChange') }}
							</div>
						</div>

						<button
							:aria-label="t('accountsModule.buttons.close.title')"
							:class="headerNs.e('close')"
							type="button"
							@click.prevent="onClose"
						>
							<el-icon>
								<Icon icon="fa6-solid:xmark" />
							</el-icon>
						</button>
					</template>
				</div>
			</div>
		</template>

		<settings-password-form
			v-if="identity"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:identity="identity"
		/>

		<template #footer>
			<footer
				v-if="!isXSDevice"
				:class="footerNs.b()"
			>
				<div :class="footerNs.e('left-button')">
					<el-button
						type="default"
						size="large"
						text
						@click.prevent="onClose"
					>
						{{ t('accountsModule.buttons.close.title') }}
					</el-button>
				</div>

				<div :class="footerNs.e('right-button')">
					<el-button
						type="primary"
						size="large"
						plain
						@click.prevent="onSave"
					>
						{{ t('accountsModule.buttons.save.title') }}
					</el-button>
				</div>
			</footer>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElDialog, ElIcon, ElLoading, useNamespace } from 'element-plus';

import { injectStoresManager, useBreakpoints } from '@fastybird/tools';
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

const headerNs = useNamespace('dialog-header');
const footerNs = useNamespace('dialog-footer');

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
