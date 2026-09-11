<template>
	<app-list-item :variant="isXSDevice ? ListItemVariantTypes.LIST : ListItemVariantTypes.DEFAULT">
		<template #icon>
			<properties-property-icon :property="props.property" />
		</template>

		<template #title>
			{{ props.property.title }}
		</template>

		<template #detail>
			<template v-if="props.property.dataType === DataType.SWITCH || (props.property.dataType === DataType.BOOLEAN && props.property.settable)">
				<actors-property-actor-switch
					:device="props.device"
					:channel="props.channel"
					:property="props.property"
				/>
			</template>

			<template v-else-if="props.property.dataType === DataType.BOOLEAN && !props.property.settable">
				<span class="font-size-[80%]">
					<template v-if="!isReady || !wsStatus">
						{{ t('devicesModule.states.notAvailable') }}
					</template>
					<template v-else-if="value">
						{{ t('devicesModule.states.on') }}
					</template>
					<template v-else>
						{{ t('devicesModule.states.off') }}
					</template>
				</span>
			</template>

			<template v-else>
				<span class="font-size-[80%] mr-1">
					<template v-if="!isReady || !wsStatus">
						{{ t('devicesModule.states.notAvailable') }}
					</template>
					<template v-else>
						{{ value }}
					</template>
				</span>
				<span
					v-if="props.property.unit !== null"
					class="font-size-[55%]"
				>
					{{ props.property.unit }}
				</span>
			</template>
		</template>
	</app-list-item>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

import { AppListItem, ListItemVariantTypes } from '@fastybird/application';
import { DataType } from '@fastybird/metadata-library';
import { useBreakpoints } from '@fastybird/tools';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';

import { useConnectorState, useDeviceState } from '../../composables';
import { PropertyType } from '../../types';
import ActorsPropertyActorSwitch from '../actors/actors-property-actor-switch.vue';
import PropertiesPropertyIcon from '../properties/properties-property-icon.vue';

import { IPropertyDefaultPropertyProps } from './property-default-property.types';

defineOptions({
	name: 'PropertyDefaultProperty',
});

const props = defineProps<IPropertyDefaultPropertyProps>();

const { t } = useI18n();
const { isXSDevice } = useBreakpoints();

const { isReady: isConnectorReady } = props.connector !== undefined ? useConnectorState(props.connector) : { isReady: undefined };
const { isReady: isDeviceReady } = props.device !== undefined ? useDeviceState(props.device) : { isReady: undefined };

const isReady = computed<boolean>((): boolean => {
	if (isConnectorReady !== undefined) {
		return isConnectorReady.value;
	}

	if (isDeviceReady !== undefined) {
		return isDeviceReady.value;
	}

	return false;
});

const value = computed<any>((): any => {
	if (props.property.type.type === PropertyType.VARIABLE) {
		return props.property.value;
	}

	return props.property.actualValue;
});

const { status: wsStatus } = useWampV1Client();
</script>
