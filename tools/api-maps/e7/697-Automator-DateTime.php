<?php declare(strict_types = 1);

/**
 * E7.3 (#697), Automator/DateTime. The change list format is documented in tools/api-surface.php.
 *
 *   - 5 constants gain the type string (NAME, the two TYPE and the two SCHEMA_TYPE).
 *   - DI\DateTimeExtension and Exceptions\InvalidArgument become final (census T4).
 *   - Exceptions\Exception stays: D6 keeps a package's marker interface while it has an
 *     implementer, and InvalidArgument is one (census T2).
 *   - The two hydrators' @throws gain ValueError (the two baseline entries this PR deletes). The
 *     methods still throw exactly what they threw.
 */

return [
	'changed' => [
		'FastyBird\\Automator\\DateTime\\DI\\DateTimeExtension::NAME' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DateTime\\DI\\DateTimeExtension' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DateTime\\Entities\\Conditions\\DateCondition::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DateTime\\Entities\\Conditions\\TimeCondition::TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DateTime\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Automator\\DateTime\\Hydrators\\Conditions\\DataCondition::hydrateDateAttribute()' => [
			'throws' => [
				'FastyBird\\Core\\Api\\Exceptions\\JsonApi',
				'ValueError',
			],
		],
		'FastyBird\\Automator\\DateTime\\Hydrators\\Conditions\\TimeCondition::hydrateTimeAttribute()' => [
			'throws' => [
				'FastyBird\\Core\\Api\\Exceptions\\JsonApi',
				'ValueError',
			],
		],
		'FastyBird\\Automator\\DateTime\\Schemas\\Conditions\\DateCondition::SCHEMA_TYPE' => [
			'type' => 'string',
		],
		'FastyBird\\Automator\\DateTime\\Schemas\\Conditions\\TimeCondition::SCHEMA_TYPE' => [
			'type' => 'string',
		],
	],
];
