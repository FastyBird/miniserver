<?php declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;
use Rector\ValueObject\PhpVersion;

/**
 * E5.10 (#642): dissolve FastyBird\Core\Constants into the types that own each constant. Copied
 * from tools/rector/skeleton.php, which documents the paths, the skips and why names are not
 * imported.
 *
 * One rule, nothing else (#460 §3.8): RenameClassConstFetchRector rewrites every fetch of the 30
 * constants census T5 gives a destination under rules C2, C3 and C4, to that destination. The
 * rename is type-aware: a fetch is rewritten only when its class resolves to
 * FastyBird\Core\Constants, so the extensions' own `Constants` classes -- imported under the same
 * short name in their own packages -- are left alone. The destinations are written fully
 * qualified; the hand-fix commit after this one turns them into the namespace imports the
 * codebase uses.
 *
 * Not in this list:
 *   - the 33 C1 constants (NOT_SPECIFIED_SOURCE, the *_SOURCE identities, PLUGIN_API_KEY): their
 *     only use was a case value of a Values\Types\Sources enum, and the commit before this one
 *     made each of them the literal of that case;
 *   - C5's TOKEN_URI_NAME, which #635 already deleted.
 *
 * The destination constants themselves are declared by hand, with their values unchanged, in a
 * later commit; this rule only rewrites the fetches.
 *
 *   make rector-e5 RECTOR_CONFIG=tools/rector/e5-constants.php
 */

