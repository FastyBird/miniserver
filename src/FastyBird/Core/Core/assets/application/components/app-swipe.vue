<template>
	<div
		ref="container"
		:data-disabled="props.disabled"
		:class="ns.b()"
	>
		<template
			v-for="(item, index) in props.items"
			:key="index"
		>
			<app-swipe-item
				ref="elements"
				v-model:revealed="innerRevealed[index]"
				:threshold="props.threshold"
				:disabled="props.itemDisabled(item) || props.disabled"
				:class="ns.e('item')"
				@closed="handleClosed(item, index)"
				@revealed="handleRevealed(item, index, $event)"
				@left-revealed="emit('leftRevealed', { index, item, close: $event.close })"
				@right-revealed="emit('rightRevealed', { index, item, close: $event.close })"
				@active="emit('active', $event)"
			>
				<template
					#content="{ revealed: rowRevealed, disabled: rowDisabled, revealLeft: rowRevealLeft, revealRight: rowRevealRight, close: rowClose }"
				>
					<slot
						:item="item"
						:index="index"
						:revealed="rowRevealed"
						:disabled="rowDisabled"
						:reveal-left="rowRevealLeft"
						:reveal-right="rowRevealRight"
						:close="rowClose"
					/>
				</template>

				<template
					v-if="'left' in $slots"
					#left="{ close: closeLeft }"
				>
					<slot
						:item="item"
						:index="index"
						:close="closeLeft"
						name="left"
					/>
				</template>

				<template
					v-if="'right' in $slots"
					#right="{ close: closeRight }"
				>
					<slot
						:item="item"
						:index="index"
						:close="closeRight"
						name="right"
					/>
				</template>
			</app-swipe-item>
		</template>
	</div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

import { useNamespace } from 'element-plus';

import AppSwipeItem from './app-swipe-item.vue';
import './app-swipe.scss';
import type { IAppSwipeEmits, IAppSwipeProps, SwipeActionsOutDir } from './app-swipe.types';

defineOptions({
	name: 'AppSwipe',
});

const props = withDefaults(defineProps<IAppSwipeProps>(), {
	threshold: 45,
	revealed: () => ({}),
	disabled: false,
	itemDisabled: () => false,
});

const emit = defineEmits<IAppSwipeEmits>();

const ns = useNamespace('swipe');

const container = ref<HTMLElement | null>(null);
const elements = ref<InstanceType<typeof AppSwipeItem>[]>([]);
const innerRevealed = ref<{ [key: number]: SwipeActionsOutDir }>(props.revealed || {});

const revealLeft = (index: number): void => {
	if (!(index in elements.value)) {
		return;
	}

	elements.value[index].revealLeft();
};

const revealRight = (index: number): void => {
	if (!(index in elements.value)) {
		return;
	}

	elements.value[index].revealRight();
};

const close = (index?: number): void => {
	if (elements.value.length === 0) {
		return;
	}

	if (index === undefined) {
		return Object.values(elements.value).forEach((element) => element.close());
	}

	if (!(index in elements.value)) {
		return;
	}

	elements.value[index].close();
};

const isRevealed = (index: number): boolean => {
	return index in innerRevealed.value || false;
};

const handleRevealed = (item: any, index: number, event: { side: SwipeActionsOutDir; close: () => void }): void => {
	emit('revealed', {
		index,
		item,
		side: event.side,
		close: event.close,
	});

	emitRevealed({
		...innerRevealed.value,
		[index]: event.side,
	});
};

const handleClosed = (item: any, index: number): void => {
	emit('closed', {
		index,
		item,
	});

	const newRevealed = innerRevealed.value;
	delete newRevealed[index];

	emitRevealed(newRevealed);
};

const emitRevealed = (val: { [key: number]: SwipeActionsOutDir }): void => {
	emit('update:revealed', val);
};

watch(
	() => props.revealed,
	(val): void => {
		innerRevealed.value = val as { [key: number]: SwipeActionsOutDir };
	}
);

watch(
	() => props.items,
	(): void => {
		emitRevealed({});
	}
);

defineExpose({
	/** @description */
	revealLeft,
	/** @description */
	revealRight,
	/** @description */
	isRevealed,
	/** @description */
	close,
});
</script>
