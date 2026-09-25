<?php declare(strict_types = 1);

/**
 * E3.11 (#504): Http.
 *
 * Written by hand from the approved census, section "Http (50 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, and the `Routing\` three-way
 * split section, and the Events/Exceptions assignment. 50 files in the census's table; 8 of
 * them (`Http\{Entity,Response,ResponseAttributes,ResponseFactory,ScalarEntity,ServerResponse,
 * ServerResponseFactory,Stream}`) are already at their target FQCN and do not move -- this map
 * has 42 entries:
 *
 * - `Commands\HttpServer` -> `Http\Commands\HttpServer`
 * - `Controllers\SlimRouter\*` (2) -> `Http\Controllers\*`
 * - `Events\HttpServer{Error,Request,Response,Startup}` (4) -> `Http\Events\*`
 * - `Exceptions\{FileNotFound,Http,HttpMethodNotAllowed,HttpNotFound,HttpSpecialized,
 *   StreamResourceCall}` (6) -> `Http\Exceptions\*`
 * - `Middleware\SlimRouter\*` (2) -> `Http\Middleware\*`
 * - `Middleware\WebServer\{Cors,Router,StaticFiles}` (3) -> `Http\Middleware\*`
 * - `Routing\*` and `Routing\Handlers\*`, the Slim-derived HTTP router (20) -> `Http\Routing\*`
 *   and `Http\Routing\Handlers\*` -- names unchanged except `Handlers\IHandler` -> `Handlers\
 *   Handler` (policy B, 2+ implementers inside Core, per the census's name table and the
 *   `Routing\` three-way split section). `Routing\{AppRouter,IWampRouter,RouteList,WampRoute}`
 *   are NOT in this map -- they stay in `Routing\` for #458's E3.12 (WebSockets) and E3.14
 *   (root) to move; landing any of them under `Http\` is an escalation.
 * - `Server\HttpServer\*` (3) -> `Http\Server\*`
 * - `Subscribers\HttpServer\Server` -> `Http\Subscribers\Server`
 *
 * The 5 WS-handshake files under `Http\` (`IRequest`, `Request`, `RequestFactory`,
 * `IResponse`, `WampResponse`) are also NOT in this map -- census collision table: they stay
 * `Http\*` today and move to `WebSockets\Handshake\*` in E3.12.
 *
 * `normalize` clears the naming-baseline `alias ... FastyBird\Core\Http as ... (expected
 * CoreHttp)` entries: every illegal alias of the (non-moving, already-existing) `FastyBird\
 * Core\Http` namespace is re-aliased to the legal two-segment `CoreHttp`, without moving a
 * file -- same mechanism as `FastyBird\Core\Documents` in #500's map.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Commands\\HttpServer' => 'FastyBird\\Core\\Http\\Commands\\HttpServer',
		'FastyBird\\Core\\Controllers\\SlimRouter\\ControllerResolver' => 'FastyBird\\Core\\Http\\Controllers\\ControllerResolver',
		'FastyBird\\Core\\Controllers\\SlimRouter\\IControllerResolver' => 'FastyBird\\Core\\Http\\Controllers\\IControllerResolver',
		'FastyBird\\Core\\Events\\HttpServerError' => 'FastyBird\\Core\\Http\\Events\\HttpServerError',
		'FastyBird\\Core\\Events\\HttpServerRequest' => 'FastyBird\\Core\\Http\\Events\\HttpServerRequest',
		'FastyBird\\Core\\Events\\HttpServerResponse' => 'FastyBird\\Core\\Http\\Events\\HttpServerResponse',
		'FastyBird\\Core\\Events\\HttpServerStartup' => 'FastyBird\\Core\\Http\\Events\\HttpServerStartup',
		'FastyBird\\Core\\Exceptions\\FileNotFound' => 'FastyBird\\Core\\Http\\Exceptions\\FileNotFound',
		'FastyBird\\Core\\Exceptions\\Http' => 'FastyBird\\Core\\Http\\Exceptions\\Http',
		'FastyBird\\Core\\Exceptions\\HttpMethodNotAllowed' => 'FastyBird\\Core\\Http\\Exceptions\\HttpMethodNotAllowed',
		'FastyBird\\Core\\Exceptions\\HttpNotFound' => 'FastyBird\\Core\\Http\\Exceptions\\HttpNotFound',
		'FastyBird\\Core\\Exceptions\\HttpSpecialized' => 'FastyBird\\Core\\Http\\Exceptions\\HttpSpecialized',
		'FastyBird\\Core\\Exceptions\\StreamResourceCall' => 'FastyBird\\Core\\Http\\Exceptions\\StreamResourceCall',
		'FastyBird\\Core\\Middleware\\SlimRouter\\IMiddlewareDispatcher' => 'FastyBird\\Core\\Http\\Middleware\\IMiddlewareDispatcher',
		'FastyBird\\Core\\Middleware\\SlimRouter\\MiddlewareDispatcher' => 'FastyBird\\Core\\Http\\Middleware\\MiddlewareDispatcher',
		'FastyBird\\Core\\Middleware\\WebServer\\Cors' => 'FastyBird\\Core\\Http\\Middleware\\Cors',
		'FastyBird\\Core\\Middleware\\WebServer\\Router' => 'FastyBird\\Core\\Http\\Middleware\\Router',
		'FastyBird\\Core\\Middleware\\WebServer\\StaticFiles' => 'FastyBird\\Core\\Http\\Middleware\\StaticFiles',
		'FastyBird\\Core\\Routing\\FastRouteDispatcher' => 'FastyBird\\Core\\Http\\Routing\\FastRouteDispatcher',
		'FastyBird\\Core\\Routing\\Handlers\\IHandler' => 'FastyBird\\Core\\Http\\Routing\\Handlers\\Handler',
		'FastyBird\\Core\\Routing\\Handlers\\IRequestHandler' => 'FastyBird\\Core\\Http\\Routing\\Handlers\\IRequestHandler',
		'FastyBird\\Core\\Routing\\Handlers\\RequestHandler' => 'FastyBird\\Core\\Http\\Routing\\Handlers\\RequestHandler',
		'FastyBird\\Core\\Routing\\Handlers\\RequestResponseArgsHandler' => 'FastyBird\\Core\\Http\\Routing\\Handlers\\RequestResponseArgsHandler',
		'FastyBird\\Core\\Routing\\Handlers\\RequestResponseHandler' => 'FastyBird\\Core\\Http\\Routing\\Handlers\\RequestResponseHandler',
		'FastyBird\\Core\\Routing\\IRoute' => 'FastyBird\\Core\\Http\\Routing\\IRoute',
		'FastyBird\\Core\\Routing\\IRouteCollector' => 'FastyBird\\Core\\Http\\Routing\\IRouteCollector',
		'FastyBird\\Core\\Routing\\IRouteGroup' => 'FastyBird\\Core\\Http\\Routing\\IRouteGroup',
		'FastyBird\\Core\\Routing\\IRouteParser' => 'FastyBird\\Core\\Http\\Routing\\IRouteParser',
		'FastyBird\\Core\\Routing\\IRouter' => 'FastyBird\\Core\\Http\\Routing\\IRouter',
		'FastyBird\\Core\\Routing\\LinkGenerator' => 'FastyBird\\Core\\Http\\Routing\\LinkGenerator',
		'FastyBird\\Core\\Routing\\Route' => 'FastyBird\\Core\\Http\\Routing\\Route',
		'FastyBird\\Core\\Routing\\RouteCollector' => 'FastyBird\\Core\\Http\\Routing\\RouteCollector',
		'FastyBird\\Core\\Routing\\RouteGroup' => 'FastyBird\\Core\\Http\\Routing\\RouteGroup',
		'FastyBird\\Core\\Routing\\RouteHandler' => 'FastyBird\\Core\\Http\\Routing\\RouteHandler',
		'FastyBird\\Core\\Routing\\RouteParser' => 'FastyBird\\Core\\Http\\Routing\\RouteParser',
		'FastyBird\\Core\\Routing\\Router' => 'FastyBird\\Core\\Http\\Routing\\Router',
		'FastyBird\\Core\\Routing\\RoutingResults' => 'FastyBird\\Core\\Http\\Routing\\RoutingResults',
		'FastyBird\\Core\\Routing\\ServerRouter' => 'FastyBird\\Core\\Http\\Routing\\ServerRouter',
		'FastyBird\\Core\\Server\\HttpServer\\Application' => 'FastyBird\\Core\\Http\\Server\\Application',
		'FastyBird\\Core\\Server\\HttpServer\\Factory' => 'FastyBird\\Core\\Http\\Server\\Factory',
		'FastyBird\\Core\\Server\\HttpServer\\MimeTypesList' => 'FastyBird\\Core\\Http\\Server\\MimeTypesList',
		'FastyBird\\Core\\Subscribers\\HttpServer\\Server' => 'FastyBird\\Core\\Http\\Subscribers\\Server',
	],
	'normalize' => [
		'FastyBird\\Core\\Http',
	],
	'namespaces' => [],
	'files' => [],
];
