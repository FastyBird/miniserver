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
				path: 'dashboards',
				name: routeNames.dashboards,
				component: () => import('../views/view-dashboards.vue'),
				meta: {
					guards: ['authenticated'],
				},
				children: [
					{
						path: ':id',
						name: routeNames.dashboardDetail,
						component: () => import('../views/view-dashboard-detail.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
						children: [
							{
								path: 'settings',
								name: routeNames.dashboardSettings,
								component: () => import('../views/view-dashboard-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/widget/add',
								name: routeNames.dashboardSettingsAddWidget,
								component: () => import('../views/view-widget-settings.vue'),
								props: (route) => ({ id: null, dashboardId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/widget/:widgetId',
								name: routeNames.dashboardSettingsEditWidget,
								component: () => import('../views/view-widget-settings.vue'),
								props: (route) => ({ id: route.params.widgetId, dashboardId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
						],
					},
				],
			},
			{
				path: 'groups',
				name: routeNames.groups,
				component: () => import('../views/view-groups.vue'),
				meta: {
					guards: ['authenticated'],
				},
				children: [
					{
						path: ':id',
						name: routeNames.groupDetail,
						component: () => import('../views/view-group-detail.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
						children: [
							{
								path: 'settings',
								name: routeNames.groupSettings,
								component: () => import('../views/view-group-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/widget/add',
								name: routeNames.groupSettingsAddWidget,
								component: () => import('../views/view-widget-settings.vue'),
								props: (route) => ({ id: null, groupId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'settings/widget/:widgetId',
								name: routeNames.groupSettingsEditWidget,
								component: () => import('../views/view-widget-settings.vue'),
								props: (route) => ({ id: route.params.widgetId, groupId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
						],
					},
				],
			},
			{
				path: 'widgets',
				name: routeNames.widgets,
				component: () => import('../views/view-widgets.vue'),
				meta: {
					guards: ['authenticated'],
				},
				children: [
					{
						path: ':id',
						name: routeNames.widgetDetail,
						component: () => import('../views/view-widget-detail.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
						children: [
							{
								path: 'settings',
								name: routeNames.widgetSettings,
								component: () => import('../views/view-widget-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
						],
					},
				],
			},
		],
	},
];

export default (router: Router): void => {
	moduleRoutes.forEach((route) => {
		router.addRoute('root', route);
	});
};
