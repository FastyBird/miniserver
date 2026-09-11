import type { Component } from 'vue';

export enum AppBarHeadingAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	CENTER = 'center',
}

export type AppBarHeadingAlign = `${AppBarHeadingAlignTypes}`;

export interface IAppBarHeadingProps {
	align?: AppBarHeadingAlign;
	/** icon of the heading; can also be passed with a named slot */
	icon?: string | Component;
	teleport?: boolean;
}
