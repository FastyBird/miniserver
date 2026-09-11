export enum ListItemVariantTypes {
	DEFAULT = 'default',
	LIST = 'list',
}

export type ListItemVariant = `${ListItemVariantTypes}`;

export interface IAppListItemProps {
	/** list item variant */
	variant?: ListItemVariant;
}

export interface IAppListItemEmits {
	(e: 'click', evt: UIEvent): void;
}
