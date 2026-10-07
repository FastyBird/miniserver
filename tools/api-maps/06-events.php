<?php declare(strict_types = 1);

/**
 * E5.6 (#638): the WebSockets hooks are dispatched as PSR-14 events, under census T4's names
 * (docs/superpowers/plans/2026-10-04-core-e5-census.md, #460 §3.4), and the module listeners of
 * ServerCreated register through a Core tag (escalation #658). The move map is
 * tools/core-moves/23-websockets-events.php; the change list format is documented in
 * tools/api-surface.php.
 *
 * - 'renamed': the 13 one-to-one T4 renames of map 23. The two duplicate pairs merge into the
 *   class that carries the whole payload: `IncommingMessageEvent` becomes `MessageReceived`
 *   (client, request and message) and `IncomingMessage` (client and request only) goes;
 *   `ClientConnectEvent` goes into the existing `ClientConnected` (identical payload).
 * - 'removed': the 12 public callback arrays (`ServerRuntime` 3, `Wrapper` 5, `Application` 4)
 *   and the two merged-away classes.
 * - 'added': `WebSocketsExtension::SERVER_CREATED_LISTENER_TAG`, the tag a package's
 *   ServerCreated listener is registered with, its value being the listener's priority (#658).
 * - 'changed':
 *   - `ServerRuntime`, `Wrapper`, `Application` and `WampApplication` take the PSR-14 dispatcher
 *     in their constructor (before the optional logger, where there is one);
 *   - `ClientConnected` follows the other events (`Exchange\Events\*`): it extends Symfony's
 *     `Event`, which makes it stoppable, and so is no longer a `readonly class`;
 *   - `ApplicationFailed`'s exception is a promoted property, like every other payload;
 *   - `Subscribers\Client::incomingMessage()` takes the merged `MessageReceived`.
 *
 * Declared DI changes (tools/di-snapshot.php, all 47 containers, base main @ a08318aaa): see the
 * PR description. Removed: the 12 + 2 bridge setups of WebSocketsExtension and the 3 module
 * `onCreate[]` enablers. Added: the 3 module listener services, tagged
 * fastybird.core.webSockets.serverCreatedListener at -10/-20/-30, one `addListener()` setup each on
 * the autowired Symfony dispatcher, and the dispatcher argument of the 4 runtime services.
 */

$event = static fn (string $name): string => 'FastyBird\\Core\\WebSockets\\Events\\' . $name;

