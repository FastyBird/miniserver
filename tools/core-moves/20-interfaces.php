<?php declare(strict_types = 1);

/**
 * E5.4 (#636): collapse Core's single-implementation Api, Http, Persistence and Security
 * interfaces. The pilot of the move tool's `collapse` key (#460 §3.1, §3.2).
 *
 * Written by hand from the approved census, table T1 of
 * docs/superpowers/plans/2026-10-04-core-e5-census.md: rows T1.a (Api, 16), T1.b (Http, 8),
 * T1.c (Persistence: `IEntityCrud`, `IEntityMapper`) and T1.e (Security: `IUserStorage`) --
 * 27 interfaces, every one of them "collapse". None of these rows is "keep as <RoleName>", so
 * the `classes` key is empty. `IEntityRemoved` and `TEntityRemoved` (T1.c) were deleted by
 * #635; the WebSockets rows (T1.f) are #637's.
 *
 * `Http\Routing\IRouter` collapses under rule R1 (census §0.3, X3): its other implementers --
 * Core's `ServerRouter` and the HomeKit and NsPanel `Router\Router` -- all extend `Router`, so
 * `Router` stays non-final. Every other target is already `final` (T1 decision rule): no class
 * becomes `final` and none loses it.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
	'collapse' => [
		// T1.a Api (16)
		'FastyBird\\Core\\Api\\Encoding\\IDocument' => 'FastyBird\\Core\\Api\\Encoding\\Document',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IErrorObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ErrorObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IErrorObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ErrorObjectCollection',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ILinkObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\LinkObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ILinkObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\LinkObjectCollection',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IMetaObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\MetaObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IMetaObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\MetaObjectCollection',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IRelationshipObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\RelationshipObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IRelationshipObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\RelationshipObjectCollection',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceIdentifierCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceIdentifierCollection',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceIdentifierObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceIdentifierObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IResourceObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceObjectCollection',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ISourceObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\SourceObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IStandardObject' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObject',
		'FastyBird\\Core\\Api\\Encoding\\Objects\\IStandardObjectCollection' => 'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObjectCollection',

		// T1.b Http (8); IRouter under R1, Router stays non-final
		'FastyBird\\Core\\Http\\Controllers\\IControllerResolver' => 'FastyBird\\Core\\Http\\Controllers\\ControllerResolver',
		'FastyBird\\Core\\Http\\Middleware\\IMiddlewareDispatcher' => 'FastyBird\\Core\\Http\\Middleware\\MiddlewareDispatcher',
		'FastyBird\\Core\\Http\\Routing\\Handlers\\IRequestHandler' => 'FastyBird\\Core\\Http\\Routing\\Handlers\\RequestHandler',
		'FastyBird\\Core\\Http\\Routing\\IRoute' => 'FastyBird\\Core\\Http\\Routing\\Route',
		'FastyBird\\Core\\Http\\Routing\\IRouteCollector' => 'FastyBird\\Core\\Http\\Routing\\RouteCollector',
		'FastyBird\\Core\\Http\\Routing\\IRouteGroup' => 'FastyBird\\Core\\Http\\Routing\\RouteGroup',
		'FastyBird\\Core\\Http\\Routing\\IRouteParser' => 'FastyBird\\Core\\Http\\Routing\\RouteParser',
		'FastyBird\\Core\\Http\\Routing\\IRouter' => 'FastyBird\\Core\\Http\\Routing\\Router',

		// T1.c Persistence (2)
		'FastyBird\\Core\\Persistence\\Crud\\IEntityCrud' => 'FastyBird\\Core\\Persistence\\Crud\\EntityCrud',
		'FastyBird\\Core\\Persistence\\Mapping\\IEntityMapper' => 'FastyBird\\Core\\Persistence\\Mapping\\EntityMapper',

		// T1.e Security (1)
		'FastyBird\\Core\\Security\\Identity\\IUserStorage' => 'FastyBird\\Core\\Security\\Identity\\UserStorage',
	],
];
