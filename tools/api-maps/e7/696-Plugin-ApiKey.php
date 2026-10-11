<?php declare(strict_types = 1);

/**
 * E7.2 (#696), Plugin/ApiKey. The change list format is documented in tools/api-surface.php.
 *
 *   - Entities\Entity collapses into its only implementer Entities\Key (census T2, D6), and the
 *     trait Entities\TEntity becomes Entities\HasEntityId (census T1). Key's ORM attributes are
 *     unchanged.
 *   - 2 constants gain a type, Commands\Create and the extension become final, and
 *     Middleware\Validator becomes a readonly class.
 *   - Nette\SmartObject leaves 3 classes, and with it the six magic methods the trait declares.
 */

return [
	'renamed' => [
		'FastyBird\\Plugin\\ApiKey\\Entities\\Entity' => 'FastyBird\\Plugin\\ApiKey\\Entities\\Key',
		'FastyBird\\Plugin\\ApiKey\\Entities\\TEntity' => 'FastyBird\\Plugin\\ApiKey\\Entities\\HasEntityId',
	],
	'removed' => [
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create::__call()',
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create::__callStatic()',
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create::__get()',
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create::__isset()',
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create::__set()',
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create::__unset()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeyRepository::__call()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeyRepository::__callStatic()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeyRepository::__get()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeyRepository::__isset()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeyRepository::__set()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeyRepository::__unset()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeysManager::__call()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeysManager::__callStatic()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeysManager::__get()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeysManager::__isset()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeysManager::__set()',
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeysManager::__unset()',
	],
	'changed' => [
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\ApiKey\\Commands\\Create' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\ApiKey\\DI\\ApiKeyExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\ApiKey\\DI\\ApiKeyExtension' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\ApiKey\\Middleware\\Validator' => [
			'readonly' => true,
		],
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeyRepository' => [
			'traits' => [],
		],
		'FastyBird\\Plugin\\ApiKey\\Models\\Entities\\KeysManager' => [
			'traits' => [],
		],
	],
];
