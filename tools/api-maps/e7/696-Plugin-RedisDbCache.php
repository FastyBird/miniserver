<?php declare(strict_types = 1);

/**
 * E7.2 (#696), Plugin/RedisDbCache. The change list format is documented in tools/api-surface.php.
 *
 * public constants gain a type (6), final classes, readonly classes and Nette\SmartObject removed from 4 classes.
 * Clients\Client stays open (mocked by its own class name).
 */

return [
	'removed' => [
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Record::__call()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Record::__callStatic()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Record::__get()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Record::__isset()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Record::__set()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Record::__unset()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage::__call()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage::__callStatic()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage::__get()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage::__isset()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage::__set()',
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage::__unset()',
		'FastyBird\\Plugin\\RedisDbCache\\Clients\\Client::__call()',
		'FastyBird\\Plugin\\RedisDbCache\\Clients\\Client::__callStatic()',
		'FastyBird\\Plugin\\RedisDbCache\\Clients\\Client::__get()',
		'FastyBird\\Plugin\\RedisDbCache\\Clients\\Client::__isset()',
		'FastyBird\\Plugin\\RedisDbCache\\Clients\\Client::__set()',
		'FastyBird\\Plugin\\RedisDbCache\\Clients\\Client::__unset()',
		'FastyBird\\Plugin\\RedisDbCache\\Connections\\Configuration::__call()',
		'FastyBird\\Plugin\\RedisDbCache\\Connections\\Configuration::__callStatic()',
		'FastyBird\\Plugin\\RedisDbCache\\Connections\\Configuration::__get()',
		'FastyBird\\Plugin\\RedisDbCache\\Connections\\Configuration::__isset()',
		'FastyBird\\Plugin\\RedisDbCache\\Connections\\Configuration::__set()',
		'FastyBird\\Plugin\\RedisDbCache\\Connections\\Configuration::__unset()',
	],
	'changed' => [
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Journal::KEY_PRIORITY' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Journal::NS_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Journal::SUFFIX_KEYS' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Journal::SUFFIX_TAGS' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Journal' => [
			'final' => true,
			'readonly' => true,
		],
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Record' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage::NS_PREFIX' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDbCache\\Caching\\Storage' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDbCache\\Clients\\Client' => [
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDbCache\\Connections\\Configuration' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Plugin\\RedisDbCache\\DI\\RedisDbCacheExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Plugin\\RedisDbCache\\DI\\RedisDbCacheExtension' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDbCache\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Plugin\\RedisDbCache\\Exceptions\\InvalidState' => [
			'final' => true,
		],
	],
];
