import { Plugin } from 'vue';
import { I18n } from 'vue-i18n';
import { Router } from 'vue-router';

import { Pinia } from 'pinia';

import { Client } from '@fastybird/vue-wamp-v1';

export * from './exchange';

export type InstallFunction = Plugin & { installed?: boolean };

export interface IUiModuleOptions {
	router?: Router;
	meta: IUiModuleMeta;
	configuration: IUiModuleConfiguration;
	store: Pinia;
	wsClient?: Client;
	i18n?: I18n;
}

export interface IUiModuleMeta {
	author: string;
	website: string;
	version: string;
	[key: string]: any;
}

export interface IUiModuleConfiguration {
	injectionKeys: {
		eventBusInjectionKey?: symbol | string;
	};
	[key: string]: any;
}

export interface IRoutes {
	root: string;

	dashboards: string;
	dashboardDetail: string;
	dashboardSettings: string;
	dashboardSettingsAddWidget: string;
	dashboardSettingsEditWidget: string;

	groups: string;
	groupDetail: string;
	groupSettings: string;
	groupSettingsAddWidget: string;
	groupSettingsEditWidget: string;

	widgets: string;
	widgetDetail: string;
	widgetSettings: string;
}

export enum FormResultTypes {
	NONE = 'none',
	WORKING = 'working',
	ERROR = 'error',
	OK = 'ok',
}

export type FormResultType = FormResultTypes.NONE | FormResultTypes.WORKING | FormResultTypes.ERROR | FormResultTypes.OK;
export interface DashboardDocument {
	id: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;
	tabs: TabDocument['id'][];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface TabDocument {
	id: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;
	dashboard: DashboardDocument['id'];
	widgets: WidgetDocument['id'][];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface GroupDocument {
	id: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	priority: number;
	widgets: WidgetDocument['id'][];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface WidgetDocument {
	id: string;
	type: string;
	source: string;
	identifier: string;
	name: string | null;
	comment: string | null;
	display: WidgetDisplayDocument['id'];
	data_sources: WidgetDataSourceDocument['id'][];
	tabs: TabDocument['id'][];
	groups: GroupDocument['id'][];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface WidgetDisplayDocument {
	id: string;
	type: string;
	source: string;
	identifier: string;
	params: object;
	widget: WidgetDocument['id'];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface WidgetDataSourceDocument {
	id: string;
	type: string;
	source: string;
	identifier: string;
	params: object;
	widget: WidgetDocument['id'];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}
