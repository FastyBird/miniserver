import { App } from 'vue';

import mitt, { Emitter } from 'mitt';

import { provideEventBus } from './injection';
import { Events } from './types';

export default {
	install: (app: App): void => {
		const eventBus: Emitter<Events> = mitt<Events>();

		app.config.globalProperties['eventBus'] = eventBus;

		provideEventBus(app, eventBus);
	},
};
