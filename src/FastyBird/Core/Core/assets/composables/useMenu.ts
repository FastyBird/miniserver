import { RouteRecordRaw, useRouter } from 'vue-router';

declare type RouteRecord = Omit<RouteRecordRaw, 'children'> & {
	children: { [key: string]: RouteRecord };
};

const getRouteName = (record: RouteRecordRaw): string => {
	if (typeof record.name === 'string') {
		return record.name;
	} else if (typeof record.name === 'symbol') {
		return record.name.toString();
	}

	return record.path;
};

const buildRouteTree = (routes: RouteRecordRaw[]): { [key: string]: RouteRecord } => {
	const routeMap: { [key: string]: RouteRecord } = {};

	for (const route of routes) {
		routeMap[getRouteName(route)] = { ...route, children: {} };

		if (route.children && Array.isArray(route.children)) {
			routeMap[getRouteName(route)].children = buildRouteTree(route.children);
		}
	}

	return routeMap;
};

const filterRouteTree = (routes: { [key: string]: RouteRecord }): { [key: string]: RouteRecord } => {
	const routeMap: { [key: string]: RouteRecord } = {};

	mainLoop: for (const search of Object.keys(routes)) {
		for (const name of Object.keys(routes)) {
			if (name !== search && routes[name].children && findRoute(search, routes[name].children)) {
				continue mainLoop;
			}
		}

		routeMap[search] = routes[search];
	}

	return routeMap;
};

const findRoute = (search: string, routes: { [key: string]: RouteRecord }): boolean => {
	for (const route of Object.keys(routes)) {
		if (route === search) {
			return true;
		}

		if (routes[route].children) {
			if (findRoute(search, routes[route].children)) {
				return true;
			}
		}
	}

	return false;
};

export function useMenu(): {
	mainMenuItems: { [key: string]: RouteRecord };
	userMenuItems: { [key: string]: RouteRecord };
} {
	const router = useRouter();

	const routesTree = filterRouteTree(buildRouteTree(router.getRoutes()));

	return {
		mainMenuItems: routesTree,
		userMenuItems: routesTree,
	};
}
