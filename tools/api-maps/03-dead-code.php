<?php declare(strict_types = 1);

/**
 * E5.3 (#635): Core's dead types, hooks and constants, deleted exactly as census T2 approves
 * (docs/superpowers/plans/2026-10-04-core-e5-census.md, escalation defaults X1-A, X7, X9, X10).
 * The change list format is documented in tools/api-surface.php.
 *
 * Not in this list, because the manifest never recorded it: T2 row 12, the
 * WebSockets/Compat/User.php shim. It declares Nette\Security\User, outside FastyBird\Core\, and
 * the autoloader never loads it. #635 keeps it for now (escalated: PHPStan resolves the 4
 * Nette\Security\User signatures X10 keeps only through it).
 *
 * Removing IWampApplication also drops it from WampApplication's interface list (the tool does
 * that for every removed type), so that needs no 'changed' item.
 */

return [
	'removed' => [
		// T2 rows 1-2: the memory cache adapter and the storage only it used
		'FastyBird\\Core\\Caching\\MemoryAdapterStorage',
		'FastyBird\\Core\\Caching\\MemoryStorage',

		// T2 row 3 (X9): the Symfony console formatter, its interface and Console's setter
		'FastyBird\\Core\\WebSockets\\Helpers\\Formatter\\Symfony',
		'FastyBird\\Core\\WebSockets\\Helpers\\Formatter\\IFormatter',
		'FastyBird\\Core\\WebSockets\\Helpers\\Console::setFormatter()',

		// T2 rows 4-7
		'FastyBird\\Core\\WebSockets\\Exceptions\\WampNotImplemented',
		'FastyBird\\Core\\Http\\ScalarEntity',
		'FastyBird\\Core\\Http\\Routing\\Handlers\\RequestResponseArgsHandler',
		'FastyBird\\Core\\Values\\Transformers\\DataTypeTransformer',

		// T2 rows 8-9: the Latte access extension and its 3 nodes (0 templates exist)
		'FastyBird\\Core\\Security\\Latte\\AccessExtension',
		'FastyBird\\Core\\Security\\Latte\\Nodes\\AllowedHrefNode',
		'FastyBird\\Core\\Security\\Latte\\Nodes\\IfAllowedNode',
		'FastyBird\\Core\\Security\\Latte\\Nodes\\NElseAllowedNode',

		// T2 row 10
		'FastyBird\\Core\\Persistence\\Crud\\EntityCrudFactory',

		// T2 row 13 (X7): the DBAL type; its 6 NEON registrations go with it
		'FastyBird\\Core\\Persistence\\Types\\UTCDateTime',

		// T2 rows 14-15 (T1.c, T1.d)
		'FastyBird\\Core\\Persistence\\Entities\\IEntityRemoved',
		'FastyBird\\Core\\Persistence\\Entities\\TEntityRemoved',
		'FastyBird\\Core\\Phone\\Entities\\TPhone',

		// T2 rows 16-23 (X1-A): the whole dead server-push pipeline
		'FastyBird\\Core\\WebSockets\\PushMessages\\Consumer',
		'FastyBird\\Core\\WebSockets\\PushMessages\\Pusher',
		'FastyBird\\Core\\WebSockets\\PushMessages\\IConsumer',
		'FastyBird\\Core\\WebSockets\\PushMessages\\IPusher',
		'FastyBird\\Core\\WebSockets\\PushMessages\\ConsumersRegistry',
		'FastyBird\\Core\\WebSockets\\PushMessages\\IConsumersRegistry',
		'FastyBird\\Core\\WebSockets\\Subscribers\\OnServerStartHandler',
		'FastyBird\\Core\\WebSockets\\Encoding\\PushMessageSerializer',
		'FastyBird\\Core\\WebSockets\\Entities\\PushMessages\\IMessage',
		'FastyBird\\Core\\WebSockets\\Entities\\PushMessages\\Message',
		'FastyBird\\Core\\WebSockets\\Controllers\\IWampApplication',
		'FastyBird\\Core\\WebSockets\\Controllers\\WampApplication::handlePush()',
		'FastyBird\\Core\\WebSockets\\Controllers\\WampApplication::$onPush',
		'FastyBird\\Core\\WebSockets\\Events\\PushEvent',

		// T2 row 24 (P7): the 9 dead callback arrays (T3 rows 14-22)
		'FastyBird\\Core\\Security\\Identity\\User::$onLoggedIn',
		'FastyBird\\Core\\Security\\Identity\\User::$onLoggedOut',
		'FastyBird\\Core\\Persistence\\Crud\\Create\\EntityCreator::$beforeAction',
		'FastyBird\\Core\\Persistence\\Crud\\Create\\EntityCreator::$afterAction',
		'FastyBird\\Core\\Persistence\\Crud\\Update\\EntityUpdater::$beforeAction',
		'FastyBird\\Core\\Persistence\\Crud\\Update\\EntityUpdater::$afterAction',
		'FastyBird\\Core\\Persistence\\Crud\\Delete\\EntityDeleter::$beforeAction',
		'FastyBird\\Core\\Persistence\\Crud\\Delete\\EntityDeleter::$afterAction',
		'FastyBird\\Core\\Persistence\\Query\\QueryObject::$onPostFetch',

		// T2 row 26 (C5); ROLE_VISITOR is referenced and stays (D7)
		'FastyBird\\Core\\Constants::TOKEN_URI_NAME',
	],
	'changed' => [
		// T2 row 25 (D20): $context was the first injectPrimary() parameter, not a constructor one
		'FastyBird\\Core\\WebSockets\\Controllers\\Controller::injectPrimary()' => [
			'-parameters.$context' => null,
			'parameters.$controllerFactory.position' => 0,
			'parameters.$router.position' => 1,
			'parameters.$linkGenerator.position' => 2,
			'parameters.$user.position' => 3,
		],
	],
];
