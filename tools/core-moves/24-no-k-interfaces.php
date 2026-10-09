<?php declare(strict_types = 1);

/**
 * #678: collapse the Core interfaces that meet none of K1-K4 (#460 §3.1), the documented
 * exceptions of E5's close-out (#645).
 *
 * Written by hand from the "Likely outcome" column of #678, as the maintainer decided there, and
 * from docs/conventions.md -> "Core's remaining interfaces" -> "No K reason found" (#677). Each
 * interface has one implementation, counted under rule R1 (E5 census X3), and is no substitution
 * point:
 *
 *   - `Persistence\Providers\DateProvider` into `Persistence\Utilities\DateTimeProvider`;
 *   - `Security\Access\CheckRequirements` into `Security\Access\AnnotationChecker`;
 *   - `WebSockets\Controllers\DispatchRequest` into `WebSockets\Controllers\Request`, whose plain
 *     public properties satisfied the interface's hooked ones (#644);
 *   - `WebSockets\Controllers\Dispatcher` into the abstract `WebSockets\Controllers\Application`
 *     (R1: `WampApplication` extends it and stays a separate class);
 *   - `WebSockets\Entities\ConnectedClient` into `WebSockets\Entities\Client` (R1: `WampClient`
 *     extends it; its redundant `implements ConnectedClient` was dropped by hand first);
 *   - `WebSockets\Clients\ClientProvider` into `WebSockets\Clients\WampClientFactory`. It was
 *     counted K1 only on paper: its other implementer, `ClientFactory`, was registered and always
 *     removed again before compilation, and was deleted by hand first.
 *
 * `Http\ResponseAttributes`, the sixth row, has no implementer, so it is not a collapse: its one
 * read constant moves onto `Http\ServerResponse` by hand. No class becomes `final` and none loses
 * it: `Client` and `Application` stay extendable, every other target already is final.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
	'collapse' => [
		'FastyBird\\Core\\Persistence\\Providers\\DateProvider' => 'FastyBird\\Core\\Persistence\\Utilities\\DateTimeProvider',
		'FastyBird\\Core\\Security\\Access\\CheckRequirements' => 'FastyBird\\Core\\Security\\Access\\AnnotationChecker',
		'FastyBird\\Core\\WebSockets\\Clients\\ClientProvider' => 'FastyBird\\Core\\WebSockets\\Clients\\WampClientFactory',
		'FastyBird\\Core\\WebSockets\\Controllers\\DispatchRequest' => 'FastyBird\\Core\\WebSockets\\Controllers\\Request',
		'FastyBird\\Core\\WebSockets\\Controllers\\Dispatcher' => 'FastyBird\\Core\\WebSockets\\Controllers\\Application',
		'FastyBird\\Core\\WebSockets\\Entities\\ConnectedClient' => 'FastyBird\\Core\\WebSockets\\Entities\\Client',
	],
];
