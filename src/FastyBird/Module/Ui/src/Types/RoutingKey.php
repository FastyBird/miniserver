<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Types;

use FastyBird\Core\Exchange\Publisher;

/**
 * Exchange routing keys of the module's documents and actions
 *
 * The constant is the prefix the document keys are built from. It is not a key itself, so it is
 * not a case.
 */
enum RoutingKey: string
{

	public const string PREFIX = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.module.document';

	// WIDGETS
	case WIDGET_DOCUMENT_REPORTED = self::PREFIX . '.reported.widget';

	case WIDGET_DOCUMENT_CREATED = self::PREFIX . '.created.widget';

	case WIDGET_DOCUMENT_UPDATED = self::PREFIX . '.updated.widget';

	case WIDGET_DOCUMENT_DELETED = self::PREFIX . '.deleted.widget';

	// WIDGETS DATA SOURCES
	case WIDGET_DATA_SOURCE_DOCUMENT_REPORTED = self::PREFIX . '.reported.widget.dataSource';

	case WIDGET_DATA_SOURCE_DOCUMENT_CREATED = self::PREFIX . '.created.widget.dataSource';

	case WIDGET_DATA_SOURCE_DOCUMENT_UPDATED = self::PREFIX . '.updated.widget.dataSource';

	case WIDGET_DATA_SOURCE_DOCUMENT_DELETED = self::PREFIX . '.deleted.widget.dataSource';

	// WIDGETS DISPLAYS
	case WIDGET_DISPLAY_DOCUMENT_REPORTED = self::PREFIX . '.reported.widget.display';

	case WIDGET_DISPLAY_DOCUMENT_CREATED = self::PREFIX . '.created.widget.display';

	case WIDGET_DISPLAY_DOCUMENT_UPDATED = self::PREFIX . '.updated.widget.display';

	case WIDGET_DISPLAY_DOCUMENT_DELETED = self::PREFIX . '.deleted.widget.display';

	// GROUPS
	case GROUP_DOCUMENT_REPORTED = self::PREFIX . '.reported.group';

	case GROUP_DOCUMENT_CREATED = self::PREFIX . '.created.group';

	case GROUP_DOCUMENT_UPDATED = self::PREFIX . '.updated.group';

	case GROUP_DOCUMENT_DELETED = self::PREFIX . '.deleted.group';

	// DASHBOARDS
	case DASHBOARD_DOCUMENT_REPORTED = self::PREFIX . '.reported.dashboard';

	case DASHBOARD_DOCUMENT_CREATED = self::PREFIX . '.created.dashboard';

	case DASHBOARD_DOCUMENT_UPDATED = self::PREFIX . '.updated.dashboard';

	case DASHBOARD_DOCUMENT_DELETED = self::PREFIX . '.deleted.dashboard';

	// DASHBOARDS TABS
	case DASHBOARD_TAB_DOCUMENT_REPORTED = self::PREFIX . '.reported.dashboard.tab';

	case DASHBOARD_TAB_DOCUMENT_CREATED = self::PREFIX . '.created.dashboard.tab';

	case DASHBOARD_TAB_DOCUMENT_UPDATED = self::PREFIX . '.updated.dashboard.tab';

	case DASHBOARD_TAB_DOCUMENT_DELETED = self::PREFIX . '.deleted.dashboard.tab';

	// ACTIONS
	case WIDGET_DATA_SOURCE_ACTION = Publisher\MessagePublisher::ROUTING_KEY_PREFIX . '.action.widget.dataSource';

}
