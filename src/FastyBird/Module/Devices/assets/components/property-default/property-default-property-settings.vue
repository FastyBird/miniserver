<template>
	<fb-list-item :variant="ListItemVariantTypes.LIST">
		<template #title>
			{{ props.title ?? props.property.title }}
		</template>

		<template
			v-if="props.title"
			#subtitle
		>
			{{ props.property.identifier }}
		</template>

		<template #detail>
			<el-button-group>
				<el-button
					:icon="FasPencil"
					size="small"
					plain
					@click="emit('edit', $event)"
				/>

				<el-button
					:icon="FasTrash"
					type="warning"
					size="small"
					plain
					@click="emit('remove', $event)"
				/>
			</el-button-group>
		</template>
	</fb-list-item>
</template>

<script setup lang="ts">
import { h } from 'vue';
import type { VNode } from 'vue';

import { ElButton, ElButtonGroup } from 'element-plus';

import { FbListItem, ListItemVariantTypes } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { IPropertyDefaultPropertySettingsProps } from './property-default-property-settings.types';

const FasPencil = (): VNode => h(Icon, { icon: 'fa6-solid:pencil' });
const FasTrash = (): VNode => h(Icon, { icon: 'fa6-solid:trash' });

defineOptions({
	name: 'PropertyDefaultPropertySettings',
});

const props = defineProps<IPropertyDefaultPropertySettingsProps>();

const emit = defineEmits<{
	(e: 'edit', event: Event): void;
	(e: 'remove', event: Event): void;
}>();
</script>
