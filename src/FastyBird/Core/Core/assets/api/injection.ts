import { App, InjectionKey, inject as _inject, hasInjectionContext } from 'vue';

import { AxiosInstance } from 'axios';

const backendKey: InjectionKey<AxiosInstance | undefined> = Symbol('Tools-Backend');

export function injectBackend(app?: App): AxiosInstance {
	if (app && app._context && app._context.provides && app._context.provides[backendKey]) {
		return app._context.provides[backendKey];
	}

	if (hasInjectionContext()) {
		const axios = _inject(backendKey, undefined);

		if (axios) {
			return axios;
		}
	}

	throw new Error('A backend instance has not been provided.');
}

export function provideBackend(app: App, axios: AxiosInstance): void {
	app.provide(backendKey, axios);
}
