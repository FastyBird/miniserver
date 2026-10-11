<?php declare(strict_types = 1);

/**
 * E7.2 (#696), Plugin/CouchDb. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type (3), final classes (the extension, 3 events, 2 exceptions, StatesManager and
 * StatesRepository), StatesRepository readonly, and Nette\SmartObject removed from 3 classes. States\State
 * stays open: tests/fixtures/CustomState extends it.
 */

return [
	'removed' => [
		'FastyBird\\Plugin\\CouchDb\\Connections\\Connection::__call()',
		'FastyBird\\Plugin\\CouchDb\\Connections\\Connection::__callStatic()',
		'FastyBird\\Plugin\\CouchDb\\Connections\\Connection::__get()',
		'FastyBird\\Plugin\\CouchDb\\Connections\\Connection::__isset()',
		'FastyBird\\Plugin\\CouchDb\\Connections\\Connection::__set()',
		'FastyBird\\Plugin\\CouchDb\\Connections\\Connection::__unset()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesManager::__call()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesManager::__callStatic()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesManager::__get()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesManager::__isset()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesManager::__set()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesManager::__unset()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesRepository::__call()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesRepository::__callStatic()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesRepository::__get()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesRepository::__isset()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesRepository::__set()',
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesRepository::__unset()',
	],
	'changed' => [
		'FastyBird\\Plugin\\CouchDb\\Connections\\Connection' => [
			'traits' => [],
		],
		'FastyBird\\Plugin\\CouchDb\\DI\\CouchDbExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\CouchDb\\DI\\CouchDbExtension' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\CouchDb\\Events\\StateCreated' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\CouchDb\\Events\\StateDeleted' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\CouchDb\\Events\\StateUpdated' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\CouchDb\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\CouchDb\\Exceptions\\NotUpdated' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesManager' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\CouchDb\\Models\\States\\StatesRepository' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\CouchDb\\States\\State::CREATED_AT_FIELD' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\CouchDb\\States\\State::UPDATED_AT_FIELD' => [
			'type' => 'string',
		],
	],
];
