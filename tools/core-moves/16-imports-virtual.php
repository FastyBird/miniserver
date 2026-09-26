<?php declare(strict_types = 1);

/**
 * #541 pilot: normalize the Virtual connector's own capability-shaped namespaces wherever a
 * bare import of one collides with a same-kind sibling's short name (most often the Devices
 * module's own `Documents`/`Entities`/`Queries`, imported under its two-segment alias already).
 *
 * `FastyBird\Connector\Virtual\Documents`, `...\Entities` and `...\Queries` do not move -- they
 * are simply re-aliased, in every file where the bare import needs it, to `VirtualDocuments`,
 * `VirtualEntities` and `VirtualQueries`.
 *
 * Virtual's `Nette\DI` collision (tools/naming-baseline.txt) is deliberately not in this map:
 * normalizing `Nette\DI` would apply repository-wide, well beyond Virtual, and is left to its
 * own PR.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [],
	'normalize' => [
		'FastyBird\\Connector\\Virtual\\Documents',
		'FastyBird\\Connector\\Virtual\\Entities',
		'FastyBird\\Connector\\Virtual\\Queries',
	],
	'namespaces' => [],
	'files' => [],
];
