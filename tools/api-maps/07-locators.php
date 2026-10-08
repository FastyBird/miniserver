<?php declare(strict_types = 1);

/**
 * E5.7 (#639): replace Core's service locators with injection (#460 §3.5, census T7). The change
 * list format is documented in tools/api-surface.php.
 *
 * Api\Encoding\Builder, Api\Middleware\JsonApiMiddleware and Api\Hydrators\Container took
 * Nette\DI\Container and looked the schema container up with getByType() on first use. Each now
 * takes Api\Encoding\SchemaContainer in its constructor, at the position the container held, as
 * `$schemaContainer`. A parameter rename has no 'renamed' form, and the manifest keeps parameters
 * in declaration order, so each constructor's whole parameter list is restated. findHydrator() no
 * longer declares Nette\DI\MissingServiceException, which only the lookup could throw. The lazy
 * definition that breaks the cycle (fbCore.api.schemas.container) is DI, not API: it is in the DI
 * snapshot, not here.
 */

return [
	'changed' => [
		'FastyBird\\Core\\Api\\Encoding\\Builder::__construct()' => [
			// the whole list: the schema container replaces the DI container, first
			'parameters' => [
				'$schemaContainer' => [
					'position' => 0,
					'type' => 'FastyBird\\Core\\Api\\Encoding\\SchemaContainer',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$metaAuthor' => [
					'position' => 1,
					'type' => 'array|string',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$metaCopyright' => [
					'position' => 2,
					'type' => '?string',
					'optional' => true,
					'default' => 'null',
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
			],
		],
		'FastyBird\\Core\\Api\\Hydrators\\Container::__construct()' => [
			// the whole list: the schema container replaces the DI container, first
			'parameters' => [
				'$schemaContainer' => [
					'position' => 0,
					'type' => 'FastyBird\\Core\\Api\\Encoding\\SchemaContainer',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$logger' => [
					'position' => 1,
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
		'FastyBird\\Core\\Api\\Hydrators\\Container::findHydrator()' => [
			'throws' => [
				'FastyBird\\Core\\Exceptions\\InvalidArgument',
				'FastyBird\\Core\\Exceptions\\InvalidState',
				'FastyBird\\Core\\Exceptions\\Runtime',
			],
		],
		'FastyBird\\Core\\Api\\Middleware\\JsonApiMiddleware::__construct()' => [
			// the whole list: the schema container replaces the DI container, second
			'parameters' => [
				'$responseFactory' => [
					'position' => 0,
					'type' => 'Psr\\Http\\Message\\ResponseFactoryInterface',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$schemaContainer' => [
					'position' => 1,
					'type' => 'FastyBird\\Core\\Api\\Encoding\\SchemaContainer',
					'optional' => false,
					'default' => null,
					'defaultConstant' => null,
					'variadic' => false,
					'byRef' => false,
					'promoted' => true,
					'attributes' => [],
				],
				'$logger' => [
					'position' => 2,
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
	],
];
