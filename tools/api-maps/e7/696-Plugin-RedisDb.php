<?php declare(strict_types = 1);

/**
 * E7.2 (#696), Plugin/RedisDb. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type (4), final classes, readonly classes and Nette\SmartObject removed from 14
 * classes. Clients\Client stays open (mocked by its own class name), States\State stays open (the
 * RedisDb bridges extend it) and Exceptions\InvalidState stays open (NotFound and NotUpdated extend it).
 */

return [
	'removed' => [
		'FastyBird\\Plugin\\RedisDb\\Clients\\Async\\Client::__call()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Async\\Client::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Async\\Client::__get()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Async\\Client::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Async\\Client::__set()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Async\\Client::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Client::__call()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Client::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Client::__get()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Client::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Client::__set()',
		'FastyBird\\Plugin\\RedisDb\\Clients\\Client::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Connections\\Configuration::__call()',
		'FastyBird\\Plugin\\RedisDb\\Connections\\Configuration::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Connections\\Configuration::__get()',
		'FastyBird\\Plugin\\RedisDb\\Connections\\Configuration::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Connections\\Configuration::__set()',
		'FastyBird\\Plugin\\RedisDb\\Connections\\Configuration::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManager::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManager::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManager::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManager::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManager::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManager::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManagerFactory::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManagerFactory::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManagerFactory::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManagerFactory::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManagerFactory::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManagerFactory::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepository::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepository::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepository::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepository::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepository::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepository::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepositoryFactory::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepositoryFactory::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepositoryFactory::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepositoryFactory::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepositoryFactory::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepositoryFactory::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManager::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManager::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManager::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManager::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManager::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManager::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManagerFactory::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManagerFactory::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManagerFactory::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManagerFactory::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManagerFactory::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManagerFactory::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepository::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepository::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepository::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepository::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepository::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepository::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepositoryFactory::__call()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepositoryFactory::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepositoryFactory::__get()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepositoryFactory::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepositoryFactory::__set()',
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepositoryFactory::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Async\\Publisher::__call()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Async\\Publisher::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Async\\Publisher::__get()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Async\\Publisher::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Async\\Publisher::__set()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Async\\Publisher::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Publisher::__call()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Publisher::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Publisher::__get()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Publisher::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Publisher::__set()',
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Publisher::__unset()',
		'FastyBird\\Plugin\\RedisDb\\Utilities\\IdentifierGenerator::__call()',
		'FastyBird\\Plugin\\RedisDb\\Utilities\\IdentifierGenerator::__callStatic()',
		'FastyBird\\Plugin\\RedisDb\\Utilities\\IdentifierGenerator::__get()',
		'FastyBird\\Plugin\\RedisDb\\Utilities\\IdentifierGenerator::__isset()',
		'FastyBird\\Plugin\\RedisDb\\Utilities\\IdentifierGenerator::__set()',
		'FastyBird\\Plugin\\RedisDb\\Utilities\\IdentifierGenerator::__unset()',
	],
	'changed' => [
		'FastyBird\\Plugin\\RedisDb\\Clients\\Async\\Client' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Clients\\Client' => [
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Connections\\Configuration' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\DI\\RedisDbExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDb\\DI\\RedisDbExtension' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Events\\AfterMessageHandled' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Events\\BeforeMessageHandled' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Events\\ConnectionClosed' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Events\\MessageConsumed' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Events\\MessageReceived' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Exceptions\\NotFound' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Exceptions\\NotUpdated' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManager' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesManagerFactory' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepository' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\Async\\StatesRepositoryFactory' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManager' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesManagerFactory' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepository' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Models\\States\\StatesRepositoryFactory' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Async\\Publisher' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\Publishers\\Publisher' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDb\\States\\State::CREATED_AT_FIELD' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDb\\States\\State::ID_FIELD' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDb\\States\\State::UPDATED_AT_FIELD' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDb\\Utilities\\IdentifierGenerator' => [
			'traits' => [],
		],
	],
];