return [
	'renamed' => [
		// ServerRuntime: onCreate, onStart, onStop
		$event('CreateEvent') => $event('ServerCreated'),
		$event('StartEvent') => $event('ServerStarted'),
		$event('StopEvent') => $event('ServerStopped'),

		// Wrapper: onClientDisconnected, onClientError, onIncomingMessage, onAfterIncomingMessage
		$event('ClientDisconnectEvent') => $event('ClientDisconnected'),
		$event('ClientErrorEvent') => $event('ClientFailed'),
		$event('IncommingMessageEvent') => $event('MessageReceived'),
		$event('AfterIncommingMessageEvent') => $event('MessageProcessed'),

		// Application: onOpen, onClose, onMessage, onError
		$event('OpenEvent') => $event('ConnectionOpened'),
		$event('CloseEvent') => $event('ConnectionClosed'),
		$event('MessageEvent') => $event('ApplicationMessageReceived'),
		$event('ErrorEvent') => $event('ApplicationFailed'),

		// Commands\WsServer
		$event('WsServerStartup') => $event('ServerLaunched'),
		$event('WsServerError') => $event('ServerFailed'),
	],
	'removed' => [
		// T4: the duplicate of each merged pair
		$event('ClientConnectEvent'),
		$event('IncomingMessage'),

		// #460 §3.4: the callback arrays
		'FastyBird\\Core\\WebSockets\\Server\\ServerRuntime::$onCreate',
		'FastyBird\\Core\\WebSockets\\Server\\ServerRuntime::$onStart',
		'FastyBird\\Core\\WebSockets\\Server\\ServerRuntime::$onStop',
		'FastyBird\\Core\\WebSockets\\Server\\Wrapper::$onClientConnected',
		'FastyBird\\Core\\WebSockets\\Server\\Wrapper::$onClientDisconnected',
		'FastyBird\\Core\\WebSockets\\Server\\Wrapper::$onClientError',
		'FastyBird\\Core\\WebSockets\\Server\\Wrapper::$onIncomingMessage',
		'FastyBird\\Core\\WebSockets\\Server\\Wrapper::$onAfterIncomingMessage',
		'FastyBird\\Core\\WebSockets\\Controllers\\Application::$onOpen',
		'FastyBird\\Core\\WebSockets\\Controllers\\Application::$onClose',
		'FastyBird\\Core\\WebSockets\\Controllers\\Application::$onMessage',
		'FastyBird\\Core\\WebSockets\\Controllers\\Application::$onError',
	],
	'added' => [
		// #658: the tag a package's ServerCreated listener registers with
		'FastyBird\\Core\\WebSockets\\DI\\WebSocketsExtension::SERVER_CREATED_LISTENER_TAG' => [
			'visibility' => 'public',
			'final' => false,
			'type' => 'string',
			'value' => '\'fastybird.core.webSockets.serverCreatedListener\'',
		],
	],
	'changed' => [
		// #460 §3.4: the four runtime classes take the PSR-14 dispatcher and dispatch themselves
		'FastyBird\\Core\\WebSockets\\Server\\ServerRuntime::__construct()' => [
			// the whole list: the dispatcher goes before the optional logger
			'parameters' => [
				'$handlers' => [
					'position' => 0,
					'type' => 'FastyBird\\Core\\WebSockets\\Server\\Handlers',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$loop' => [
					'position' => 1,
					'type' => 'React\\EventLoop\\LoopInterface',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$configuration' => [
					'position' => 2,
					'type' => 'FastyBird\\Core\\WebSockets\\Server\\Configuration',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$dispatcher' => [
					'position' => 3,
					'type' => 'Psr\\EventDispatcher\\EventDispatcherInterface',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$logger' => [
					'position' => 4,
					'type' => '?Psr\\Log\\LoggerInterface',
					'optional' => true,
					'default' => 'null',
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => false,
					'attributes' => [],
				],
			],
		],
		'FastyBird\\Core\\WebSockets\\Server\\Wrapper::__construct()' => [
			'parameters.$dispatcher' => [
				'position' => 2,
				'type' => 'Psr\\EventDispatcher\\EventDispatcherInterface',
				'optional' => false,
				'default' => null,
				'defaultConstant' => null,
				'variadic' => false,
				'byRef' => false,
				'promoted' => true,
				'attributes' => [],
			],
		],
		'FastyBird\\Core\\WebSockets\\Controllers\\Application::__construct()' => [
			// the whole list: the dispatcher goes before the optional logger
			'parameters' => [
				'$router' => [
					'position' => 0,
					'type' => 'FastyBird\\Core\\WebSockets\\Wamp\\WampRouter',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$controllerFactory' => [
					'position' => 1,
					'type' => 'FastyBird\\Core\\WebSockets\\Controllers\\ControllerFactory',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$clientsStorage' => [
					'position' => 2,
					'type' => 'FastyBird\\Core\\WebSockets\\Clients\\Storage',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$dispatcher' => [
					'position' => 3,
					'type' => 'Psr\\EventDispatcher\\EventDispatcherInterface',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$logger' => [
					'position' => 4,
					'type' => '?Psr\\Log\\LoggerInterface',
					'optional' => true,
					'default' => 'null',
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => false,
					'attributes' => [],
				],
			],
		],
		'FastyBird\\Core\\WebSockets\\Controllers\\WampApplication::__construct()' => [
			// the whole list: the dispatcher goes before the optional logger
			'parameters' => [
				'$topicsStorage' => [
					'position' => 0,
					'type' => 'FastyBird\\Core\\WebSockets\\Topics\\Storage',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$router' => [
					'position' => 1,
					'type' => 'FastyBird\\Core\\WebSockets\\Wamp\\WampRouter',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => false,
					'attributes' => [],
				],
				'$controllerFactory' => [
					'position' => 2,
					'type' => 'FastyBird\\Core\\WebSockets\\Controllers\\ControllerFactory',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => false,
					'attributes' => [],
				],
				'$clientsStorage' => [
					'position' => 3,
					'type' => 'FastyBird\\Core\\WebSockets\\Clients\\Storage',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => false,
					'attributes' => [],
				],
				'$dispatcher' => [
					'position' => 4,
					'type' => 'Psr\\EventDispatcher\\EventDispatcherInterface',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => false,
					'attributes' => [],
				],
				'$logger' => [
					'position' => 5,
					'type' => '?Psr\\Log\\LoggerInterface',
					'optional' => true,
					'default' => 'null',
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => false,
					'attributes' => [],
				],
			],
		],

		// T4: the merge target becomes a Symfony event like the others
		'FastyBird\\Core\\WebSockets\\Events\\ClientConnected' => [
			'readonly' => false,
			'parent' => 'Symfony\\Contracts\\EventDispatcher\\Event',
			'interfaces' => ['Psr\\EventDispatcher\\StoppableEventInterface'],
		],

		// T4: every payload a typed, promoted constructor property
		'FastyBird\\Core\\WebSockets\\Events\\ApplicationFailed::__construct()' => [
			'parameters.$ex.promoted' => true,
		],

		// T4: the subscriber takes the merged event
		'FastyBird\\Core\\WebSockets\\Subscribers\\Client::incomingMessage()' => [
			'parameters.$event.type' => 'FastyBird\\Core\\WebSockets\\Events\\MessageReceived',
		],
	],
];