$constants = 'FastyBird\\Core\\Constants';

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/../../src/FastyBird',
		__DIR__ . '/../../tests',
		__DIR__ . '/../../bin',
		__DIR__ . '/../../public',
		__DIR__ . '/../../migrations',
	])
	->withSkip([
		'*/node_modules/*',
		'*/assets/*',
		__DIR__ . '/../../tests/stubs',
	])
	->withCache(__DIR__ . '/../../var/tools/Rector')
	->withPhpVersion(PhpVersion::PHP_84)
	->withConfiguredRule(RenameClassConstFetchRector::class, [
		// C2: used by exactly one extension, plus tests -- that extension's own class, same name
		new RenameClassAndConstFetch($constants, 'MODULE_ACCOUNTS_PREFIX', 'FastyBird\\Module\\Accounts\\Constants', 'MODULE_ACCOUNTS_PREFIX'),
		new RenameClassAndConstFetch($constants, 'MODULE_DEVICES_PREFIX', 'FastyBird\\Module\\Devices\\Constants', 'MODULE_DEVICES_PREFIX'),
		new RenameClassAndConstFetch($constants, 'MODULE_TRIGGERS_PREFIX', 'FastyBird\\Module\\Triggers\\Constants', 'MODULE_TRIGGERS_PREFIX'),
		new RenameClassAndConstFetch($constants, 'MODULE_UI_PREFIX', 'FastyBird\\Module\\Ui\\Constants', 'MODULE_UI_PREFIX'),
		new RenameClassAndConstFetch($constants, 'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX', 'FastyBird\\Bridge\\ShellyConnectorHomeKitConnector\\Constants', 'BRIDGE_SHELLY_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX'),
		new RenameClassAndConstFetch($constants, 'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX', 'FastyBird\\Bridge\\VieraConnectorHomeKitConnector\\Constants', 'BRIDGE_VIERA_CONNECTOR_HOMEKIT_CONNECTOR_PREFIX'),
		new RenameClassAndConstFetch($constants, 'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX', 'FastyBird\\Bridge\\VirtualThermostatAddonHomeKitConnector\\Constants', 'BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX'),
		new RenameClassAndConstFetch($constants, 'EXCHANGE_CHANNEL_NAME', 'FastyBird\\Plugin\\RedisDb\\DI\\RedisDbExtension', 'EXCHANGE_CHANNEL_NAME'),
		new RenameClassAndConstFetch($constants, 'VALUE_FORMAT_NUMBER_RANGE', 'FastyBird\\Module\\Devices\\Constants', 'VALUE_FORMAT_NUMBER_RANGE'),
		new RenameClassAndConstFetch($constants, 'VALUE_FORMAT_STRING_ENUM', 'FastyBird\\Module\\Devices\\Constants', 'VALUE_FORMAT_STRING_ENUM'),
		new RenameClassAndConstFetch($constants, 'VALUE_FORMAT_COMBINED_ENUM', 'FastyBird\\Module\\Devices\\Constants', 'VALUE_FORMAT_COMBINED_ENUM'),
		// C3: belongs to one Core capability -- a typed constant on the owning type
		new RenameClassAndConstFetch($constants, 'TOKEN_HEADER_NAME', 'FastyBird\\Core\\Security\\Identity\\TokenReader', 'HEADER_NAME'),
		new RenameClassAndConstFetch($constants, 'TOKEN_HEADER_REGEXP', 'FastyBird\\Core\\Security\\Identity\\TokenReader', 'HEADER_PATTERN'),
		new RenameClassAndConstFetch($constants, 'TOKEN_CLAIM_USER', 'FastyBird\\Core\\Security\\Identity\\TokenBuilder', 'CLAIM_USER'),
		new RenameClassAndConstFetch($constants, 'TOKEN_CLAIM_ROLES', 'FastyBird\\Core\\Security\\Identity\\TokenBuilder', 'CLAIM_ROLES'),
		new RenameClassAndConstFetch($constants, 'PERMISSIONS_DELIMITER', 'FastyBird\\Core\\Security\\Access\\Checker', 'PERMISSIONS_DELIMITER'),
		new RenameClassAndConstFetch($constants, 'WS_HEADER_AUTHORIZATION', 'FastyBird\\Core\\WebSockets\\Subscribers\\Client', 'HEADER_AUTHORIZATION'),
		new RenameClassAndConstFetch($constants, 'WS_HEADER_WS_KEY', 'FastyBird\\Core\\WebSockets\\Subscribers\\Client', 'HEADER_WS_KEY'),
		new RenameClassAndConstFetch($constants, 'WS_HEADER_ORIGIN', 'FastyBird\\Core\\WebSockets\\Subscribers\\Client', 'HEADER_ORIGIN'),
		// C4: shared across capabilities or extensions -- the capability that defines its meaning
		new RenameClassAndConstFetch($constants, 'ROUTER_API_PREFIX', 'FastyBird\\Core\\Http\\Routing\\Router', 'API_PREFIX'),
		new RenameClassAndConstFetch($constants, 'VALUE_NOT_SET', 'FastyBird\\Core\\Values\\Utilities\\Value', 'NOT_SET'),
		new RenameClassAndConstFetch($constants, 'VALUE_EQUATION_TRANSFORMER', 'FastyBird\\Core\\Values\\Transformers\\EquationTransformer', 'PATTERN'),
		new RenameClassAndConstFetch($constants, 'MESSAGE_BUS_PREFIX_KEY', 'FastyBird\\Core\\Exchange\\Publisher\\MessagePublisher', 'ROUTING_KEY_PREFIX'),
		new RenameClassAndConstFetch($constants, 'ACCESS_TOKEN_COOKIE', 'FastyBird\\Core\\Security\\Identity\\TokenReader', 'COOKIE_NAME'),
		new RenameClassAndConstFetch($constants, 'ROLE_ANONYMOUS', 'FastyBird\\Core\\Security\\Identity\\User', 'ROLE_ANONYMOUS'),
		new RenameClassAndConstFetch($constants, 'ROLE_VISITOR', 'FastyBird\\Core\\Security\\Identity\\User', 'ROLE_VISITOR'),
		new RenameClassAndConstFetch($constants, 'ROLE_USER', 'FastyBird\\Core\\Security\\Identity\\User', 'ROLE_USER'),
		new RenameClassAndConstFetch($constants, 'ROLE_MANAGER', 'FastyBird\\Core\\Security\\Identity\\User', 'ROLE_MANAGER'),
		new RenameClassAndConstFetch($constants, 'ROLE_ADMINISTRATOR', 'FastyBird\\Core\\Security\\Identity\\User', 'ROLE_ADMINISTRATOR'),
		new RenameClassAndConstFetch($constants, 'USER_ANONYMOUS', 'FastyBird\\Core\\Security\\Identity\\User', 'ANONYMOUS_ID'),
	]);
