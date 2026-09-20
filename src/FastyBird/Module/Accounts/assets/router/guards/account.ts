import { IStoresManager } from '@fastybird/miniserver-core';
import * as Sentry from '@sentry/vue';

import { sessionStoreKey } from '../../configuration';

const accountGuard = (storesManager: IStoresManager): boolean | { name: string } | undefined => {
	const sessionStore = storesManager.getStore(sessionStoreKey);

	const account = sessionStore.account();

	if (import.meta.env.PROD && account !== null) {
		Sentry.setUser({
			email: account.email?.address,
		});
	}

	return;
};

export default accountGuard;
