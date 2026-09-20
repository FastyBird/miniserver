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
	lock?: () => Promise<boolean>;
	canAccess: (resource: string, action: string) => Promise<boolean>;
}
