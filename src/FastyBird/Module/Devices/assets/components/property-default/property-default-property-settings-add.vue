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
							<div
								:class="headerNs.e('title')"
								role="heading"
							>
								{{ t('devicesModule.headings.properties.add') }}
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
								{{ isDraft ? t('devicesModule.buttons.add.title') : t('devicesModule.buttons.save.title') }}
							</el-button>
						</div>
					</template>

					<template v-else>
						<div :class="headerNs.e('heading')">
							<el-icon :class="headerNs.e('icon')">
								<component :is="FasPlus" />
							</el-icon>

							<div
								:class="headerNs.e('title')"
								role="heading"
							>
								{{ t('devicesModule.headings.properties.add') }}
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
			v-if="isConnectorProperty || props.property.type.type === PropertyType.VARIABLE"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
			:connector="props.connector"
			:device="props.device"
			:channel="props.channel"
			:property="props.property"
			@added="onAdded"
			@saved="onSaved"
		/>

		<template v-else>
			<template v-if="activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_TYPE">
				<div class="mb-2 flex flex-row">
					<el-button
						:icon="FasFile"
						size="large"
						type="primary"
						class="w-full uppercase"
						@click.prevent="onOpenView(PropertyDefaultPropertySettingsAddViewTypes.NEW_PROPERTY)"
					>
						{{ t('devicesModule.buttons.new.title') }}
					</el-button>

					<el-button
						:icon="FasClone"
						size="large"
						type="primary"
						class="w-full uppercase"
						@click.prevent="onOpenView(PropertyDefaultPropertySettingsAddViewTypes.SELECT_CONNECTOR)"
					>
						{{ t('devicesModule.buttons.mapExisting.title') }}
					</el-button>
				</div>

				<el-alert
					type="info"
					:closable="false"
				>
					<h3>New parameter</h3>
					<p>This option will create new independent item parameter to receive or set data. This type of parameter could be fully customized.</p>

					<hr />

					<h3>Mapped parameter</h3>
					<p>
						This option will create parameter mapped to existing parameter. This type of parameter could not be configured, every settings is used
						from mapped parent one.
					</p>
				</el-alert>
			</template>

			<property-default-property-settings-form
				v-if="
					activeView === PropertyDefaultPropertySettingsAddViewTypes.NEW_PROPERTY ||
					activeView === PropertyDefaultPropertySettingsAddViewTypes.MAPPED_PROPERTY
				"
				v-model:remote-form-submit="remoteFormSubmit"
				v-model:remote-form-result="remoteFormResult"
				:connector="props.connector"
				:device="props.device"
				:channel="props.channel"
				:property="props.property"
				@added="onAdded"
				@saved="onSaved"
			/>

			<template v-if="activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_CONNECTOR">
				<app-list>
					<template #title>
						{{ t('devicesModule.headings.properties.connectorSelect') }}
					</template>

					<div class="py-2">
						<app-list-item
							v-for="connectorItem in connectors"
							:key="connectorItem.id"
							:disabled="connectorItem.disabled"
							:variant="ListItemVariantTypes.DEFAULT"
							@click="onSelectConnector(connectorItem)"
						>
							<template #icon>
								<connectors-connector-icon :connector="connectorItem" />
							</template>

							<template #title>
								{{ connectorItem.title }}
							</template>

							<template
								v-if="connectorItem.hasComment"
								#subtitle
							>
								{{ connectorItem.comment }}
							</template>

							<template #button>
								<el-icon>
									<Icon icon="fa6-solid:chevron-right" />
								</el-icon>
							</template>
						</app-list-item>
					</div>

					<el-result v-if="!connectors.length">
						<template #primary>
							<Icon icon="fa6-solid:ethernet" />
						</template>

						<template #secondary>
							<Icon icon="fa6-solid:exclamation" />
						</template>

						<template #title>
							{{ t('devicesModule.texts.misc.noConnectors') }}
						</template>
					</el-result>
				</app-list>
			</template>

			<template v-if="activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_DEVICE">
				<app-list>
					<template #title>
						{{ t('devicesModule.headings.properties.deviceSelect') }}
					</template>

					<div class="py-2">
						<app-list-item
							v-for="deviceItem in devices"
							:key="deviceItem.id"
							:disabled="deviceItem.disabled"
							:variant="ListItemVariantTypes.DEFAULT"
							@click="onSelectDevice(deviceItem)"
						>
							<template #icon>
								<devices-device-icon :device="deviceItem" />
							</template>

							<template #title>
								{{ deviceItem.title }}
							</template>

							<template
								v-if="deviceItem.hasComment"
								#subtitle
							>
								{{ deviceItem.comment }}
							</template>

							<template #button>
								<el-icon>
									<Icon icon="fa6-solid:chevron-right" />
								</el-icon>
							</template>
						</app-list-item>
					</div>

					<el-result v-if="!devices.length">
						<template #primary>
							<Icon icon="fa6-solid:plug" />
						</template>

						<template #secondary>
							<Icon icon="fa6-solid:exclamation" />
						</template>

						<template #title>
							{{ t('devicesModule.texts.misc.noDevices') }}
						</template>
					</el-result>
				</app-list>
			</template>

			<template v-if="activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_CHANNEL">
				<app-list>
					<template #title>
						{{ t('devicesModule.headings.properties.channelSelect') }}
					</template>

					<div class="py-2">
						<app-list-item
							v-for="channelItem in channels"
							:key="channelItem.id"
							:disabled="channelItem.disabled"
							:variant="ListItemVariantTypes.DEFAULT"
							@click="onSelectChannel(channelItem)"
						>
							<template #icon>
								<Icon icon="fa6-solid:cube" />
							</template>

							<template #title>
								{{ channelItem.title }}
							</template>

							<template
								v-if="channelItem.hasComment"
								#subtitle
							>
								{{ channelItem.comment }}
							</template>

							<template #button>
								<el-icon>
									<Icon icon="fa6-solid:chevron-right" />
								</el-icon>
							</template>
						</app-list-item>
					</div>

					<el-result v-if="!channels.length">
						<template #primary>
							<Icon icon="fa6-solid:cube" />
						</template>

						<template #secondary>
							<Icon icon="fa6-solid:exclamation" />
						</template>

						<template #title>
							{{ t('devicesModule.texts.devices.noChannels') }}
						</template>
					</el-result>
				</app-list>
			</template>

			<template v-if="activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_PARENT">
				<app-list>
					<template #title>
						{{ t('devicesModule.headings.properties.parentSelect') }}
					</template>

					<div class="py-2">
						<app-list-item
							v-for="propertyItem in properties"
							:key="propertyItem.id"
							:variant="ListItemVariantTypes.DEFAULT"
							@click="onSelectParent(propertyItem)"
						>
							<template #icon>
								<properties-property-icon :property="propertyItem" />
							</template>

							<template #title>
								{{ propertyItem.title }}
							</template>

							<template #button>
								<el-icon>
									<Icon icon="fa6-solid:chevron-right" />
								</el-icon>
							</template>
						</app-list-item>
					</div>

					<el-result v-if="!properties.length">
						<template #primary>
							<Icon icon="fa6-solid:cube" />
						</template>

						<template #secondary>
							<Icon icon="fa6-solid:exclamation" />
						</template>

						<template #title>
							<template v-if="isDeviceProperty">
								{{ t('devicesModule.texts.devices.noProperties') }}
							</template>

							<template v-if="isChannelProperty">
								{{ t('devicesModule.texts.channels.noProperties') }}
							</template>
						</template>
					</el-result>
				</app-list>
			</template>
		</template>

		<template #footer>
			<footer :class="footerNs.b()">
				<div :class="footerNs.e('left-button')">
					<el-button
						v-if="
							!isConnectorProperty &&
							props.property.type.type !== PropertyType.VARIABLE &&
							(activeView === PropertyDefaultPropertySettingsAddViewTypes.NEW_PROPERTY ||
								activeView === PropertyDefaultPropertySettingsAddViewTypes.MAPPED_PROPERTY)
						"
						size="large"
						link
						name="cancel"
						class="uppercase"
						@click="onClose"
					>
						{{ t('devicesModule.buttons.cancel.title') }}
					</el-button>

					<el-button
						v-else
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
						v-if="isConnectorProperty || props.property.type.type === PropertyType.VARIABLE"
						:loading="remoteFormResult === FormResultTypes.WORKING"
						:disabled="remoteFormResult !== FormResultTypes.NONE"
						:icon="remoteFormResult === FormResultTypes.OK ? FarCircleCheck : remoteFormResult === FormResultTypes.ERROR ? FarCircleXmark : undefined"
						type="primary"
						size="large"
						name="submit"
						class="uppercase"
						@click="onSubmit"
					>
						{{ isDraft ? t('devicesModule.buttons.add.title') : t('devicesModule.buttons.save.title') }}
					</el-button>

					<template v-else>
						<template v-if="activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_TYPE">
							<el-button
								type="primary"
								size="large"
								name="submit"
								class="uppercase"
								disabled
							>
								{{ isDraft ? t('devicesModule.buttons.add.title') : t('devicesModule.buttons.save.title') }}
							</el-button>
						</template>

						<template
							v-if="
								activeView === PropertyDefaultPropertySettingsAddViewTypes.NEW_PROPERTY ||
								activeView === PropertyDefaultPropertySettingsAddViewTypes.MAPPED_PROPERTY
							"
						>
							<el-button
								:loading="remoteFormResult === FormResultTypes.WORKING"
								:disabled="remoteFormResult !== FormResultTypes.NONE"
								:icon="
									remoteFormResult === FormResultTypes.OK ? FarCircleCheck : remoteFormResult === FormResultTypes.ERROR ? FarCircleXmark : undefined
								"
								type="primary"
								size="large"
								name="submit"
								class="uppercase"
								@click="onSubmit"
							>
								{{ isDraft ? t('devicesModule.buttons.add.title') : t('devicesModule.buttons.save.title') }}
							</el-button>
						</template>

						<template
							v-if="
								activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_CONNECTOR ||
								activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_DEVICE ||
								activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_CHANNEL ||
								activeView === PropertyDefaultPropertySettingsAddViewTypes.SELECT_PARENT
							"
						>
							<el-button
								type="primary"
								size="large"
								name="submit"
								class="uppercase"
								@click="onBack"
							>
								{{ t('devicesModule.buttons.back.title') }}
							</el-button>
						</template>
					</template>
				</div>
			</footer>
		</template>
	</el-dialog>
