export enum ActionRoutes {
	WIDGET_DATA_SOURCE = 'fb.exchange.action.widget.dataSource',
}

export enum RoutingKeys {
	// Dashboards
	DASHBOARD_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.dashboard',
	DASHBOARD_DOCUMENT_CREATED = 'fb.exchange.module.document.created.dashboard',
	DASHBOARD_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.dashboard',
	DASHBOARD_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.dashboard',

	// Tabs
	TAB_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.tab',
	TAB_DOCUMENT_CREATED = 'fb.exchange.module.document.created.tab',
	TAB_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.tab',
	TAB_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.tab',

	// Groups
	GROUP_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.group',
	GROUP_DOCUMENT_CREATED = 'fb.exchange.module.document.created.group',
	GROUP_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.group',
	GROUP_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.group',

	// Widgets
	WIDGET_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.widget',
	WIDGET_DOCUMENT_CREATED = 'fb.exchange.module.document.created.widget',
	WIDGET_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.widget',
	WIDGET_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.widget',

	// Widget's display
	WIDGET_DISPLAY_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.widget.display',
	WIDGET_DISPLAY_DOCUMENT_CREATED = 'fb.exchange.module.document.created.widget.display',
	WIDGET_DISPLAY_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.widget.display',
	WIDGET_DISPLAY_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.widget.display',

	// Widget's data sources
	WIDGET_DATA_SOURCE_DOCUMENT_REPORTED = 'fb.exchange.module.document.reported.widget.dataSource',
	WIDGET_DATA_SOURCE_DOCUMENT_CREATED = 'fb.exchange.module.document.created.widget.dataSource',
	WIDGET_DATA_SOURCE_DOCUMENT_UPDATED = 'fb.exchange.module.document.updated.widget.dataSource',
	WIDGET_DATA_SOURCE_DOCUMENT_DELETED = 'fb.exchange.module.document.deleted.widget.dataSource',
}

export enum ExchangeCommand {
	SET = 'set',
	GET = 'get',
	REPORT = 'report',
}
