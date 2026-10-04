<?php declare(strict_types = 1);

/**
 * E5.4 (#636): Core's single-implementation Api, Http, Persistence and Security interfaces,
 * collapsed into their implementations exactly as census T1 approves
 * (docs/superpowers/plans/2026-10-04-core-e5-census.md, rows T1.a, T1.b, T1.c and T1.e; IRouter
 * under R1, escalation X3). The move map is tools/core-moves/20-interfaces.php; the change list
 * format is documented in tools/api-surface.php.
 *
 * Besides the 27 collapses (each a 'renamed' onto the existing implementation, which also drops it
 * from every `interfaces` list), the 'changed' items are the two hand-fix consequences the PR
 * declares: a collapsed class's references to itself spelled `self` (RequireSelfReference), and the
 * `@throws` the collapsed interfaces used to hide, now declared on Core's callers (PHPStan's
 * missingCheckedExceptionInThrows). No class becomes final: every target already is, except Router,
 * which stays extended (R1).
 *
 * Declared DI changes (tools/di-snapshot.php, all 47 containers, base main @ 0e1ecce34): only the
 * `$wiring` entries of the 3 collapsed interfaces that had one disappear -- `Http\Routing\IRouter`
 * (46 containers), `Persistence\Mapping\IEntityMapper` (42) and `Security\Identity\IUserStorage`
 * (41). In every container each one listed exactly the candidates its implementation's own
 * `$wiring` entry already lists, so no type gains or loses a candidate: `Routing\Router` keeps
 * fbCore.http.routing.router as its one autowired service, with the HomeKit and NsPanel routers
 * non-autowired. No service, type, setup, tag, alias or order changes.
 */

return [
	'renamed' => [
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
	'changed' => [
		// a collapsed class naming itself: RequireSelfReference spells it `self`
		'FastyBird\\Core\\Api\\Encoding\\Document::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ErrorObjectCollection::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\LinkObjectCollection::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\MetaObjectCollection::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\RelationshipObjectCollection::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceIdentifierCollection::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceIdentifierObject::isSame()' => [
			'parameters.$identifier.type' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceObjectCollection::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObject::copy()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObject::remove()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObject::set()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObject::setMany()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Api\\Encoding\\Objects\\StandardObjectCollection::create()' => [
			'return' => 'self',
		],
		'FastyBird\\Core\\Http\\Routing\\RouteCollector::__construct()' => [
			'parameters.$routeCollector.type' => '?self',
		],

		// the @throws the collapsed interfaces hid, declared on Core's callers
		'FastyBird\\Core\\Api\\Encoding\\Objects\\ResourceIdentifierObject::mapType()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Api\\Hydrators\\Container::findHydrator()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Exceptions\\Runtime',
				'Nette\\DI\\MissingServiceException',
			],
		],
		'FastyBird\\Core\\Api\\Hydrators\\Hydrator::hydrateHasMany()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Api\\Hydrators\\Hydrator::hydrateHasOne()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Api\\Hydrators\\Hydrator::hydrateRelationships()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Middleware\\Router::__invoke()' => [
			'throws' => [
				'InvalidArgumentException',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Route::handle()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\RouteParser::fullUrlFor()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\RouteParser::relativeUrlFor()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\RouteParser::urlFor()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::any()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::delete()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::get()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::getNamedRoute()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::map()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::options()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::patch()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::post()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::put()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Routing\\Router::urlFor()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Http\\Server\\Application::run()' => [
			'throws' => [
				'InvalidArgumentException',
				'RuntimeException',
			],
		],
		'FastyBird\\Core\\Persistence\\Crud\\Create\\EntityCreator::create()' => [
			'throws' => [
				'Doctrine\\DBAL\\Exception\\UniqueConstraintViolationException',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Persistence\\Exceptions\\EntityCreation',
				'ReflectionException',
			],
		],
		'FastyBird\\Core\\Persistence\\Crud\\Update\\EntityUpdater::update()' => [
			'throws' => [
				'Doctrine\\DBAL\\Exception\\UniqueConstraintViolationException',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Persistence\\Exceptions\\EntityCreation',
				'ReflectionException',
			],
		],
		'FastyBird\\Core\\Security\\Models\\Policies\\Manager::create()' => [
			'throws' => [
				'Doctrine\\DBAL\\Exception\\UniqueConstraintViolationException',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Persistence\\Exceptions\\EntityCreation',
				'ReflectionException',
			],
		],
		'FastyBird\\Core\\Security\\Models\\Policies\\Manager::update()' => [
			'throws' => [
				'Doctrine\\DBAL\\Exception\\UniqueConstraintViolationException',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Persistence\\Exceptions\\EntityCreation',
				'ReflectionException',
			],
		],
		'FastyBird\\Core\\Security\\Models\\Tokens\\Manager::create()' => [
			'throws' => [
				'Doctrine\\DBAL\\Exception\\UniqueConstraintViolationException',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Persistence\\Exceptions\\EntityCreation',
				'ReflectionException',
			],
		],
		'FastyBird\\Core\\Security\\Models\\Tokens\\Manager::update()' => [
			'throws' => [
				'Doctrine\\DBAL\\Exception\\UniqueConstraintViolationException',
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Persistence\\Exceptions\\EntityCreation',
				'ReflectionException',
			],
		],
	],
];
