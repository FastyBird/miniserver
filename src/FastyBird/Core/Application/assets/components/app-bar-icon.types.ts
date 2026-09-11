export enum AppBarIconAlignTypes {
	LEFT = 'left',
	RIGHT = 'right',
	NONE = 'none',
}

export type AppBarIconAlign = `${AppBarIconAlignTypes}`;

export interface IAppBarIconProps {
	align?: AppBarIconAlign;
	teleport?: boolean;
}