</template>

<script setup lang="ts">
import { computed, h, inject, ref } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElAlert, ElButton, ElDialog, ElIcon, ElResult, useNamespace } from 'element-plus';
import { orderBy } from 'natural-orderby';

import { AppList, AppListItem, ListItemVariantTypes } from '@fastybird/application';
import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import {
	channelPropertiesStoreKey,
	channelsStoreKey,
	connectorPropertiesStoreKey,
	connectorsStoreKey,
	devicePropertiesStoreKey,
	devicesStoreKey,
} from '../../configuration';
import { ApplicationError } from '../../errors';
import {
	FormResultType,
	FormResultTypes,
	IChannel,
	IChannelProperty,
	IConnector,
	IConnectorProperty,
	IDevice,
	IDeviceProperty,
	PropertyType,
} from '../../types';
import ConnectorsConnectorIcon from '../connectors/connectors-connector-icon.vue';
import DevicesDeviceIcon from '../devices/devices-device-icon.vue';
import PropertiesPropertyIcon from '../properties/properties-property-icon.vue';
import PropertyDefaultPropertySettingsForm from '../property-default/property-default-property-settings-form.vue';

import {
	IChannelListItem,
	IConnectorListItem,
	IDeviceListItem,
	IPropertyDefaultPropertySettingsAddProps,
	PropertyDefaultPropertySettingsAddViewTypes,
} from './property-default-property-settings-add.types';

