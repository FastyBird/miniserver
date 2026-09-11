export type SwipeActionsOutDir = 'left' | 'right';

export interface IAppSwipeProps {
	/** items rendered as swipeable rows */
	items: any[];
	/** horizontal drag distance (px) needed to snap a row open */
	threshold?: number;
	/** currently revealed side per item index */
	revealed?: { [key: number]: SwipeActionsOutDir };
	/** disable swiping for every item */
	disabled?: boolean;
	/** per-item override to disable swiping */
	itemDisabled?: (item: any) => boolean;
}

export interface IAppSwipeEmits {
	(e: 'update:revealed', value: { [key: number]: SwipeActionsOutDir }): void;
	(e: 'active', value: boolean): void;
	(e: 'closed', value: { index: number; item: any }): void;
	(e: 'revealed', value: { index: number; item: any; side: SwipeActionsOutDir; close: () => void }): void;
	(e: 'leftRevealed', value: { index: number; item: any; close: () => void }): void;
	(e: 'rightRevealed', value: { index: number; item: any; close: () => void }): void;
}

export interface IAppSwipeItemProps {
	/** horizontal drag distance (px) needed to snap this row open */
	threshold?: number;
	/** currently revealed side of this row */
	revealed?: boolean | SwipeActionsOutDir;
	/** disable swiping for this row */
	disabled?: boolean;
}

export interface IAppSwipeItemEmits {
	(e: 'update:revealed', value: SwipeActionsOutDir | boolean): void;
	(e: 'active', value: boolean): void;
	(e: 'closed'): void;
	(e: 'revealed', value: { side: SwipeActionsOutDir; close: () => void }): void;
	(e: 'leftRevealed', value: { close: () => void }): void;
	(e: 'rightRevealed', value: { close: () => void }): void;
}
