import { ComputedRef } from 'vue';

export interface IAccountDetails {
	name: string;
	email: string;
	avatar?: string;
}

export interface IAccountManager {
	isSignedIn: ComputedRef<boolean>;
	isLocked: ComputedRef<boolean>;
	details: ComputedRef<IAccountDetails | null>;
	signIn: (credentials: { username: string; password: string }) => Promise<boolean>;
	signOut: () => Promise<boolean>;
	/**
	 * The access token the session signs in with; a different one once the session was refreshed
	 */
	accessToken?: ComputedRef<string | null>;
	/**
	 * Makes sure the session can still sign in, refreshing it when its access token has expired.
	 * Resolves false only when it cannot: nobody is signed in, or the server refused the refresh.
	 * A refresh which did not get an answer resolves true, the session may still be valid.
	 */
	ensureSession?: () => Promise<boolean>;
	lock?: () => Promise<boolean>;
	canAccess: (resource: string, action: string) => Promise<boolean>;
}
