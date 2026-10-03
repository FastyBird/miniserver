export * from './exchange';

export interface IUiModuleMeta {
	author: string;
	website: string;
	version: string;
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

// Documents\Widgets\Displays\*::toArray(). The display parameters are flattened, and each
// display type sends only its own subset of them.
export interface WidgetDisplayDocument {
	id: string;
	type: string;
	source: string;
	widget: WidgetDocument['id'];
	minimum_value?: number | null;
	maximum_value?: number | null;
	step_value?: number | null;
	precision?: number | null;
	enable_min_max?: boolean | null;
	icon?: string | null;
	owner: string | null;
	created_at: string | null;
	updated_at: string | null;
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
