<template>
	<dl class="grid m-0">
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.connectors.devices') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<i18n-t
					keypath="devicesModule.texts.connectors.devicesCount"
					:plural="props.connectorData.devices.length"
				>
					<template #count>
						<strong>{{ props.connectorData.devices.length }}</strong>
					</template>
				</i18n-t>
			</el-text>
		</dd>
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.connectors.status') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<el-tag
					:type="stateColor"
					size="small"
				>
					{{ t(`devicesModule.misc.state.${connectorState.toLowerCase()}`) }}
				</el-tag>
			</el-text>
		</dd>
		<template v-if="serverProperty && (serverPortProperty !== null || serverSecuredPortProperty === null)">
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t('devicesModule.texts.connectors.server') }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
				<el-text>
					{{ `${serverProperty.value}${serverPortProperty !== null ? ':' + serverPortProperty.value : ''}` }}
				</el-text>
			</dd>
		</template>
		<template v-if="serverProperty && serverSecuredPortProperty !== null">
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t('devicesModule.texts.connectors.securedServer') }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
				<el-text>
					{{ `${serverProperty.value}:${serverSecuredPortProperty.value}` }}
				</el-text>
			</dd>
		</template>
		<template
			v-for="property in infoProperties"
			:key="property.id"
		>
			<dt
				class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
				style="background: var(--el-fill-color-light)"
			>
				{{ t(`devicesModule.misc.property.connector.${property.identifier}`) }}
			</dt>
			<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
				<el-text>
					{{ property.value }}
				</el-text>
			</dd>
		</template>
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.connectors.service') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<el-tag
					v-if="props.service === null"
					size="small"
					type="danger"
				>
					{{ t('devicesModule.misc.missing') }}
				</el-tag>
				<el-tag
					v-else
					:type="props.service.running ? 'success' : 'danger'"
					size="small"
				>
					{{ props.service.running ? t('devicesModule.misc.state.running') : t('devicesModule.misc.state.stopped') }}
				</el-tag>
			</el-text>
		</dd>
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.connectors.bridges') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<i18n-t
					keypath="devicesModule.texts.connectors.bridgesCount"
					:plural="props.bridges.length"
				>
					<template #count>
						<strong>{{ props.bridges.length }}</strong>
					</template>
				</i18n-t>
			</el-text>
		</dd>
		<dt
			class="b-b b-b-solid b-r b-r-solid py-1 px-2 flex items-center justify-end"
			style="background: var(--el-fill-color-light)"
		>
			{{ t('devicesModule.texts.connectors.alerts') }}
		</dt>
		<dd class="col-start-2 b-b b-b-solid m-0 p-2 flex items-center min-w-[8rem]">
			<el-text>
				<el-tag
					size="small"
					:type="props.alerts.length === 0 ? 'success' : 'danger'"
				>
					<i18n-t
						keypath="devicesModule.texts.connectors.alertsCount"
						:plural="props.alerts.length"
					>
						<template #count>
							<strong>{{ props.alerts.length }}</strong>
						</template>
					</i18n-t>
				</el-tag>
			</el-text>
		</dd>
	</dl>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { I18nT, useI18n } from 'vue-i18n';

import { ElTag, ElText } from 'element-plus';

import { useWampV1Client } from '@fastybird/websockets-library';

import { useConnectorState } from '../../composables';
import { ConnectionState, ConnectorPropertyIdentifier, IConnectorDetailProps, IConnectorProperty, PropertyType, StateColor } from '../../types';

defineOptions({
	name: 'ConnectorDefaultConnectorDetail',
});

const props = defineProps<IConnectorDetailProps>();

const { t } = useI18n();

const { status: wsStatus } = useWampV1Client();

const { state: connectorState } = useConnectorState(props.connectorData.connector);

const dynamicProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return props.connectorData.properties.filter((property: IConnectorProperty): boolean => property.type.type === PropertyType.DYNAMIC);
});

const infoProperties = computed<IConnectorProperty[]>((): IConnectorProperty[] => {
	return dynamicProperties.value.filter((property: IConnectorProperty): boolean =>
		[ConnectorPropertyIdentifier.BAUD_RATE, ConnectorPropertyIdentifier.INTERFACE, ConnectorPropertyIdentifier.ADDRESS].includes(
			property.identifier as ConnectorPropertyIdentifier
		)
	);
});

const serverProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null => {
	return dynamicProperties.value.find((property: IConnectorProperty): boolean => property.identifier === ConnectorPropertyIdentifier.SERVER) ?? null;
});

const serverPortProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null => {
	return dynamicProperties.value.find((property: IConnectorProperty): boolean => property.identifier === ConnectorPropertyIdentifier.PORT) ?? null;
});

const serverSecuredPortProperty = computed<IConnectorProperty | null>((): IConnectorProperty | null => {
	return (
		dynamicProperties.value.find((property: IConnectorProperty): boolean => property.identifier === ConnectorPropertyIdentifier.SECURED_PORT) ?? null
	);
});

const stateColor = computed<StateColor>((): StateColor => {
	if (!wsStatus || [ConnectionState.UNKNOWN].includes(connectorState.value)) {
		return undefined;
	}

	if ([ConnectionState.CONNECTED, ConnectionState.READY, ConnectionState.RUNNING].includes(connectorState.value)) {
		return 'success';
	} else if ([ConnectionState.INIT].includes(connectorState.value)) {
		return 'info';
	} else if ([ConnectionState.DISCONNECTED, ConnectionState.STOPPED, ConnectionState.SLEEPING].includes(connectorState.value)) {
		return 'warning';
	}

	return 'danger';
});
</script>
