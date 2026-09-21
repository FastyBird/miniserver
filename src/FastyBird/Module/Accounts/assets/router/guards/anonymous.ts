import { RouteLocationNormalized } from 'vue-router';

import get from 'lodash.get';

import { IStoresManager } from '@fastybird/miniserver-core';

import { sessionStoreKey } from '../../configuration';

const anonymousGuard = (storesManager: IStoresManager, to: RouteLocationNormalized): boolean | { name: string } | undefined => {
	const sessionStore = storesManager.getStore(sessionStoreKey);
	const toGuards = get(to.meta, 'guards', []);

	if (sessionStore.isSignedIn() && Array.isArray(toGuards) && toGuards.includes('anonymous')) {
		return { name: 'root' };
	}
};

export default anonymousGuard;
