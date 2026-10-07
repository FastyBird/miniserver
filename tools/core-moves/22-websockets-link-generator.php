<?php declare(strict_types = 1);

/**
 * E5.5 (#637): move the WAMP link generator out of the HTTP router's namespace and into
 * WebSockets (#460 §1.9, §4 "E5.5").
 *
 * `Http\Routing\LinkGenerator` is a WebSockets class: it generates WAMP links from
 * `Wamp\WampRouter` and `Controllers\ControllerFactory`, and `WebSockets\DI\WebSocketsExtension`
 * registers it, as `fbCore.webSockets.routing.generator` (#556). E3 left it where the Slim router
 * had it, and E4 handed the move to E5. Its service name does not change; the modules that
 * resolve it by type (`DevicesExtension`, `UiExtension`, `DevicesModuleUiModuleExtension` and the
 * three `SocketsBridge` consumers) are rewritten by the tool.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Http\\Routing\\LinkGenerator' => 'FastyBird\\Core\\WebSockets\\Routing\\LinkGenerator',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
