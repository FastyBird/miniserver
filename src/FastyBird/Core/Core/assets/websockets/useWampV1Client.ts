import { InjectionKey, computed, inject } from 'vue';

import { IWampClient, IWebSocketResult, RpCallResponse } from './types';

export const key: InjectionKey<IWampClient> = Symbol('wampClient');

export function useWampV1Client<T>(): IWebSocketResult<T> {
	const wampClient = inject(key);

	return {
		status: computed<boolean>((): boolean => {
			if (wampClient === undefined) {
				return false;
			}

			return wampClient.isConnected.value;
		}),
		open: (): void => {
			if (wampClient === undefined) {
				throw new Error('WAMP client is not created');
			}

			wampClient.open();
		},
		close: (): void => {
			if (wampClient === undefined) {
				throw new Error('WAMP client is not created');
			}

			wampClient.close();
		},
		call: (...args): Promise<RpCallResponse<T>> => {
			if (wampClient === undefined) {
				throw new Error('WAMP client is not created');
			}

			return wampClient.call(...args);
		},
		client: wampClient,
	};
}
