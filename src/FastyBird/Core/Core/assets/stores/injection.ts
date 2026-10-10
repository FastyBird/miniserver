import { App, InjectionKey, inject as _inject, hasInjectionContext } from 'vue';

import { StoresManager } from './manager';

const storesManagerKey: InjectionKey<StoresManager | undefined> = Symbol('Core-StoresManager');

export function injectStoresManager(app?: App): StoresManager {
	if (app && app._context && app._context.provides && app._context.provides[storesManagerKey]) {
		return app._context.provides[storesManagerKey];
	}

	if (hasInjectionContext()) {
		const manager = _inject(storesManagerKey, undefined);

		if (manager) {
			return manager;
		}
	}

	throw new Error('A stores manager has not been provided.');
}

export function provideStoresManager(app: App, manager: StoresManager): void {
	app.provide(storesManagerKey, manager);
}
