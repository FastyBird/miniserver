<template>
	<div
		ref="element"
		:data-disabled="props.disabled"
		:class="ns.b()"
	>
		<template v-if="'left' in $slots">
			<div
				ref="left"
				:class="ns.e('left')"
			>
				<slot
					:close="close"
					name="left"
				/>
			</div>
		</template>

		<div
			v-if="props.disabled"
			ref="content"
			:class="ns.e('content')"
		>
			<slot
				:revealed="innerRevealed"
				:disabled="props.disabled"
				:reveal-left="revealLeft"
				:reveal-right="revealRight"
				:close="close"
				name="content"
			/>
		</div>

		<div
			v-else
			ref="content"
			v-touch-pan.horizontal.mouse.prevent.mousePrevent="onPan"
			:class="ns.e('content')"
		>
			<slot
				:revealed="innerRevealed"
				:disabled="props.disabled"
				:reveal-left="revealLeft"
				:reveal-right="revealRight"
				:close="close"
				name="content"
			/>
		</div>

		<template v-if="'right' in $slots">
			<div
				ref="right"
				:class="ns.e('right')"
			>
				<slot
					:close="close"
					name="right"
				/>
			</div>
		</template>
	</div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, ref, useSlots, watch } from 'vue';

import { useNamespace } from 'element-plus';

import vTouchPan from './app-swipe.directive';
import type { TouchHorizontalChanges } from './app-swipe.directive';
import type { IAppSwipeItemEmits, IAppSwipeItemProps, SwipeActionsOutDir } from './app-swipe.types';

defineOptions({
	name: 'AppSwipeItem',
});

const props = withDefaults(defineProps<IAppSwipeItemProps>(), {
	threshold: 45,
	revealed: false,
	disabled: false,
});

const emit = defineEmits<IAppSwipeItemEmits>();

const ns = useNamespace('swipe-item');

const slots = useSlots();

const translateX = (x: number): string => {
	if (x === 0) {
		return '';
	}

	return `translate3d(${x}px, 0, 0)`;
};

const areEqual = (a: any, b: any): boolean => {
	if (!a && !b) {
		return true;
	}

	return a === b;
};

const element = ref<HTMLElement | undefined>(undefined);
const content = ref<HTMLElement | undefined>(undefined);
const left = ref<HTMLElement | undefined>(undefined);
const right = ref<HTMLElement | undefined>(undefined);

const innerRevealed = ref<boolean | SwipeActionsOutDir>(props.revealed || false);
const animationFrame = ref<number | null>(null);

const leftActionsWidth = ref<number>(0);
const rightActionsWidth = ref<number>(0);
const startLeft = ref<number>(0);

const isActive = ref<boolean>(false);

const distanceSwiped = (): number => {
	const contentRect = content.value?.getBoundingClientRect();
	const elementRect = element.value?.getBoundingClientRect();

	return (contentRect ? contentRect.left : 0) - (elementRect ? elementRect.left : 0) - (element.value ? element.value?.clientLeft : 0);
};

const onPan = (pan: TouchHorizontalChanges): void => {
	if (props.disabled) {
		return;
	}

	if (pan.isFirst) {
		startListener(pan);

		return;
	}

	if (!isActive.value) {
		return;
	}

	if (pan.isFinal) {
		stopListener(pan);

		return;
	}

	swipeListener(pan);
};

const startListener = ({ distance }: { distance: { x: number; y: number } }): void => {
	element.value?.classList.add(ns.m('no-transition'));

	if (distance.y <= 5) {
		leftActionsWidth.value = left.value ? left.value.clientWidth : 0;
		rightActionsWidth.value = right.value ? right.value.clientWidth : 0;

		startLeft.value = distanceSwiped();
		isActive.value = true;

		emit('active', true);
	}
};

const swipeListener = ({ offset }: { offset: { x: number; y: number } }): void => {
	const newX = offset.x + startLeft.value;

	if (!('left' in slots) && newX > 0) {
		return animateSlide(0);
	}

	if (!('right' in slots) && newX < 0) {
		return animateSlide(0);
	}

	return animateSlide(offset.x + startLeft.value);
};

