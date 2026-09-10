import { RouteRecordRaw, Router } from 'vue-router';

import { useRoutesNames } from '../composables';

const { routeNames } = useRoutesNames();

const moduleRoutes: RouteRecordRaw[] = [
	{
		path: '/',
		name: routeNames.root,
		component: () => import('../layouts/layout-default.vue'),
		children: [
			{
				path: 'triggers',
				name: routeNames.triggers,
				component: () => import('../views/view-triggers.vue'),
				meta: {
					guards: ['authenticated'],
				},
			},
		],
	},
];

export default (router: Router): void => {
	moduleRoutes.forEach((route) => {
		router.addRoute('/', route);
	});
};
