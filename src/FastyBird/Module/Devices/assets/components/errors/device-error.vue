<template>
	<div
		v-if="error"
		class="flex flex-col justify-center w-full h-full"
	>
		<el-result>
			<template #icon>
				<app-icon-with-child
					type="primary"
					:size="50"
				>
					<template #primary>
						<Icon icon="fa6-solid:plug" />
					</template>
					<template #secondary>
						<Icon icon="fa6-solid:exclamation" />
					</template>
				</app-icon-with-child>
			</template>

			<template #title>
				<h1>An error occurred</h1>
			</template>
			<template #sub-title>
				<el-text>Device could not be loaded</el-text>
			</template>
		</el-result>
	</div>

	<template v-else>
		<slot />
	</template>
</template>

<script setup lang="ts">
import { onErrorCaptured, ref } from 'vue';
import type { ComponentPublicInstance } from 'vue';

import { ElResult, ElText } from 'element-plus';

import { AppIconWithChild } from '@fastybird/application';
import { Icon } from '@iconify/vue';

import { ApplicationError } from '../../errors';

defineOptions({
	name: 'DeviceError',
});

const error = ref<unknown | null>(null);

onErrorCaptured((err: unknown, _vm: ComponentPublicInstance | null, info: string): boolean => {
	error.value = err;

	if (err instanceof ApplicationError) {
		console.log(err.type);
	}

	console.log(err, info);

	return false; // prevent further propagation
});
</script>
