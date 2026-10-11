<?php declare(strict_types = 1);

/**
 * #701 (E7.7, #462), Bridge/RedisDbPluginTriggersModule. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type, final classes and Nette\SmartObject removed from 4 classes.
 */

return [
	'removed' => [
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager::__call()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager::__get()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager::__set()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager::__unset()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository::__call()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository::__callStatic()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository::__get()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository::__isset()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository::__set()',
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository::__unset()',
	],
	'changed' => [
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\DI\\RedisDbPluginTriggersModuleExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\DI\\RedisDbPluginTriggersModuleExtension' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\States\\Action' => [
			'final' => true,
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\States\\Condition' => [
			'final' => true,
		],
	],
];
