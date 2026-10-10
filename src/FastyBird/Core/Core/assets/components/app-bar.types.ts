export interface IAppBarProps {
	menuButtonHidden?: boolean;
	menuCollapsed?: boolean;
}

export interface IAppBarEmits {
	(e: 'toggleMenu', evt: UIEvent): void;
}
