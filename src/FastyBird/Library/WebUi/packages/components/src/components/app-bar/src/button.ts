import { buttonEmits, buttonProps } from 'element-plus';

import { buildProps, definePropType } from '@fastybird/web-ui-utils';

import type { ExtractPropTypes } from 'vue';

export enum AppBarButtonAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	BACK = 'back',
	NONE = 'none',
}

export const appBarButtonAlignTypes = [
	AppBarButtonAlignTypes.LEFT,
	AppBarButtonAlignTypes.RIGHT,
	AppBarButtonAlignTypes.BACK,
	AppBarButtonAlignTypes.NONE,
] as const;

export type AppBarButtonAlign =
	AppBarButtonAlignTypes.LEFT | AppBarButtonAlignTypes.RIGHT | AppBarButtonAlignTypes.BACK | AppBarButtonAlignTypes.NONE;

export const appBarButtonProps = buildProps({
	...buttonProps,
	/**
	 * @description
	 */
	align: {
		type: definePropType<AppBarButtonAlign>(String),
		values: appBarButtonAlignTypes,
		default: AppBarButtonAlignTypes.NONE,
	},
	/**
	 * @description
	 */
	small: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	teleport: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	disabled: {
		type: definePropType<boolean>(Boolean),
		default: false,
	},
	/**
	 * @description
	 */
	classes: {
		type: definePropType<string[]>(Array),
		default: [],
	},
});

export type AppBarButtonProps = ExtractPropTypes<typeof appBarButtonProps>;

export const appBarButtonEmits = {
	...buttonEmits,
};

export type AppBarButtonEmits = typeof appBarButtonEmits;
