import { App, InjectionKey, inject as _inject, hasInjectionContext } from 'vue';

import { Emitter } from 'mitt';

import { Events } from './types';

export * from './types';

const eventBusKey: InjectionKey<Emitter<Events> | undefined> = Symbol('Tools-EventBus');

export function injectEventBus(app?: App): Emitter<Events> {
	if (app && app._context && app._context.provides && app._context.provides[eventBusKey]) {
		return app._context.provides[eventBusKey];
	}

	if (hasInjectionContext()) {
		const eventBus = _inject(eventBusKey, undefined);

		if (eventBus) {
			return eventBus;
		}
	}

	throw new Error('A event bus has not been provided.');
}

export function provideEventBus(app: App, eventBus: Emitter<Events>): void {
	app.provide(eventBusKey, eventBus);
}
