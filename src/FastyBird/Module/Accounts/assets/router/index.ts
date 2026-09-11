import { App, h } from 'vue';
import type { VNode } from 'vue';
import { RouteRecordRaw, Router } from 'vue-router';

import { injectStoresManager } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import { useRoutesNames } from '../composables';
import { accountGuard, anonymousGuard, authenticatedGuard, sessionGuard } from '../router/guards';

const FasKey = (): VNode => h(Icon, { icon: 'fa6-solid:key' });
const FasUser = (): VNode => h(Icon, { icon: 'fa6-solid:user' });

const { routeNames } = useRoutesNames();

const moduleRoutes: RouteRecordRaw[] = [
	{
		path: '/',
		name: routeNames.root,
		component: () => import('../layouts/layout-default.vue'),
		meta: {
			title: 'Accounts module',
		},
		children: [
			{
				path: 'sign',
				name: 'accounts_module-sign',
				component: () => import('../layouts/layout-sign.vue'),
				meta: {
					title: 'Sign',
				},
				redirect: () => ({ name: routeNames.signIn }),
				children: [
					{
						path: 'in',
						name: routeNames.signIn,
						component: () => import('../views/view-sign-in.vue'),
						meta: {
							guards: ['anonymous'],
							title: 'Sign in',
						},
					},
					{
						path: 'up',
						name: routeNames.signUp,
						component: () => import('../views/view-sign-up.vue'),
						meta: {
							guards: ['anonymous'],
							title: 'Sign up',
						},
					},
				],
			},
			{
				path: 'reset-password',
				name: routeNames.resetPassword,
				component: () => import('../views/view-reset-password.vue'),
				meta: {
					guards: ['anonymous'],
					title: 'Reset password',
					icon: FasKey,
				},
			},
			{
				path: 'account',
				name: routeNames.account,
				component: () => import('../layouts/layout-account.vue'),
				meta: {
					title: 'Your account',
					icon: FasUser,
				},
				redirect: () => ({ name: routeNames.accountProfile }),
				children: [
					{
						path: 'profile',
						name: routeNames.accountProfile,
						component: () => import('../views/view-profile.vue'),
						meta: {
							guards: ['authenticated'],
							title: 'Edit profile',
						},
					},
					{
						path: 'password',
						name: routeNames.accountPassword,
						component: () => import('../views/view-password.vue'),
						meta: {
							guards: ['authenticated'],
							title: 'Edit password',
						},
					},
				],
			},
		],
	},
];

export default (router: Router, app: App): void => {
	const storesManager = injectStoresManager(app);

	moduleRoutes.forEach((route) => {
		router.addRoute('root', route);
	});

	// Register router guards
	router.beforeEach(async () => {
		return await sessionGuard(storesManager);
	});
	router.beforeEach((to) => {
		return anonymousGuard(storesManager, to);
	});
	router.beforeEach((to) => {
		return authenticatedGuard(storesManager, to);
	});
	router.beforeEach(() => {
		return accountGuard(storesManager);
	});
};
