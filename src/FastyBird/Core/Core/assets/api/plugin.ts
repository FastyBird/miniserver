import { App } from 'vue';

import axios, { InternalAxiosRequestConfig } from 'axios';

import { ModulePrefix } from '../../metadata';
import { provideBackend } from '../backend';
import { useBackend } from '../composables';

interface IAxiosOptions {
	apiPrefix: string;
	apiTarget: string | null;
	apiKey: string | null;
	apiPrefixedModules: boolean;
}

export default {
	install: (app: App, options: IAxiosOptions): void => {
		const baseUrl = options.apiTarget !== null ? options.apiTarget : options.apiPrefix;

		axios.defaults.baseURL = baseUrl;

		const MAX_REQUESTS_COUNT = 10;
		const MAX_REQUESTS_COUNT_DELAY = 10;

		axios.interceptors.request.use((request): Promise<any> => {
			const { pendingRequests } = useBackend();

			request.baseURL = baseUrl;

			if (typeof request.headers !== 'undefined') {
				Object.assign(request.headers, { 'Content-Type': 'application/vnd.api+json' });
			}

			if (options.apiKey !== null) {
				if (typeof request.headers !== 'undefined') {
					Object.assign(request.headers, { 'X-Api-Key': options.apiKey });
				}
			}

			return new Promise((resolve) => {
				const interval = setInterval(() => {
					if (pendingRequests.value < MAX_REQUESTS_COUNT) {
						pendingRequests.value++;

						clearInterval(interval);

						resolve(request);
					}
				}, MAX_REQUESTS_COUNT_DELAY);
			});
		});

		// Modify api url prefix
		axios.interceptors.request.use((request): InternalAxiosRequestConfig => {
			if (!options.apiPrefixedModules && typeof request.url !== 'undefined') {
				Object.values(ModulePrefix).forEach((modulePrefix) => {
					request.url = request.url?.replace(new RegExp(`^/${modulePrefix}`, 'g'), ''); // Remove base path
				});
			}

			return request;
		});

		app.config.globalProperties['backend'] = axios;

		provideBackend(app, axios);
	},
};
