import type { PropType } from 'vue';

import { buttonEmits, buttonProps } from 'element-plus';

export enum AppBarButtonAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	BACK = 'back',
	NONE = 'none',
}

export type AppBarButtonAlign = `${AppBarButtonAlignTypes}`;

export const appBarButtonProps = {
	...buttonProps,
	align: { type: String as PropType<AppBarButtonAlign>, default: AppBarButtonAlignTypes.NONE },
	small: { type: Boolean, default: false },
	teleport: { type: Boolean, default: false },
	disabled: { type: Boolean, default: false },
	classes: { type: Array as PropType<string[]>, default: (): string[] => [] },
};

export const appBarButtonEmits = { ...buttonEmits };
