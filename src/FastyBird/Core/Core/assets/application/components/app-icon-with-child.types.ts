export type AppIconWithChildType = 'primary' | 'default' | 'info' | 'success' | 'warning' | 'danger';

export interface IAppIconWithChildProps {
	/** child icon variant */
	type?: AppIconWithChildType;
	/** main icon size */
	size?: number | string;
	/** main icon color */
	color?: string;
}
