<?php declare(strict_types = 1);

/**
 * E5.5 (#637): collapse Core's single-implementation WebSockets interfaces and role-name the two
 * that stay (#460 §3.1, §3.2).
 *
 * Written by hand from the approved census, table T1.f of
 * docs/superpowers/plans/2026-10-04-core-e5-census.md, and its checkpoint on #460:
 *
 *   - 'classes': the two storage drivers are KEPT. Each is a substitution point selected by
 *     configuration (K3) -- the keys `fbCore.webSockets.storage.clients.driver` and
 *     `fbCore.webSockets.storage.topics.driver`, defaulting to the services
 *     `fbCore.webSockets.clients.driver.memory` and `fbCore.webSockets.wamp.topics.driver.memory`
 *     -- with a test double each (`DummyClientsDriver`, `DummyTopicsDriver`). They lose the `I`
 *     and take the role name `Driver` in their own sub-namespace; neither namespace declares a
 *     concrete `Driver`.
 *   - 'collapse': the other 12 T1.f rows that still exist. Each has one production implementer
 *     and no K reason. `Encoding\IProtocol` collapses under rule R1 (census §0.3, X3): its other
 *     implementer, `HyBi10`, extends `RFC6455`, so `RFC6455` stays non-final. Every other target
 *     is already `final`: no class becomes `final` and none loses it.
 *
 * The other 6 T1.f rows were deleted by #635 (X1-A and T2): `IWampApplication`,
 * `Entities\PushMessages\IMessage`, `Helpers\Formatter\IFormatter`, `PushMessages\IConsumer`,
 * `IConsumersRegistry` and `IPusher`.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		// T1.f, kept and role-named (K3, config-selected)
		'FastyBird\\Core\\WebSockets\\Clients\\Drivers\\IDriver' => 'FastyBird\\Core\\WebSockets\\Clients\\Drivers\\Driver',
		'FastyBird\\Core\\WebSockets\\Topics\\Drivers\\IDriver' => 'FastyBird\\Core\\WebSockets\\Topics\\Drivers\\Driver',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
	'collapse' => [
		// T1.f WebSockets (12); IProtocol under R1, RFC6455 stays non-final
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
	],
];
