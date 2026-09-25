<?php declare(strict_types = 1);

/**
 * E3.12 (#505): WebSockets.
 *
 * Written by hand from the approved census, section "WebSockets (103 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, plus the `Routing\`
 * three-way split section, the Events/Exceptions assignment, the name table (27 policy-B
 * renames, 9 of them in WebSockets) and the stutter table. 103 files in the census's table;
 * 102 of them are class/interface moves (this map's `classes` key); the 103rd,
 * `Compat/User.php`, is a file-only move (decision 2, below).
 *
 * - `Clients\WsServer\*` (7) -> `WebSockets\Clients\*` -- `IClientFactory` renamed to
 *   `ClientProvider` (collision table: would collide with the concrete `ClientFactory` on a
 *   bare I-drop).
 * - `Commands\WsServer` -> `WebSockets\Commands\WsServer`.
 * - `Controllers\WebSockets\*` (13, including the flattened `Controller\*` sub-namespace,
 *   stutter table) -> `WebSockets\Controllers\*`. `Controller\Controller` keeps its name
 *   (renaming to `AbstractController` fails `make cs`'s `SuperfluousAbstractClassNaming`
 *   sniff; flattening the directory instead removes the stutter -- stutter table). `IApplication`
 *   -> `Dispatcher`, `Controller\IController` -> `RequestController`, `IRequest` ->
 *   `DispatchRequest`, `Responses\IResponse` -> `ControllerResponse` (collision table: each
 *   would collide with a concrete sibling or with the other `IRequest`/`IResponse` pair in
 *   `Http\`, which stays behind as `WebSockets\Handshake\*`, see below).
 * - `Encoding\WebSockets\*` (11) -> `WebSockets\Encoding\*` -- `IData` -> `FrameData`
 *   (name table).
 * - `Entities\WebSockets\*` (4) and `Entities\WsServer\*` (6) -> `WebSockets\Entities\*` --
 *   `WsServer\IClient` -> `ConnectedClient` (collision table: would collide with the concrete
 *   `Client`/`WampClient` siblings on a bare I-drop).
 * - The 17 remaining `Events\*` (everything not claimed by Documents/Exchange/Persistence/
 *   Http/EventLoop/Presenters in the Events/Exceptions assignment) -> `WebSockets\Events\*`.
 * - The 10 remaining `Exceptions\*` (`Abort`, `BadRequest`, `BadResponse`, `BadSignal`,
 *   `ClientNotFound`, `ForbiddenRequest`, `Storage`, `Terminate`, `TopicNotFound`,
 *   `WampNotImplemented`) -> `WebSockets\Exceptions\*`. `InvalidController`, `InvalidLink` and
 *   `UnexpectedValue` stay in the shared root (decision 6) and are NOT in this map.
 * - `Helpers\WsServer\*` (3) -> `WebSockets\Helpers\*`.
 * - The 5 WS-handshake files under `Http\` (`IRequest`, `IResponse`, `Request`,
 *   `RequestFactory`, `WampResponse`) -> `WebSockets\Handshake\*` -- names unchanged (single
 *   implementer, policy-B hand-off); they collide short-name-wise with the dispatch
 *   `IRequest`/`IResponse` above, resolved by sub-namespace (collision table).
 * - `Messaging\WebSockets\PushMessages\*` (6) -> `WebSockets\PushMessages\*`.
 * - `Routing\{IWampRouter,RouteList,WampRoute}` (3), the WAMP router -> `WebSockets\Wamp\*`,
 *   NEVER `Http\` (the trap #458 and this issue both name). `IWampRouter` renamed to
 *   `Wamp\WampRouter` (name table: 2+ implementers inside Core). `Routing\AppRouter` is NOT in
 *   this map -- it stays in `Routing\` for E3.14 (root) to move.
 * - `Server\WsServer\*` (6) -> `WebSockets\Server\*` -- `IWrapper` -> `ServerWrapper`
 *   (collision table: would collide with the concrete `Wrapper`), `Server` -> `ServerRuntime`
 *   (stutter table: `Server\Server` would stutter).
 * - `Subscribers\WsServer\*` (2) -> `WebSockets\Subscribers\*`.
 * - `Topics\WsServer\*` (4) -> `WebSockets\Topics\*`.
 *
 * `Compat/User.php` (decision 2): declares `namespace Nette\Security;` and conditionally
 * defines `Nette\Security\User` only `if (!class_exists('\Nette\Security\User'))` -- dead code,
 * never loaded (not in any `composer.json` `autoload.files`/`classmap`, not `require`d
 * anywhere; PSR-4 from `FastyBird\Core\` can never resolve `Nette\Security\User` to a path
 * under `src/` regardless of where the file sits). Its FQCN is NOT a `FastyBird\Core\…` symbol
 * and must never enter the `classes` rewrite: two other files in Core genuinely reference the
 * real `Nette\Security\User` (`Clients/WsServer/Storage.php`, `Controllers/WebSockets/
 * Controller/Controller.php`), and rewriting on this file's presence would retarget those.
 * It moves via `files` only, keeping its FQCN untouched, so those genuine references are left
 * alone. Confirmed before writing this map: `git grep -n "Compat/User\|Compat\\\\User\|Nette\\\\
 * Security\\\\User"` outside this file finds only the 2 genuine references above (never
 * "Compat/User" or "Compat\User" itself) -- no composer.json `autoload`/`classmap`/`files`
 * entry, no PHPStan/PHPCS exclude, no other path reference needs updating.
 *
 * No `normalize` entries: unlike `Http`/`Documents`, `FastyBird\Core\WebSockets` does not exist
 * anywhere in the tree before this move, so there is nothing to re-alias.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Clients\\WsServer\\ClientFactory' => 'FastyBird\\Core\\WebSockets\\Clients\\ClientFactory',
		'FastyBird\\Core\\Clients\\WsServer\\Drivers\\IDriver' => 'FastyBird\\Core\\WebSockets\\Clients\\Drivers\\IDriver',
		'FastyBird\\Core\\Clients\\WsServer\\Drivers\\InMemory' => 'FastyBird\\Core\\WebSockets\\Clients\\Drivers\\InMemory',
		'FastyBird\\Core\\Clients\\WsServer\\IClientFactory' => 'FastyBird\\Core\\WebSockets\\Clients\\ClientProvider',
		'FastyBird\\Core\\Clients\\WsServer\\IStorage' => 'FastyBird\\Core\\WebSockets\\Clients\\IStorage',
		'FastyBird\\Core\\Clients\\WsServer\\Storage' => 'FastyBird\\Core\\WebSockets\\Clients\\Storage',
		'FastyBird\\Core\\Clients\\WsServer\\WampClientFactory' => 'FastyBird\\Core\\WebSockets\\Clients\\WampClientFactory',
		'FastyBird\\Core\\Commands\\WsServer' => 'FastyBird\\Core\\WebSockets\\Commands\\WsServer',
		'FastyBird\\Core\\Controllers\\WebSockets\\Application' => 'FastyBird\\Core\\WebSockets\\Controllers\\Application',
		'FastyBird\\Core\\Controllers\\WebSockets\\Controller\\Controller' => 'FastyBird\\Core\\WebSockets\\Controllers\\Controller',
		'FastyBird\\Core\\Controllers\\WebSockets\\Controller\\ControllerFactory' => 'FastyBird\\Core\\WebSockets\\Controllers\\ControllerFactory',
		'FastyBird\\Core\\Controllers\\WebSockets\\Controller\\IController' => 'FastyBird\\Core\\WebSockets\\Controllers\\RequestController',
		'FastyBird\\Core\\Controllers\\WebSockets\\Controller\\IControllerFactory' => 'FastyBird\\Core\\WebSockets\\Controllers\\IControllerFactory',
		'FastyBird\\Core\\Controllers\\WebSockets\\IApplication' => 'FastyBird\\Core\\WebSockets\\Controllers\\Dispatcher',
		'FastyBird\\Core\\Controllers\\WebSockets\\IRequest' => 'FastyBird\\Core\\WebSockets\\Controllers\\DispatchRequest',
		'FastyBird\\Core\\Controllers\\WebSockets\\IWampApplication' => 'FastyBird\\Core\\WebSockets\\Controllers\\IWampApplication',
		'FastyBird\\Core\\Controllers\\WebSockets\\Reflection' => 'FastyBird\\Core\\WebSockets\\Controllers\\Reflection',
		'FastyBird\\Core\\Controllers\\WebSockets\\Request' => 'FastyBird\\Core\\WebSockets\\Controllers\\Request',
		'FastyBird\\Core\\Controllers\\WebSockets\\Responses\\ErrorResponse' => 'FastyBird\\Core\\WebSockets\\Controllers\\Responses\\ErrorResponse',
		'FastyBird\\Core\\Controllers\\WebSockets\\Responses\\IResponse' => 'FastyBird\\Core\\WebSockets\\Controllers\\Responses\\ControllerResponse',
		'FastyBird\\Core\\Controllers\\WebSockets\\Responses\\MessageResponse' => 'FastyBird\\Core\\WebSockets\\Controllers\\Responses\\MessageResponse',
		'FastyBird\\Core\\Controllers\\WebSockets\\Responses\\NullResponse' => 'FastyBird\\Core\\WebSockets\\Controllers\\Responses\\NullResponse',
		'FastyBird\\Core\\Controllers\\WebSockets\\WampApplication' => 'FastyBird\\Core\\WebSockets\\Controllers\\WampApplication',
		'FastyBird\\Core\\Encoding\\WebSockets\\HyBi10' => 'FastyBird\\Core\\WebSockets\\Encoding\\HyBi10',
		'FastyBird\\Core\\Encoding\\WebSockets\\IData' => 'FastyBird\\Core\\WebSockets\\Encoding\\FrameData',
		'FastyBird\\Core\\Encoding\\WebSockets\\IFrame' => 'FastyBird\\Core\\WebSockets\\Encoding\\IFrame',
		'FastyBird\\Core\\Encoding\\WebSockets\\IMessage' => 'FastyBird\\Core\\WebSockets\\Encoding\\IMessage',
		'FastyBird\\Core\\Encoding\\WebSockets\\IProtocol' => 'FastyBird\\Core\\WebSockets\\Encoding\\IProtocol',
		'FastyBird\\Core\\Encoding\\WebSockets\\IValidator' => 'FastyBird\\Core\\WebSockets\\Encoding\\IValidator',
		'FastyBird\\Core\\Encoding\\WebSockets\\ProtocolProxy' => 'FastyBird\\Core\\WebSockets\\Encoding\\ProtocolProxy',
		'FastyBird\\Core\\Encoding\\WebSockets\\PushMessageSerializer' => 'FastyBird\\Core\\WebSockets\\Encoding\\PushMessageSerializer',
		'FastyBird\\Core\\Encoding\\WebSockets\\RFC6455' => 'FastyBird\\Core\\WebSockets\\Encoding\\RFC6455',
		'FastyBird\\Core\\Encoding\\WebSockets\\RFC6455\\Frame' => 'FastyBird\\Core\\WebSockets\\Encoding\\RFC6455\\Frame',
		'FastyBird\\Core\\Encoding\\WebSockets\\RFC6455\\HandshakeVerifier' => 'FastyBird\\Core\\WebSockets\\Encoding\\RFC6455\\HandshakeVerifier',
		'FastyBird\\Core\\Encoding\\WebSockets\\RFC6455\\Message' => 'FastyBird\\Core\\WebSockets\\Encoding\\RFC6455\\Message',
		'FastyBird\\Core\\Encoding\\WebSockets\\Validator' => 'FastyBird\\Core\\WebSockets\\Encoding\\Validator',
		'FastyBird\\Core\\Entities\\WebSockets\\IWebSocket' => 'FastyBird\\Core\\WebSockets\\Entities\\IWebSocket',
		'FastyBird\\Core\\Entities\\WebSockets\\PushMessages\\IMessage' => 'FastyBird\\Core\\WebSockets\\Entities\\PushMessages\\IMessage',
		'FastyBird\\Core\\Entities\\WebSockets\\PushMessages\\Message' => 'FastyBird\\Core\\WebSockets\\Entities\\PushMessages\\Message',
		'FastyBird\\Core\\Entities\\WebSockets\\WebSocket' => 'FastyBird\\Core\\WebSockets\\Entities\\WebSocket',
		'FastyBird\\Core\\Entities\\WsServer\\Client' => 'FastyBird\\Core\\WebSockets\\Entities\\Client',
		'FastyBird\\Core\\Entities\\WsServer\\IClient' => 'FastyBird\\Core\\WebSockets\\Entities\\ConnectedClient',
		'FastyBird\\Core\\Entities\\WsServer\\IWampClient' => 'FastyBird\\Core\\WebSockets\\Entities\\IWampClient',
		'FastyBird\\Core\\Entities\\WsServer\\Topics\\ITopic' => 'FastyBird\\Core\\WebSockets\\Entities\\Topics\\ITopic',
		'FastyBird\\Core\\Entities\\WsServer\\Topics\\Topic' => 'FastyBird\\Core\\WebSockets\\Entities\\Topics\\Topic',
		'FastyBird\\Core\\Entities\\WsServer\\WampClient' => 'FastyBird\\Core\\WebSockets\\Entities\\WampClient',
		'FastyBird\\Core\\Events\\AfterIncommingMessageEvent' => 'FastyBird\\Core\\WebSockets\\Events\\AfterIncommingMessageEvent',
		'FastyBird\\Core\\Events\\ClientConnectEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ClientConnectEvent',
		'FastyBird\\Core\\Events\\ClientConnected' => 'FastyBird\\Core\\WebSockets\\Events\\ClientConnected',
		'FastyBird\\Core\\Events\\ClientDisconnectEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ClientDisconnectEvent',
		'FastyBird\\Core\\Events\\ClientErrorEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ClientErrorEvent',
		'FastyBird\\Core\\Events\\CloseEvent' => 'FastyBird\\Core\\WebSockets\\Events\\CloseEvent',
		'FastyBird\\Core\\Events\\CreateEvent' => 'FastyBird\\Core\\WebSockets\\Events\\CreateEvent',
		'FastyBird\\Core\\Events\\ErrorEvent' => 'FastyBird\\Core\\WebSockets\\Events\\ErrorEvent',
		'FastyBird\\Core\\Events\\IncomingMessage' => 'FastyBird\\Core\\WebSockets\\Events\\IncomingMessage',
		'FastyBird\\Core\\Events\\IncommingMessageEvent' => 'FastyBird\\Core\\WebSockets\\Events\\IncommingMessageEvent',
		'FastyBird\\Core\\Events\\MessageEvent' => 'FastyBird\\Core\\WebSockets\\Events\\MessageEvent',
		'FastyBird\\Core\\Events\\OpenEvent' => 'FastyBird\\Core\\WebSockets\\Events\\OpenEvent',
		'FastyBird\\Core\\Events\\PushEvent' => 'FastyBird\\Core\\WebSockets\\Events\\PushEvent',
		'FastyBird\\Core\\Events\\StartEvent' => 'FastyBird\\Core\\WebSockets\\Events\\StartEvent',
		'FastyBird\\Core\\Events\\StopEvent' => 'FastyBird\\Core\\WebSockets\\Events\\StopEvent',
		'FastyBird\\Core\\Events\\WsServerError' => 'FastyBird\\Core\\WebSockets\\Events\\WsServerError',
		'FastyBird\\Core\\Events\\WsServerStartup' => 'FastyBird\\Core\\WebSockets\\Events\\WsServerStartup',
		'FastyBird\\Core\\Exceptions\\Abort' => 'FastyBird\\Core\\WebSockets\\Exceptions\\Abort',
		'FastyBird\\Core\\Exceptions\\BadRequest' => 'FastyBird\\Core\\WebSockets\\Exceptions\\BadRequest',
		'FastyBird\\Core\\Exceptions\\BadResponse' => 'FastyBird\\Core\\WebSockets\\Exceptions\\BadResponse',
		'FastyBird\\Core\\Exceptions\\BadSignal' => 'FastyBird\\Core\\WebSockets\\Exceptions\\BadSignal',
		'FastyBird\\Core\\Exceptions\\ClientNotFound' => 'FastyBird\\Core\\WebSockets\\Exceptions\\ClientNotFound',
		'FastyBird\\Core\\Exceptions\\ForbiddenRequest' => 'FastyBird\\Core\\WebSockets\\Exceptions\\ForbiddenRequest',
		'FastyBird\\Core\\Exceptions\\Storage' => 'FastyBird\\Core\\WebSockets\\Exceptions\\Storage',
		'FastyBird\\Core\\Exceptions\\Terminate' => 'FastyBird\\Core\\WebSockets\\Exceptions\\Terminate',
		'FastyBird\\Core\\Exceptions\\TopicNotFound' => 'FastyBird\\Core\\WebSockets\\Exceptions\\TopicNotFound',
		'FastyBird\\Core\\Exceptions\\WampNotImplemented' => 'FastyBird\\Core\\WebSockets\\Exceptions\\WampNotImplemented',
		'FastyBird\\Core\\Helpers\\WsServer\\Console' => 'FastyBird\\Core\\WebSockets\\Helpers\\Console',
		'FastyBird\\Core\\Helpers\\WsServer\\Formatter\\IFormatter' => 'FastyBird\\Core\\WebSockets\\Helpers\\Formatter\\IFormatter',
		'FastyBird\\Core\\Helpers\\WsServer\\Formatter\\Symfony' => 'FastyBird\\Core\\WebSockets\\Helpers\\Formatter\\Symfony',
		'FastyBird\\Core\\Http\\IRequest' => 'FastyBird\\Core\\WebSockets\\Handshake\\IRequest',
		'FastyBird\\Core\\Http\\IResponse' => 'FastyBird\\Core\\WebSockets\\Handshake\\IResponse',
		'FastyBird\\Core\\Http\\Request' => 'FastyBird\\Core\\WebSockets\\Handshake\\Request',
		'FastyBird\\Core\\Http\\RequestFactory' => 'FastyBird\\Core\\WebSockets\\Handshake\\RequestFactory',
		'FastyBird\\Core\\Http\\WampResponse' => 'FastyBird\\Core\\WebSockets\\Handshake\\WampResponse',
		'FastyBird\\Core\\Messaging\\WebSockets\\PushMessages\\Consumer' => 'FastyBird\\Core\\WebSockets\\PushMessages\\Consumer',
		'FastyBird\\Core\\Messaging\\WebSockets\\PushMessages\\ConsumersRegistry' => 'FastyBird\\Core\\WebSockets\\PushMessages\\ConsumersRegistry',
		'FastyBird\\Core\\Messaging\\WebSockets\\PushMessages\\IConsumer' => 'FastyBird\\Core\\WebSockets\\PushMessages\\IConsumer',
		'FastyBird\\Core\\Messaging\\WebSockets\\PushMessages\\IConsumersRegistry' => 'FastyBird\\Core\\WebSockets\\PushMessages\\IConsumersRegistry',
		'FastyBird\\Core\\Messaging\\WebSockets\\PushMessages\\IPusher' => 'FastyBird\\Core\\WebSockets\\PushMessages\\IPusher',
		'FastyBird\\Core\\Messaging\\WebSockets\\PushMessages\\Pusher' => 'FastyBird\\Core\\WebSockets\\PushMessages\\Pusher',
		'FastyBird\\Core\\Routing\\IWampRouter' => 'FastyBird\\Core\\WebSockets\\Wamp\\WampRouter',
		'FastyBird\\Core\\Routing\\RouteList' => 'FastyBird\\Core\\WebSockets\\Wamp\\RouteList',
		'FastyBird\\Core\\Routing\\WampRoute' => 'FastyBird\\Core\\WebSockets\\Wamp\\WampRoute',
		'FastyBird\\Core\\Server\\WsServer\\Configuration' => 'FastyBird\\Core\\WebSockets\\Server\\Configuration',
		'FastyBird\\Core\\Server\\WsServer\\FlashWrapper' => 'FastyBird\\Core\\WebSockets\\Server\\FlashWrapper',
		'FastyBird\\Core\\Server\\WsServer\\Handlers' => 'FastyBird\\Core\\WebSockets\\Server\\Handlers',
		'FastyBird\\Core\\Server\\WsServer\\IWrapper' => 'FastyBird\\Core\\WebSockets\\Server\\ServerWrapper',
		'FastyBird\\Core\\Server\\WsServer\\Server' => 'FastyBird\\Core\\WebSockets\\Server\\ServerRuntime',
		'FastyBird\\Core\\Server\\WsServer\\Wrapper' => 'FastyBird\\Core\\WebSockets\\Server\\Wrapper',
		'FastyBird\\Core\\Subscribers\\WsServer\\Client' => 'FastyBird\\Core\\WebSockets\\Subscribers\\Client',
		'FastyBird\\Core\\Subscribers\\WsServer\\OnServerStartHandler' => 'FastyBird\\Core\\WebSockets\\Subscribers\\OnServerStartHandler',
		'FastyBird\\Core\\Topics\\WsServer\\Drivers\\IDriver' => 'FastyBird\\Core\\WebSockets\\Topics\\Drivers\\IDriver',
		'FastyBird\\Core\\Topics\\WsServer\\Drivers\\InMemory' => 'FastyBird\\Core\\WebSockets\\Topics\\Drivers\\InMemory',
		'FastyBird\\Core\\Topics\\WsServer\\IStorage' => 'FastyBird\\Core\\WebSockets\\Topics\\IStorage',
		'FastyBird\\Core\\Topics\\WsServer\\Storage' => 'FastyBird\\Core\\WebSockets\\Topics\\Storage',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [
		'src/FastyBird/Core/Core/src/Compat/User.php' => 'src/FastyBird/Core/Core/src/WebSockets/Compat/User.php',
	],
];