const FarCircleCheck = (): VNode => h(Icon, { icon: 'fa6-regular:circle-check' });
const FarCircleXmark = (): VNode => h(Icon, { icon: 'fa6-regular:circle-xmark' });
const FasClone = (): VNode => h(Icon, { icon: 'fa6-solid:clone' });
const FasFile = (): VNode => h(Icon, { icon: 'fa6-solid:file' });
const FasPlus = (): VNode => h(Icon, { icon: 'fa6-solid:plus' });

defineOptions({
	name: 'PropertyDefaultPropertySettingsAdd',
});

const props = defineProps<IPropertyDefaultPropertySettingsAddProps>();

const emit = defineEmits<{
	(e: 'close', canceled: boolean): void;
}>();

const { t } = useI18n();
const { isSMDevice, isMDDevice } = useBreakpoints();

const headerNs = useNamespace('dialog-header');
const footerNs = useNamespace('dialog-footer');

const connectorsStore = inject(connectorsStoreKey);
const connectorPropertiesStore = inject(connectorPropertiesStoreKey);
const devicesStore = inject(devicesStoreKey);
const devicePropertiesStore = inject(devicePropertiesStoreKey);
const channelsStore = inject(channelsStoreKey);
const channelPropertiesStore = inject(channelPropertiesStoreKey);

