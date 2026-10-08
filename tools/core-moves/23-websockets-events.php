<?php declare(strict_types = 1);

/**
 * E5.6 (#638): give the WebSockets event classes the names census T4 approves (#460 §3.4).
 *
 * Written by hand from table T4 of docs/superpowers/plans/2026-10-04-core-e5-census.md and its
 * checkpoint on #460: past tense, no `Event` suffix, one class per hook. These are the 13
 * one-to-one renames. The two duplicate pairs are merged by hand afterwards, because each merge
 * targets a class that already exists or that this map creates:
 *
 *   - `ClientConnectEvent` into the existing `ClientConnected` (identical payload);
 *   - `IncomingMessage` into `MessageReceived`, which `IncommingMessageEvent` becomes here (its
 *     payload, client, request and message, is a superset of `IncomingMessage`'s).
 *
 * `PushEvent`, the 14th, went with the dead server-push pipeline in #635 (census X1-A).
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		// ServerRuntime: onCreate, onStart, onStop
		'FastyBird\\Core\\WebSockets\\Events\\CreateEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ServerCreated',
		'FastyBird\\Core\\WebSockets\\Events\\StartEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ServerStarted',
		'FastyBird\\Core\\WebSockets\\Events\\StopEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ServerStopped',

		// Wrapper: onClientDisconnected, onClientError, onIncomingMessage, onAfterIncomingMessage
		'FastyBird\\Core\\WebSockets\\Events\\ClientDisconnectEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ClientDisconnected',
		'FastyBird\\Core\\WebSockets\\Events\\ClientErrorEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ClientFailed',
		'FastyBird\\Core\\WebSockets\\Events\\IncommingMessageEvent' => 'FastyBird\\Core\\WebSockets\\Events\\MessageReceived',
		'FastyBird\\Core\\WebSockets\\Events\\AfterIncommingMessageEvent' => 'FastyBird\\Core\\WebSockets\\Events\\MessageProcessed',

		// Application: onOpen, onClose, onMessage, onError
		'FastyBird\\Core\\WebSockets\\Events\\OpenEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ConnectionOpened',
		'FastyBird\\Core\\WebSockets\\Events\\CloseEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ConnectionClosed',
		'FastyBird\\Core\\WebSockets\\Events\\MessageEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ApplicationMessageReceived',
		'FastyBird\\Core\\WebSockets\\Events\\ErrorEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ApplicationFailed',

		// Commands\WsServer: before the socket is created, and the socket's error handler
		'FastyBird\\Core\\WebSockets\\Events\\WsServerStartup' => 'FastyBird\\Core\\WebSockets\\Events\\ServerLaunched',
		'FastyBird\\Core\\WebSockets\\Events\\WsServerError' => 'FastyBird\\Core\\WebSockets\\Events\\ServerFailed',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
