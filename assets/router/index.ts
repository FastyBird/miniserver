import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import NProgress from 'nprogress';

const routes: RouteRecordRaw[] = [
	{
		path: '/',
		name: 'root',
		component: () => import('@/layouts/layout-default.vue'),
		redirect: () => ({ name: 'application-home' }),
		children: [
			{
				path: '',
				name: 'application-home',
				component: () => import('@/views/view-home.vue'),
				meta: {
					guards: ['authenticated'],
				},
			},
		],
	},
];

const router = createRouter({
	history: createWebHistory(),
	routes,
});

router.beforeEach(() => {
	NProgress.start();
});

router.afterEach(() => {
	NProgress.done();
});

export default router;