if (
	typeof connectorsStore === 'undefined' ||
	typeof connectorPropertiesStore === 'undefined' ||
	typeof devicesStore === 'undefined' ||
	typeof devicePropertiesStore === 'undefined' ||
	typeof channelsStore === 'undefined' ||
	typeof channelPropertiesStore === 'undefined'
) {
	throw new ApplicationError('Something went wrong, module is wrongly configured', null);
}

const open = ref<boolean>(true);

let closedCallback: () => void = (): void => {};

const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const activeView = ref<PropertyDefaultPropertySettingsAddViewTypes>(PropertyDefaultPropertySettingsAddViewTypes.SELECT_TYPE);

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

const selectedConnector = ref<IConnector | null>(null);
const selectedDevice = ref<IDevice | null>(null);
const selectedChannel = ref<IChannel | null>(null);

const connectors = computed<IConnectorListItem[]>((): IConnectorListItem[] => {
	return orderBy<IConnectorListItem>(
		Object.values(connectorsStore.data ?? {})
			.filter((connector) => !connector.draft)
			.map((connector) => {
				if (!isConnectorProperty.value) {
					return { ...connector, ...{ disabled: false } };
				}

				return { ...connector, ...{ disabled: !('connector' in props.property && connector.id !== props.property.connector.id) } };
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const devices = computed<IDeviceListItem[]>((): IDeviceListItem[] => {
	if (selectedConnector.value === null) {
		return [];
	}

	return orderBy<IDeviceListItem>(
		Object.values(devicesStore.findForConnector(selectedConnector.value.id))
			.filter((device) => !device.draft)
			.map((device) => {
				if (!isDeviceProperty.value) {
					return { ...device, ...{ disabled: false } };
				}

				return { ...device, ...{ disabled: !('device' in props.property && device.id !== props.property.device.id) } };
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const channels = computed<IChannelListItem[]>((): IChannelListItem[] => {
	if (selectedDevice.value === null) {
		return [];
	}

	return orderBy<IChannelListItem>(
		Object.values(channelsStore.findForDevice(selectedDevice.value.id))
			.filter((channel) => !channel.draft)
			.map((channel) => {
				if (!isChannelProperty.value) {
					return { ...channel, ...{ disabled: false } };
				}

				return { ...channel, ...{ disabled: !('channel' in props.property && channel.id !== props.property.channel.id) } };
			}),
		[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
		['asc']
	);
});

const properties = computed<(IChannelProperty | IConnectorProperty | IDeviceProperty)[]>(
	(): (IChannelProperty | IConnectorProperty | IDeviceProperty)[] => {
		if (isChannelProperty.value) {
			if (selectedChannel.value === null) {
				return [];
			}

			return orderBy<IChannelProperty>(
				Object.values(channelPropertiesStore.findForChannel(selectedChannel.value.id)).filter(
					(property) => !property.draft && property.type.type === props.property.type.type && property.parent === null
				),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			);
		}

		if (isDeviceProperty.value) {
			if (selectedDevice.value === null) {
				return [];
			}

			return orderBy<IDeviceProperty>(
				Object.values(devicePropertiesStore.findForDevice(selectedDevice.value.id))
					.filter((property) => !property.draft)
					.filter((property) => property.type.type === props.property.type.type),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			);
		}

		if (isConnectorProperty.value) {
			if (selectedConnector.value === null) {
				return [];
			}

			return orderBy<IConnectorProperty>(
				Object.values(connectorPropertiesStore.findForConnector(selectedConnector.value.id))
					.filter((property) => !property.draft)
					.filter((property) => property.type.type === props.property.type.type),
				[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
				['asc']
			);
		}

		return [];
	}
);

const onSelectConnector = (connector: IConnectorListItem): void => {
	if (connector.disabled) {
		return;
	}

	selectedConnector.value = connector;

	activeView.value = isConnectorProperty.value
		? PropertyDefaultPropertySettingsAddViewTypes.SELECT_PARENT
		: PropertyDefaultPropertySettingsAddViewTypes.SELECT_DEVICE;
};

const onSelectDevice = (device: IDeviceListItem): void => {
	if (device.disabled) {
		return;
	}

	selectedDevice.value = device;

	activeView.value = isDeviceProperty.value
		? PropertyDefaultPropertySettingsAddViewTypes.SELECT_PARENT
		: PropertyDefaultPropertySettingsAddViewTypes.SELECT_CHANNEL;
};

const onSelectChannel = (channel: IChannelListItem): void => {
	if (channel.disabled) {
		return;
	}

	selectedChannel.value = channel;

	activeView.value = PropertyDefaultPropertySettingsAddViewTypes.SELECT_PARENT;
};

const onSelectParent = (property: IChannelProperty | IConnectorProperty | IDeviceProperty): void => {
	if (isDeviceProperty.value) {
		devicePropertiesStore.edit({
			id: props.property.id,
			parent: property as IDeviceProperty,
			data: {},
		});

		activeView.value = PropertyDefaultPropertySettingsAddViewTypes.MAPPED_PROPERTY;
	} else if (isChannelProperty.value) {
		channelPropertiesStore.edit({
			id: props.property.id,
			parent: property as IChannelProperty,
			data: {},
		});

		activeView.value = PropertyDefaultPropertySettingsAddViewTypes.MAPPED_PROPERTY;
	}
};

const onSubmit = (): void => {
	remoteFormSubmit.value = true;
};

const onClose = (): void => {
	closedCallback = (): void => emit('close', true);
	open.value = false;
};

const onClosed = (): void => {
	closedCallback();
};

const onBack = (): void => {
	if (activeView.value === PropertyDefaultPropertySettingsAddViewTypes.SELECT_CONNECTOR) {
		activeView.value = PropertyDefaultPropertySettingsAddViewTypes.SELECT_TYPE;
	} else if (activeView.value === PropertyDefaultPropertySettingsAddViewTypes.SELECT_DEVICE) {
		activeView.value = PropertyDefaultPropertySettingsAddViewTypes.SELECT_CONNECTOR;
	} else if (activeView.value === PropertyDefaultPropertySettingsAddViewTypes.SELECT_CHANNEL) {
		activeView.value = PropertyDefaultPropertySettingsAddViewTypes.SELECT_DEVICE;
	} else if (activeView.value === PropertyDefaultPropertySettingsAddViewTypes.SELECT_PARENT) {
		if (isChannelProperty.value) {
			activeView.value = PropertyDefaultPropertySettingsAddViewTypes.SELECT_CHANNEL;
		} else if (isDeviceProperty.value) {
			activeView.value = PropertyDefaultPropertySettingsAddViewTypes.SELECT_DEVICE;
		} else if (isConnectorProperty.value) {
			activeView.value = PropertyDefaultPropertySettingsAddViewTypes.SELECT_CONNECTOR;
		}
	}
};

const onOpenView = (view: PropertyDefaultPropertySettingsAddViewTypes): void => {
	activeView.value = view;
};

const onAdded = (): void => {
	closedCallback = (): void => emit('close', false);
	open.value = false;
};

const onSaved = (): void => {
	closedCallback = (): void => emit('close', false);
	open.value = false;
};
</script>
