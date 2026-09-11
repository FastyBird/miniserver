<template>
	<el-icon :size="props.size">
		<Icon icon="fa6-solid:plug-circle-bolt" />
	</el-icon>
</template>

<script setup lang="ts">
import { ElIcon } from 'element-plus';

import { Icon } from '@iconify/vue';

import { IPluginsPluginDefaultIconProps } from './plugins-plugin-default-icon.types';

defineOptions({
	name: 'PluginsPluginDefaultIcon',
});

const props = withDefaults(defineProps<IPluginsPluginDefaultIconProps>(), {
	size: 100,
});
</script>
