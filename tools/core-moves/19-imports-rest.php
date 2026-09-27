<?php declare(strict_types = 1);

/**
 * #541: normalize every remaining namespace still carrying a bare import that collides with a
 * same-kind sibling's short name, per `tools/naming-baseline.txt` at `907957f0c` -- 129 entries
 * across 27 distinct FQCNs, derived mechanically from the baseline: Addon\VirtualThermostat (6:
 * Documents, Entities, Helpers, Hydrators, Queries, Schemas), Automator\DevicesModule (1:
 * Entities), Bridge\DevicesModuleUiModule (3: Documents, Entities, Queries),
 * Bridge\ShellyConnectorHomeKitConnector (6: Documents, Entities, Queries, Router, Schemas,
 * Types), Bridge\VieraConnectorHomeKitConnector (4: Documents, Entities, Queries, Router),
 * Bridge\VirtualThermostatAddonHomeKitConnector (4: Documents, Entities, Queries, Router),
 * Module\Devices\Caching (1), Module\Ui\Caching (1), Plugin\RedisDbCache\Caching (1). This is
 * the last map of #541; the baseline is expected to reach 0 lines after it.
 *
 * `classes`, `namespaces` and `files` are empty -- none of these move, they are only
 * (re-)aliased wherever `make naming`'s check 4 requires it. Normalization is repository-wide
 * per namespace (#541's plan comment 3): a file outside the owning package that imports one of
 * these namespaces bare next to a colliding sibling is a candidate too.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [],
	'normalize' => [
		'FastyBird\\Addon\\VirtualThermostat\\Documents',
		'FastyBird\\Addon\\VirtualThermostat\\Entities',
		'FastyBird\\Addon\\VirtualThermostat\\Helpers',
		'FastyBird\\Addon\\VirtualThermostat\\Hydrators',
		'FastyBird\\Addon\\VirtualThermostat\\Queries',
		'FastyBird\\Addon\\VirtualThermostat\\Schemas',
		'FastyBird\\Automator\\DevicesModule\\Entities',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Documents',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Entities',
		'FastyBird\\Bridge\\DevicesModuleUiModule\\Queries',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Documents',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Entities',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Queries',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Router',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Schemas',
		'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Types',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Documents',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Entities',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Queries',
		'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Router',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Documents',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Entities',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Queries',
		'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Router',
		'FastyBird\\Module\\Devices\\Caching',
		'FastyBird\\Module\\Ui\\Caching',
		'FastyBird\\Plugin\\RedisDbCache\\Caching',
	],
	'namespaces' => [],
	'files' => [],
];
