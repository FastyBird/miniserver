<?php declare(strict_types = 1);

/**
 * E5.5 (#637): Core's WebSockets interfaces, collapsed or role-named exactly as census T1.f
 * approves (docs/superpowers/plans/2026-10-04-core-e5-census.md; IProtocol under R1, escalation
 * X3), the WAMP link generator moved into WebSockets (#460 §1.9), and the iPub strings replaced
 * with census T9's values (#460 §1.10, §3.11). The move maps are
 * tools/core-moves/21-interfaces-websockets.php and tools/core-moves/22-websockets-link-generator.php;
 * the change list format is documented in tools/api-surface.php.
 *
 * - 'renamed': the 12 collapses (each onto the existing implementation, which also drops it from
 *   every `interfaces` list and gives `Handshake\WampResponse` the status constants of
 *   `Handshake\IResponse`), the two storage drivers kept under their role name `Driver`, and the
 *   link generator's move. No class becomes final: every collapse target already is, except
 *   RFC6455, which `HyBi10` extends (R1).
 * - 'changed': the `@throws` the collapsed interfaces used to hide, now declared on the public
 *   and protected Core methods that call through them (PHPStan's missingCheckedExceptionInThrows;
 *   the private ones are not API), and census T9 row 1, the agent string sent as `X-Powered-By`
 *   and in the WAMP welcome. T9 rows 2-4 (the `'IPubWebSockets'` controller mapping, deleted, and
 *   the `Core:` / `Core:WebSocket` closure-route strings) live in private state and method bodies,
 *   which the manifest does not record; tests pin them.
 *
 * Declared DI changes (tools/di-snapshot.php, all 47 containers, base main @ e3e5cafb3): the
 * `$wiring` entries of the interfaces that had one disappear or are renamed with their type --
 * see the PR description for the per-type container counts. No service name, tag, setup,
 * argument or order changes, and every type keeps exactly the candidates it had: the two
 * `Driver`s keep the config-selected driver services, `Controllers\ControllerFactory` keeps
 * fbCore.webSockets.controllers.factory as its one autowired service (now also its declared
 * type), and `WebSockets\Routing\LinkGenerator` keeps fbCore.webSockets.routing.generator.
 */

return [
	'renamed' => [
		// T1.f, kept and role-named (K3, config-selected)
		'FastyBird\\Core\\WebSockets\\Clients\\Drivers\\IDriver' => 'FastyBird\\Core\\WebSockets\\Clients\\Drivers\\Driver',
		'FastyBird\\Core\\WebSockets\\Topics\\Drivers\\IDriver' => 'FastyBird\\Core\\WebSockets\\Topics\\Drivers\\Driver',

		// T1.f, collapsed (12); IProtocol under R1, RFC6455 stays non-final
		'FastyBird\\Core\\WebSockets\\Clients\\IStorage' => 'FastyBird\\Core\\WebSockets\\Clients\\Storage',
		'FastyBird\\Core\\WebSockets\\Controllers\\IControllerFactory' => 'FastyBird\\Core\\WebSockets\\Controllers\\ControllerFactory',
		'FastyBird\\Core\\WebSockets\\Encoding\\IFrame' => 'FastyBird\\Core\\WebSockets\\Encoding\\RFC6455\\Frame',
		'FastyBird\\Core\\WebSockets\\Encoding\\IMessage' => 'FastyBird\\Core\\WebSockets\\Encoding\\RFC6455\\Message',
		'FastyBird\\Core\\WebSockets\\Encoding\\IProtocol' => 'FastyBird\\Core\\WebSockets\\Encoding\\RFC6455',
		'FastyBird\\Core\\WebSockets\\Encoding\\IValidator' => 'FastyBird\\Core\\WebSockets\\Encoding\\Validator',
		'FastyBird\\Core\\WebSockets\\Entities\\IWampClient' => 'FastyBird\\Core\\WebSockets\\Entities\\WampClient',
		'FastyBird\\Core\\WebSockets\\Entities\\IWebSocket' => 'FastyBird\\Core\\WebSockets\\Entities\\WebSocket',
		'FastyBird\\Core\\WebSockets\\Entities\\Topics\\ITopic' => 'FastyBird\\Core\\WebSockets\\Entities\\Topics\\Topic',
		'FastyBird\\Core\\WebSockets\\Handshake\\IRequest' => 'FastyBird\\Core\\WebSockets\\Handshake\\Request',
		'FastyBird\\Core\\WebSockets\\Handshake\\IResponse' => 'FastyBird\\Core\\WebSockets\\Handshake\\WampResponse',
		'FastyBird\\Core\\WebSockets\\Topics\\IStorage' => 'FastyBird\\Core\\WebSockets\\Topics\\Storage',

		// #460 §1.9: the WAMP link generator, out of the HTTP router's namespace
		'FastyBird\\Core\\Http\\Routing\\LinkGenerator' => 'FastyBird\\Core\\WebSockets\\Routing\\LinkGenerator',
	],
	'changed' => [
		// the @throws the collapsed interfaces hid, declared on their public and protected callers
		'FastyBird\\Core\\WebSockets\\Controllers\\Application::processMessage()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidController',
				'FastyBird\\Core\\WebSockets\\Exceptions\\BadRequest',
				'ReflectionException',
			],
		],
		'FastyBird\\Core\\WebSockets\\Controllers\\WampApplication::handleClose()' => [
			'throws' => [
				'FastyBird\\Core\\WebSockets\\Exceptions\\Storage',
			],
		],
		'FastyBird\\Core\\WebSockets\\Server\\Wrapper::handleClose()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\WebSockets\\Exceptions\\Storage',
				'TypeError',
			],
		],

		// census T9 row 1 (P5): the agent string on the wire
		'FastyBird\\Core\\WebSockets\\Server\\ServerRuntime::VERSION' => [
			'value' => "'FastyBird/WebSockets/1.0.0'",
		],
	],
];
