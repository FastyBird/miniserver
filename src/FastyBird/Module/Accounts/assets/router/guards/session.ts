import { RouteLocation } from 'vue-router';

import { jwtDecode } from 'jwt-decode';
import get from 'lodash.get';

import { IStoresManager } from '@fastybird/miniserver-core';
import * as Sentry from '@sentry/vue';

import { sessionStoreKey } from '../../configuration';

const sessionGuard = async (storesManager: IStoresManager): Promise<boolean | RouteLocation | undefined> => {
	const sessionStore = storesManager.getStore(sessionStoreKey);

	sessionStore.initialize();

	// ///////////////////////////////
	// Both tokens cookies are present
	// ///////////////////////////////
	if (sessionStore.accessToken() !== null && sessionStore.refreshToken() !== null) {
		const decodedAccessToken = jwtDecode<{ exp: number; user: string }>(sessionStore.accessToken()!);
		const decodedRefreshToken = jwtDecode<{ exp: number; user: string }>(sessionStore.refreshToken()!);

		// Check if refresh token is expired
		if (new Date().getTime() / 1000 >= new Date(get(decodedRefreshToken, 'exp', 0) * 1000).getTime() / 1000) {
			sessionStore.clear();

			console.log('ROUTE GUARD: Refresh token is expired');

			return;
		}

		// Check if access token is expired
		if (new Date().getTime() / 1000 >= new Date(get(decodedAccessToken, 'exp', 0) * 1000).getTime() / 1000) {
			// ///////////////////////////////
			// Perform session refresh process
			// ///////////////////////////////
			try {
				if (!(await sessionStore.refresh())) {
					// Session refreshing failed
					sessionStore.clear();

					console.log('ROUTE GUARD: Session refresh failed');

					return;
				}
			} catch (e: unknown) {
				// Session refreshing failed
				sessionStore.clear();

				if (import.meta.env.PROD) {
					Sentry.captureException(e);
				} else {
					console.log('ROUTE GUARD: Session refresh failed with unknown error');
				}

				return;
			}
		}

		if (sessionStore.accountId() === null) {
			// Session store failed
			sessionStore.clear();

			return;
		}

		if (sessionStore.account() === null) {
			// ///////////////////////////////////////
			// Session account is not loaded in store
			// Try to load session account from server
			// ///////////////////////////////////////
			try {
				if (!(await sessionStore.fetch())) {
					// Fetching account failed
					sessionStore.clear();

					console.log('ROUTE GUARD: Account fetch failed');

					return;
				}
			} catch (e: unknown) {
				// Fetching account failed
				sessionStore.clear();

				if (import.meta.env.PROD) {
					Sentry.captureException(e);
				} else {
					console.log('ROUTE GUARD: Account fetch failed with unknown error');
				}
			}
		}

		// /////////////////////////////////////////
		// Try to refresh session with refresh token
		// /////////////////////////////////////////
	} else if (sessionStore.refreshToken() !== null) {
		try {
			if (!(await sessionStore.refresh())) {
				// Session refreshing failed
				sessionStore.clear();

				console.log('ROUTE GUARD: Session refresh failed');

				return;
			}
		} catch (e: unknown) {
			// Session refreshing failed
			sessionStore.clear();

			if (import.meta.env.PROD) {
				Sentry.captureException(e);
			} else {
				console.log('ROUTE GUARD: Session refresh failed with unknown error');
			}
		}

		// ///////////////////////////////////////////////////////////////////////
		// Both tokens are missing, we could not continue with authenticated user
		// ///////////////////////////////////////////////////////////////////////
	} else {
		sessionStore.clear();
	}
};

export default sessionGuard;
