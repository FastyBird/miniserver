import { App, InjectionKey } from 'vue';
import mitt, { Emitter } from 'mitt';

const defaultOptions = {
	global: true,
	inject: true,
	globalPropertyName: 'eventBus',
};

interface IEventBusOptions {
	global?: boolean;
	inject?: boolean;
	globalPropertyName?: string;
}

type UserSignedEvent = 'in' | 'out';

type Events = {
	loadingOverlay?: number | boolean;
	userSigned: UserSignedEvent;
};

export const eventBus: Emitter<Events> = mitt<Events>();
export const key: InjectionKey<Emitter<Events>> = Symbol('eventBus');

export default {
	install: (app: App, options: IEventBusOptions): void => {
		const pluginOptions = {
			...defaultOptions,
			...options,
		};

		if (pluginOptions.global) {
			app.config.globalProperties[pluginOptions.globalPropertyName] = eventBus;
		}

		if (pluginOptions.inject) {
			app.provide(key, eventBus);
		}
	},
};
