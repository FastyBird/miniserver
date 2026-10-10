import { Handler } from 'mitt';

import { injectEventBus } from './injection';
import { Events, UseEventBus } from './types';

export function useEventBus(): UseEventBus {
	const eventBus = injectEventBus();

	const register = <Key extends keyof Events>(event: Key, listener: Handler<Events[Key]>): void => {
		eventBus.on(event, listener);
	};

	const unregister = <Key extends keyof Events>(event: Key, listener: Handler<Events[Key]>): void => {
		eventBus.off(event, listener);
	};

	const emit = <Key extends keyof Events>(event: Key, payload: Events[Key]): void => {
		eventBus.emit(event, payload);
	};

	return {
		eventBus,
		register,
		unregister,
		emit,
	};
}
