<template>
	<el-dialog
		v-model="open"
		:show-close="false"
		align-center
		@closed="onClosed"
	>
		<template #header>
			<fb-dialog-header
				:layout="isMDDevice ? 'default' : isSMDevice ? 'tablet' : 'phone'"
				:left-btn-label="t('devicesModule.buttons.close.title')"
				:right-btn-label="isDraft ? t('devicesModule.buttons.update.title') : t('devicesModule.buttons.save.title')"
				:icon="FasPencil"
				@left-click="onClose"
				@right-click="onSubmit"
				@close="onClose"
			>
				<template #title>
					{{ t('devicesModule.headings.properties.edit') }}
				</template>
			</fb-dialog-header>
		</template>

		<property-default-property-settings-form
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:connector="props.connector"
			:device="props.device"
			:channel="props.channel"
			:property="props.property"
			@created="onCreated"
		/>

		<template #footer>
			<fb-dialog-footer
				:left-btn-label="t('devicesModule.buttons.close.title')"
				:right-btn-label="isDraft ? t('devicesModule.buttons.update.title') : t('devicesModule.buttons.save.title')"
				@left-click="onClose"
				@right-click="onSubmit"
			>
				<template #left-button>
					<el-button
						size="large"
						link
						name="close"
						class="uppercase"
						@click="onClose"
					>
						{{ t('devicesModule.buttons.close.title') }}
					</el-button>
				</template>

				<template #right-button>
					<el-button
						:loading="remoteFormResult === FormResultTypes.WORKING"
						:disabled="remoteFormResult !== FormResultTypes.NONE"
						:icon="remoteFormResult === FormResultTypes.OK ? FarCircleCheck : remoteFormResult === FormResultTypes.ERROR ? FarCircleXmark : undefined"
						type="primary"
						size="large"
						name="submit"
						class="uppercase"
						@click="onSubmit"
					>
						{{ t('devicesModule.buttons.save.title') }}
					</el-button>
				</template>
			</fb-dialog-footer>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElDialog } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
import { FbDialogFooter, FbDialogHeader } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { FormResultType, FormResultTypes } from '../../types';
import PropertyDefaultPropertySettingsForm from '../property-default/property-default-property-settings-form.vue';

import { IPropertyDefaultPropertySettingsEditProps } from './property-default-property-settings-edit.types';

const FarCircleCheck = (): VNode => h(Icon, { icon: 'fa6-regular:circle-check' });
const FarCircleXmark = (): VNode => h(Icon, { icon: 'fa6-regular:circle-xmark' });
const FasPencil = (): VNode => h(Icon, { icon: 'fa6-solid:pencil' });

defineOptions({
	name: 'PropertyDefaultPropertySettingsEdit',
});

const props = defineProps<IPropertyDefaultPropertySettingsEditProps>();

const emit = defineEmits<{
	(e: 'close'): void;
}>();

const { t } = useI18n();
const { isSMDevice, isMDDevice } = useBreakpoints();

const open = ref<boolean>(true);

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const isDraft = computed<boolean>((): boolean => {
	if (isChannelProperty.value) {
		return props.channel ? props.channel.draft : false;
	}

	if (isDeviceProperty.value) {
		return props.device ? props.device.draft : false;
	}

	if (isConnectorProperty.value) {
		return props.connector ? props.connector.draft : false;
	}

	return false;
});

const isConnectorProperty = computed<boolean>((): boolean => props.connector !== undefined);
const isDeviceProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel === undefined);
const isChannelProperty = computed<boolean>((): boolean => props.device !== undefined && props.channel !== undefined);

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	open.value = false;
};

const onClosed = (): void => {
	emit('close');
};

const onCreated = (): void => {
	onClose();
};

watch(
	(): FormResultType => remoteFormResult.value,
	(actual: FormResultType, previous: FormResultType): void => {
		if (actual === FormResultTypes.NONE && previous === FormResultTypes.OK) {
			onClose();
		}
	}
);
</script>
