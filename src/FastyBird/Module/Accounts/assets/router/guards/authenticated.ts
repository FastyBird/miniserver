import { RouteLocationNormalized } from 'vue-router';

import get from 'lodash.get';

import { IStoresManager } from '@fastybird/miniserver-core';

import { sessionStoreKey } from '../../configuration';

const authenticatedGuard = (storesManager: IStoresManager, to: RouteLocationNormalized): boolean | { name: string } | undefined => {
	const sessionStore = storesManager.getStore(sessionStoreKey);
	const toGuards = get(to.meta, 'guards', []);

	if (!sessionStore.isSignedIn() && Array.isArray(toGuards) && toGuards.includes('authenticated')) {
		return { name: 'accounts_module-sign_in' };
	}
};

export default authenticatedGuard;
