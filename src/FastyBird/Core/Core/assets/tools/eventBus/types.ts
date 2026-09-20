export type Events = {
	loadingOverlay?: number | boolean;
	userSigned: 'in' | 'out';
	userLocked: boolean;
	[key: string]: any;
};
