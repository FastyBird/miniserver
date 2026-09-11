<template>
	<div
		v-if="error"
		class="flex flex-col justify-center w-full h-full"
	>
		<el-result>
			<template #icon>
				<fb-icon-with-child
					type="primary"
					:size="50"
				>
					<template #primary>
						<Icon icon="fa6-solid:box" />
					</template>
					<template #secondary>
						<Icon icon="fa6-solid:exclamation" />
					</template>
				</fb-icon-with-child>
			</template>

			<template #title>
				<h1>An error occurred</h1>
			</template>
			<template #sub-title>
				<el-text>Channel could not be loaded</el-text>
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

import { FbIconWithChild } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { ApplicationError } from '../../errors';

defineOptions({
	name: 'ChannelError',
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
