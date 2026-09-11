<template>
	<el-icon :size="props.size">
		<Icon icon="fa6-solid:lightbulb" />
	</el-icon>
</template>

<script setup lang="ts">
import { ElIcon } from 'element-plus';

import { Icon } from '@iconify/vue';

import { IPropertiesPropertyIconProps } from './properties-property-icon.types';

defineOptions({
	name: 'PropertiesPropertyIcon',
});

const props = defineProps<IPropertiesPropertyIconProps>();
</script>
