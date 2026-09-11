<template>
	<el-dialog
		v-model="open"
		:show-close="false"
		align-center
		@closed="onClosed"
	>
		<template #header>
			<div :class="[headerNs.b(), headerNs.m('type-primary'), headerNs.m('layout-' + (isMDDevice ? 'default' : isSMDevice ? 'tablet' : 'phone'))]">
				<div :class="headerNs.e('inner')">
					<template v-if="!isMDDevice">
						<div :class="headerNs.e('heading')">
							<div :class="headerNs.e('title')">
								{{ t('devicesModule.headings.properties.edit') }}
							</div>
						</div>

						<div :class="headerNs.e('left-button')">
							<el-button
								type="default"
								size="default"
								text
								@click.prevent="onClose"
							>
								{{ t('devicesModule.buttons.close.title') }}
							</el-button>
						</div>

						<div :class="headerNs.e('right-button')">
							<el-button
								type="default"
								size="default"
								text
								@click.prevent="onSubmit"
							>
								{{ isDraft ? t('devicesModule.buttons.update.title') : t('devicesModule.buttons.save.title') }}
							</el-button>
						</div>
					</template>

					<template v-else>
						<div :class="headerNs.e('heading')">
							<el-icon :class="headerNs.e('icon')">
								<component :is="FasPencil" />
							</el-icon>

							<div :class="headerNs.e('title')">
								{{ t('devicesModule.headings.properties.edit') }}
							</div>
						</div>

						<button
							:aria-label="t('devicesModule.buttons.close.title')"
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
			<footer
				v-if="isMDDevice"
				:class="footerNs.b()"
			>
				<div :class="footerNs.e('left-button')">
					<el-button
						size="large"
						link
						name="close"
						class="uppercase"
						@click="onClose"
					>
						{{ t('devicesModule.buttons.close.title') }}
					</el-button>
				</div>

				<div :class="footerNs.e('right-button')">
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
				</div>
			</footer>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElDialog, ElIcon, useNamespace } from 'element-plus';

import { useBreakpoints } from '@fastybird/tools';
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

const headerNs = useNamespace('dialog-header');
const footerNs = useNamespace('dialog-footer');

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
