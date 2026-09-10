<template>
	<RouterView v-if="populated" />
</template>

<script setup lang="ts">
import { onBeforeMount, ref } from 'vue';

import get from 'lodash.get';

import { useDashboards, useGroups, useWidgetDataSources, useWidgetDisplay, useWidgets } from '../models';
import { DashboardDocument, GroupDocument, WidgetDataSourceDocument, WidgetDisplayDocument, WidgetDocument } from '../types';

defineOptions({
	name: 'LayoutDefault',
});

const dashboardsStore = useDashboards();
const groupsStore = useGroups();
const widgetsStore = useWidgets();
const widgetDataSourcesStore = useWidgetDataSources();
const widgetDisplayStore = useWidgetDisplay();

const populated = ref<boolean>(false);

onBeforeMount(async (): Promise<void> => {
	// DASHBOARDS
	const ssrDashboardsData: DashboardDocument[] | null = get(window, '__UI_MODULE_DASHBOARDS__', []);

	if (ssrDashboardsData !== null) {
		await dashboardsStore.insertData({
			data: ssrDashboardsData,
		});
	}

	const ssrDashboardData: DashboardDocument | null = get(window, '__UI_MODULE_DASHBOARD__', null);

	if (ssrDashboardData !== null) {
		await dashboardsStore.insertData({
			data: ssrDashboardData,
		});
	}

	// GROUPS
	const ssrGroupsData: GroupDocument[] | null = get(window, '__UI_MODULE_GROUPS__', []);

	if (ssrGroupsData !== null) {
		await groupsStore.insertData({
			data: ssrGroupsData,
		});
	}

	const ssrGroupData: GroupDocument | null = get(window, '__UI_MODULE_GROUP__', null);

	if (ssrGroupData !== null) {
		await groupsStore.insertData({
			data: ssrGroupData,
		});
	}

	// WIDGETS
	const ssrWidgetsData: WidgetDocument[] | null = get(window, '__UI_MODULE_WIDGETS__', []);

	if (ssrWidgetsData !== null) {
		await widgetsStore.insertData({
			data: ssrWidgetsData,
		});
	}

	const ssrWidgetsDataSourcesData: WidgetDataSourceDocument[] | null = get(window, '__UI_MODULE_WIDGETS_DATA_SOURCES__', []);

	if (ssrWidgetsDataSourcesData !== null) {
		await widgetDataSourcesStore.insertData({
			data: ssrWidgetsDataSourcesData,
		});
	}

	const ssrWidgetsDisplaysData: WidgetDisplayDocument[] | null = get(window, '__UI_MODULE_WIDGETS_DISPLAYS__', null);

	if (ssrWidgetsDisplaysData !== null) {
		await widgetDisplayStore.insertData({
			data: ssrWidgetsDisplaysData,
		});
	}

	const ssrWidgetData: WidgetDocument | null = get(window, '__UI_MODULE_WIDGET__', null);

	if (ssrWidgetData !== null) {
		await widgetsStore.insertData({
			data: ssrWidgetData,
		});
	}

	const ssrWidgetDataSourcesData: WidgetDataSourceDocument[] | null = get(window, '__UI_MODULE_WIDGET_DATA_SOURCES__', []);

	if (ssrWidgetDataSourcesData !== null) {
		await widgetDataSourcesStore.insertData({
			data: ssrWidgetDataSourcesData,
		});
	}

	const ssrWidgetDisplayData: WidgetDisplayDocument | null = get(window, '__UI_MODULE_WIDGET_DISPLAY__', null);

	if (ssrWidgetDisplayData !== null) {
		await widgetDisplayStore.insertData({
			data: ssrWidgetDisplayData,
		});
	}

	populated.value = true;
});
</script>
