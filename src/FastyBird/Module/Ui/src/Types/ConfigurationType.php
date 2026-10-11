<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Types;

/**
 * Configuration cache types
 */
enum ConfigurationType: string
{

	case DASHBOARDS = 'dashboards';

	case DASHBOARDS_TABS = 'dashboards_tabs';

	case GROUPS = 'groups';

	case WIDGETS = 'widgets';

	case WIDGETS_DATA_SOURCES = 'widgets_data_sources';

	case WIDGETS_DISPLAY = 'widgets_display';

}