const stopListener = ({ offset, distance }: { distance: { x: number; y: number }; offset: { x: number; y: number } }): void => {
	element.value?.classList.remove(ns.m('no-transition'));

	isActive.value = false;

	emit('active', false);

	const newX = startLeft.value + offset.x;

	if (
		(startLeft.value === 0 && Math.abs(newX) <= props.threshold) ||
		(distance.x >= props.threshold &&
			((startLeft.value > 0 && distance.x < leftActionsWidth.value) || (startLeft.value < 0 && distance.x < rightActionsWidth.value)))
	) {
		reveal(false, false);

		return;
	}

	reveal(newX > 0 ? 'left' : 'right', false);
};

const shiftLeftActions = (newX: number): void => {
	if (!('left' in slots)) {
		return;
	}

	if (newX < 0) {
		newX = 0;
	}

	const actions = left.value;

	if (actions === undefined) {
		return;
	}

	const actionsWidth = leftActionsWidth.value;

	const progress = 1 - Math.min(newX / actionsWidth, 1);
	const deltaX = Math.min(newX, actionsWidth);

	const { children } = actions;
	const { length } = children;

	for (let i = 0; i < length; i++) {
		const child = children[i] as HTMLElement;
		const offsetLeft = actionsWidth - child.offsetLeft - child.offsetWidth;

		child.style.transform = translateX(deltaX + offsetLeft * progress);

		if (length > 1) {
			child.style.zIndex = `${length - i}`;
		}
	}
};

const shiftRightActions = (newX: number): void => {
	if (!('right' in slots)) {
		return;
	}

	if (newX > 0) {
		newX = 0;
	}

	const actions = right.value;

	if (actions === undefined) {
		return;
	}

	const actionsWidth = rightActionsWidth.value;

	const progress = 1 + Math.max(newX / actionsWidth, -1);
	const deltaX = Math.max(newX, -actionsWidth);
	const { children } = actions;

	for (let i = 0; i < children.length; i++) {
		const child = children[i] as HTMLElement;

		child.style.transform = translateX(deltaX - child.offsetLeft * progress);
	}
};

const reveal = (dir: SwipeActionsOutDir | boolean, recalculateWidth: boolean): void => {
	if (isActive.value && areEqual(innerRevealed.value, dir)) {
		return;
	}

	if ((dir === 'left' && left.value === undefined) || (dir === 'right' && right.value === undefined)) {
		dir = false;
	}

	innerRevealed.value = dir;

	emit('update:revealed', dir);

	// Close
	if (!dir) {
		animateSlide(0);

		emit('closed');

		return;
	}

	// Left
	if (dir === 'left' && left.value) {
		leftActionsWidth.value = recalculateWidth ? left.value.clientWidth : leftActionsWidth.value;
		animateSlide(leftActionsWidth.value);

		emit('revealed', { side: 'left', close: close });
		emit('leftRevealed', { close: close });

		return;
	}

	// Right
	if (dir === 'right' && right.value) {
		rightActionsWidth.value = recalculateWidth ? right.value.clientWidth : rightActionsWidth.value;
		animateSlide(-rightActionsWidth.value);

		emit('revealed', { side: 'right', close: close });
		emit('rightRevealed', { close: close });
	}
};

const animateSlide = (to: number): void => {
	if (animationFrame.value !== null) {
		cancelAnimationFrame(animationFrame.value);
	}

	animationFrame.value = requestAnimationFrame(() => {
		if (content.value !== undefined) {
			content.value.style.transform = translateX(to);
		}

		shiftLeftActions(to);
		shiftRightActions(to);
	});
};

const close = (): void => {
	if (isActive.value) return;

	reveal(false, true);
};

const revealLeft = (): void => {
	if (isActive.value || !left.value) {
		return;
	}

	reveal('left', true);
};

const revealRight = (): void => {
	if (isActive.value || !right.value) {
		return;
	}

	reveal('right', true);
};

onBeforeUnmount((): void => {
	if (animationFrame.value !== null) {
		cancelAnimationFrame(animationFrame.value);
	}
});

watch(
	(): boolean | SwipeActionsOutDir => props.revealed,
	(val: boolean | SwipeActionsOutDir) => {
		if (innerRevealed.value === val) {
			return;
		}

		reveal(val, true);
	}
);

defineExpose({
	/** @description */
	revealLeft,
	/** @description */
	revealRight,
	/** @description */
	close,
});
</script>
