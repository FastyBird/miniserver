<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UiModule!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           18.03.20
 */

namespace FastyBird\Module\Ui;

use FastyBird\Core\Constants as Metadata;

/**
 * Service constants
 *
 * @package        FastyBird:UiModule!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * MODULE API ROUTING
	 */

	public const ROUTE_NAME_DASHBOARDS = 'dashboards';

	public const ROUTE_NAME_DASHBOARD = 'dashboard';

	public const ROUTE_NAME_DASHBOARD_RELATIONSHIP = 'dashboard.relationship';

	public const ROUTE_NAME_DASHBOARD_TABS = 'dashboard.tabs';

	public const ROUTE_NAME_DASHBOARD_TAB = 'dashboard.tab';

	public const ROUTE_NAME_DASHBOARD_TAB_RELATIONSHIP = 'dashboard.tab.relationship';

	public const ROUTE_NAME_GROUPS = 'dashboard.groups';

	public const ROUTE_NAME_GROUP = 'dashboard.group';

	public const ROUTE_NAME_GROUP_RELATIONSHIP = 'dashboard.group.relationship';

	public const ROUTE_NAME_WIDGETS = 'widgets';

	public const ROUTE_NAME_WIDGET = 'widget';

	public const ROUTE_NAME_WIDGET_RELATIONSHIP = 'widget.relationship';

	public const ROUTE_NAME_WIDGET_DISPLAY = 'widget.display';

	public const ROUTE_NAME_WIDGET_DISPLAY_RELATIONSHIP = 'widget.display.relationship';

	public const ROUTE_NAME_WIDGET_DATA_SOURCES = 'widget.widget.data-sources';

	public const ROUTE_NAME_WIDGET_DATA_SOURCE = 'widget.widget.data-source';

	public const ROUTE_NAME_WIDGET_DATA_SOURCE_RELATIONSHIP = 'widget.widget.data-source.relationship';

	/**
	 * MODULE MESSAGE BUS
	 */

	public const ROUTING_PREFIX = Metadata\Constants::MESSAGE_BUS_PREFIX_KEY . '.module.document';

	// WIDGETS
	public const MESSAGE_BUS_WIDGET_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.widget';

	public const MESSAGE_BUS_WIDGET_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.widget';

	public const MESSAGE_BUS_WIDGET_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.widget';

	public const MESSAGE_BUS_WIDGET_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.widget';

	// WIDGETS DATA SOURCES
	public const MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.widget.dataSource';

	public const MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.widget.dataSource';

	public const MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.widget.dataSource';

	public const MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.widget.dataSource';

	// WIDGETS DISPLAYS
	public const MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.widget.display';

	public const MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.widget.display';

	public const MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.widget.display';

	public const MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.widget.display';

	// GROUPS
	public const MESSAGE_BUS_GROUP_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.group';

	public const MESSAGE_BUS_GROUP_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.group';

	public const MESSAGE_BUS_GROUP_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.group';

	public const MESSAGE_BUS_GROUP_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.group';

	// DASHBOARDS
	public const MESSAGE_BUS_DASHBOARD_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.dashboard';

	public const MESSAGE_BUS_DASHBOARD_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.dashboard';

	public const MESSAGE_BUS_DASHBOARD_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.dashboard';

	public const MESSAGE_BUS_DASHBOARD_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.dashboard';

	// DASHBOARDS TABS
	public const MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_REPORTED_ROUTING_KEY = self::ROUTING_PREFIX . '.reported.dashboard.tab';

	public const MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_CREATED_ROUTING_KEY = self::ROUTING_PREFIX . '.created.dashboard.tab';

	public const MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_UPDATED_ROUTING_KEY = self::ROUTING_PREFIX . '.updated.dashboard.tab';

	public const MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_DELETED_ROUTING_KEY = self::ROUTING_PREFIX . '.deleted.dashboard.tab';

	// ACTIONS
	public const MESSAGE_BUS_WIDGET_DATA_SOURCE_ACTION_ROUTING_KEY = Metadata\Constants::MESSAGE_BUS_PREFIX_KEY . '.action.widget.dataSource';

	public const MESSAGE_BUS_CREATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Dashboards\Dashboard::class => self::MESSAGE_BUS_DASHBOARD_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Dashboards\Tabs\Tab::class => self::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Groups\Group::class => self::MESSAGE_BUS_GROUP_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Widgets\Widget::class => self::MESSAGE_BUS_WIDGET_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Widgets\DataSources\DataSource::class => self::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_CREATED_ROUTING_KEY,
			Entities\Widgets\Displays\Display::class => self::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_CREATED_ROUTING_KEY,
		];

	public const MESSAGE_BUS_UPDATED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Dashboards\Dashboard::class => self::MESSAGE_BUS_DASHBOARD_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Dashboards\Tabs\Tab::class => self::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Groups\Group::class => self::MESSAGE_BUS_GROUP_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Widgets\Widget::class => self::MESSAGE_BUS_WIDGET_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Widgets\DataSources\DataSource::class => self::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_UPDATED_ROUTING_KEY,
			Entities\Widgets\Displays\Display::class => self::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_UPDATED_ROUTING_KEY,
		];

	public const MESSAGE_BUS_DELETED_ENTITIES_ROUTING_KEYS_MAPPING
		= [
			Entities\Dashboards\Dashboard::class => self::MESSAGE_BUS_DASHBOARD_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Dashboards\Tabs\Tab::class => self::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Groups\Group::class => self::MESSAGE_BUS_GROUP_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Widgets\Widget::class => self::MESSAGE_BUS_WIDGET_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Widgets\DataSources\DataSource::class => self::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_DELETED_ROUTING_KEY,
			Entities\Widgets\Displays\Display::class => self::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_DELETED_ROUTING_KEY,
		];

	public const MESSAGE_BUS_ROUTING_KEYS
		= [
			self::MESSAGE_BUS_WIDGET_DOCUMENT_REPORTED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DOCUMENT_CREATED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DOCUMENT_UPDATED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DOCUMENT_DELETED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_CREATED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_UPDATED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_DELETED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_REPORTED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_CREATED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_UPDATED_ROUTING_KEY,
			self::MESSAGE_BUS_WIDGET_DISPLAY_DOCUMENT_DELETED_ROUTING_KEY,
			self::MESSAGE_BUS_GROUP_DOCUMENT_REPORTED_ROUTING_KEY,
			self::MESSAGE_BUS_GROUP_DOCUMENT_CREATED_ROUTING_KEY,
			self::MESSAGE_BUS_GROUP_DOCUMENT_UPDATED_ROUTING_KEY,
			self::MESSAGE_BUS_GROUP_DOCUMENT_DELETED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_DOCUMENT_REPORTED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_DOCUMENT_CREATED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_DOCUMENT_UPDATED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_DOCUMENT_DELETED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_REPORTED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_CREATED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_UPDATED_ROUTING_KEY,
			self::MESSAGE_BUS_DASHBOARD_TAB_DOCUMENT_DELETED_ROUTING_KEY,
		];

}
