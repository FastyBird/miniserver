import { Emitter, Handler } from 'mitt';

export type Events = {
	loadingOverlay?: number | boolean;
	userSigned: 'in' | 'out';
	userLocked: boolean;
	[key: string]: any;
};

export interface UseEventBus {
	eventBus: Emitter<Events>;
	register: <Key extends keyof Events>(event: Key, listener: Handler<Events[Key]>) => void;
	unregister: <Key extends keyof Events>(event: Key, listener: Handler<Events[Key]>) => void;
	emit: <Key extends keyof Events>(event: Key, payload: Events[Key]) => void;
}
