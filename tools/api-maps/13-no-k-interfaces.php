<?php declare(strict_types = 1);

/**
 * #678: the Core interfaces that met none of K1-K4 (#460 §3.1), collapsed or deleted as the
 * maintainer decided on #678 ("Likely outcome" for every row). The move map is
 * tools/core-moves/24-no-k-interfaces.php; the change list format is documented in
 * tools/api-surface.php.
 *
 * - 'renamed': the six collapses, each onto its existing implementation, which drops the
 *   interface from every `interfaces` list. `Application` (WampApplication extends it) and
 *   `Client` (WampClient extends it) stay non-final under rule R1; the other four targets already
 *   are final. `ClientProvider` qualifies because its second implementer, `ClientFactory`, was
 *   dead (below).
 * - 'removed': `Http\ResponseAttributes`, which had no implementer, with its never-read
 *   `ATTR_TOTAL_COUNT`; and `WebSockets\Clients\ClientFactory`, which `WebSocketsExtension`
 *   registered and always removed again before compilation, so no container ever held it.
 * - 'added': `ResponseAttributes::ATTR_ENTITY` moves onto its one reader, `Http\ServerResponse`,
 *   with the same value; and `Application::getSubProtocols()`, which `Application` inherited
 *   abstract from `Dispatcher` and now declares abstract itself, so `WampApplication`'s
 *   `#[Override]` and every caller typed against `Application` keep resolving.
 * - 'changed': the one `@throws` the collapse uncovered (tools/move-core-symbols.php warned of
 *   it): `AnnotationChecker::isAllowed()` declares `Exceptions\InvalidArgument`, which
 *   `CheckRequirements::isAllowed()` did not, so its caller `LinkChecker::isAllowed()` now declares
 *   it (PHPStan's missingCheckedExceptionInThrows). The tool's other warning,
 *   `Application::handleError()`'s `InvalidArgument`, asks nothing of any caller.
 *
 * Declared DI changes (tools/di-snapshot.php, all 47 containers, base main @ 3b9dcb7c4; 46
 * compile, test/Plugin/CouchDb fails on both sides): the `$wiring` entries of the collapsed
 * interfaces that had one disappear -- `Persistence\Providers\DateProvider`,
 * `WebSockets\Clients\ClientProvider` and `WebSockets\Controllers\Dispatcher` in 46 containers
 * each, `Security\Access\CheckRequirements` in the 41 that register the security services --
 * and each was equal to its implementation's own entry, so every type keeps exactly the
 * candidates it had. `DispatchRequest` and `ConnectedClient` had no entry. No service
 * name, type, factory, argument, setup, tag, alias or order changes; `ClientFactory`'s
 * registration never survived to a compiled container, so deleting it changes none.
 */

return [
	'renamed' => [
		'FastyBird\\Core\\Persistence\\Providers\\DateProvider' => 'FastyBird\\Core\\Persistence\\Utilities\\DateTimeProvider',
		'FastyBird\\Core\\Security\\Access\\CheckRequirements' => 'FastyBird\\Core\\Security\\Access\\AnnotationChecker',
		'FastyBird\\Core\\WebSockets\\Clients\\ClientProvider' => 'FastyBird\\Core\\WebSockets\\Clients\\WampClientFactory',
		'FastyBird\\Core\\WebSockets\\Controllers\\DispatchRequest' => 'FastyBird\\Core\\WebSockets\\Controllers\\Request',
		'FastyBird\\Core\\WebSockets\\Controllers\\Dispatcher' => 'FastyBird\\Core\\WebSockets\\Controllers\\Application',
		'FastyBird\\Core\\WebSockets\\Entities\\ConnectedClient' => 'FastyBird\\Core\\WebSockets\\Entities\\Client',
	],
	'removed' => [
		'FastyBird\\Core\\Http\\ResponseAttributes',
		'FastyBird\\Core\\WebSockets\\Clients\\ClientFactory',
	],
	'added' => [
		'FastyBird\\Core\\Http\\ServerResponse::ATTR_ENTITY' => [
			'visibility' => 'public',
			'final' => false,
			'type' => 'string',
			'value' => '\'__entity__\'',
		],
		'FastyBird\\Core\\WebSockets\\Controllers\\Application::getSubProtocols()' => [
			'visibility' => 'public',
			'static' => false,
			'abstract' => true,
			'final' => false,
			'byRef' => false,
			'return' => 'array',
			'parameters' => [],
			'attributes' => [],
			'throws' => [],
		],
	],
	'changed' => [
		'FastyBird\\Core\\Security\\Access\\LinkChecker::isAllowed()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'Nette\\Application\\InvalidPresenterException',
				'ReflectionException',
			],
		],
	],
];
