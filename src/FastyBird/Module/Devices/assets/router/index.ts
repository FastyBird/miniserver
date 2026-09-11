import { h } from 'vue';
import type { VNode } from 'vue';
import { RouteRecordRaw, Router } from 'vue-router';

import { Icon } from '@iconify/vue';

import { useRoutesNames } from '../composables';

const FasEthernet = (): VNode => h(Icon, { icon: 'fa6-solid:ethernet' });
const FasPlug = (): VNode => h(Icon, { icon: 'fa6-solid:plug' });

const routeNames = useRoutesNames();

const moduleRoutes: RouteRecordRaw[] = [
	{
		path: '/',
		name: routeNames.root,
		component: () => import('../layouts/layout-default.vue'),
		meta: {
			title: 'Devices module',
		},
		children: [
			{
				path: 'devices',
				name: routeNames.devices,
				component: () => import('../views/view-devices.vue'),
				meta: {
					guards: ['authenticated'],
					title: 'Devices',
					icon: FasPlug,
				},
				children: [
					{
						path: 'add',
						name: routeNames.deviceCreate,
						component: () => import('../views/view-device-settings.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
					},
					{
						path: ':id',
						name: routeNames.deviceDetail,
						component: () => import('../views/view-device-detail.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
						children: [
							{
								path: 'settings',
								name: routeNames.deviceSettings,
								component: () => import('../views/view-device-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'channels/add',
								name: routeNames.channelCreate,
								component: () => import('../views/view-channel-settings.vue'),
								props: (route) => ({ id: null, deviceId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: 'channels/:channelId',
								name: routeNames.channelDetail,
								component: () => import('../views/view-channel-detail.vue'),
								props: (route) => ({ id: route.params.channelId, deviceId: route.params.id }),
								meta: {
									guards: ['authenticated'],
								},
								children: [
									{
										path: 'settings',
										name: routeNames.channelSettings,
										component: () => import('../views/view-channel-settings.vue'),
										props: (route) => ({ id: route.params.channelId, deviceId: route.params.id }),
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
			{
				path: 'connectors',
				name: routeNames.plugins,
				component: () => import('../views/view-plugins.vue'),
				props: true,
				meta: {
					guards: ['authenticated'],
					title: 'Connectors',
					icon: FasEthernet,
				},
				children: [
					{
						path: 'install',
						name: routeNames.pluginInstall,
						component: () => import('../views/view-plugin-install.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
					},
					{
						path: ':plugin',
						name: routeNames.pluginDetail,
						component: () => import('../views/view-plugin-detail.vue'),
						props: true,
						meta: {
							guards: ['authenticated'],
						},
						children: [
							{
								path: 'add',
								name: routeNames.connectorCreate,
								component: () => import('../views/view-connector-settings.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
							},
							{
								path: ':id',
								name: routeNames.connectorDetail,
								component: () => import('../views/view-connector-detail.vue'),
								props: true,
								meta: {
									guards: ['authenticated'],
								},
								children: [
									{
										path: 'devices/add',
										name: routeNames.connectorDetailDeviceCreate,
										component: () => import('../views/view-device-settings.vue'),
										props: (route) => ({ id: null, connectorId: route.params.id }),
										meta: {
											guards: ['authenticated'],
										},
									},
									{
										path: 'devices/:deviceId',
										name: routeNames.connectorDetailDeviceDetail,
										component: () => import('../views/view-device-detail.vue'),
										props: (route) => ({ id: route.params.deviceId, connectorId: route.params.id }),
										meta: {
											guards: ['authenticated'],
										},
										children: [
											{
												path: 'settings',
												name: routeNames.connectorDetailDeviceSettings,
												component: () => import('../views/view-device-settings.vue'),
												props: (route) => ({ id: route.params.deviceId, connectorId: route.params.id }),
												meta: {
													guards: ['authenticated'],
												},
											},
											{
												path: 'channels/add',
												name: routeNames.connectorDetailDeviceDetailChannelCreate,
												component: () => import('../views/view-channel-settings.vue'),
												props: (route) => ({ id: null, deviceId: route.params.deviceId, connectorId: route.params.id }),
												meta: {
													guards: ['authenticated'],
												},
											},
											{
												path: 'channels/:channelId',
												name: routeNames.connectorDetailDeviceDetailChannelDetail,
												component: () => import('../views/view-channel-detail.vue'),
												props: (route) => ({ id: route.params.channelId, deviceId: route.params.deviceId, connectorId: route.params.id }),
												meta: {
													guards: ['authenticated'],
												},
												children: [
													{
														path: 'settings',
														name: routeNames.connectorDetailDeviceDetailChannelSettings,
														component: () => import('../views/view-channel-settings.vue'),
														props: (route) => ({ id: route.params.channelId, deviceId: route.params.deviceId, connectorId: route.params.id }),
														meta: {
															guards: ['authenticated'],
														},
													},
												],
											},
										],
									},
									{
										path: 'settings',
										name: routeNames.connectorSettings,
										component: () => import('../views/view-connector-settings.vue'),
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
		],
	},
];

export default (router: Router): void => {
	moduleRoutes.forEach((route) => {
		router.addRoute('root', route);
	});
};
