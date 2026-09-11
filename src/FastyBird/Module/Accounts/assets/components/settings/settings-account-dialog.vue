<template>
	<el-dialog
		ref="dialogRef"
		v-model="dialogVisible"
		:show-close="false"
		:fullscreen="isXSDevice"
		:draggable="!isXSDevice"
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
								{{ t('accountsModule.headings.accountSettings') }}
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
								<Icon icon="fa6-solid:user" />
							</el-icon>

							<div
								:class="headerNs.e('title')"
								role="heading"
							>
								{{ t('accountsModule.headings.accountSettings') }}
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

		<settings-account-form
			v-if="sessionStore.account()"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:account="sessionStore.account()!"
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
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElDialog, ElIcon, ElLoading, useNamespace } from 'element-plus';

import { injectStoresManager, useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import { sessionStoreKey } from '../../configuration';
import { FormResultType, FormResultTypes } from '../../types';

import { ISettingsAccountDialogProps } from './settings-account-dialog.types';
import SettingsAccountForm from './settings-account-form.vue';

defineOptions({
	name: 'SettingsAccountDialog',
});

const props = defineProps<ISettingsAccountDialogProps>();

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

const dialogRef = ref();

const dialogVisible = ref<boolean>(props.visible);
const loading = ref<any | undefined>(undefined);

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const onClose = (): void => {
	emit('update:visible', false);
	emit('close');
};

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

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
