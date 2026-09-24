<?php declare(strict_types = 1);

/**
 * E3.7 (#500): Documents.
 *
 * Written by hand from the approved census, section "Documents (27 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, and the collision
 * table's `TCreatedAt`/`TOwner`/`TUpdatedAt` row, and nothing else.
 *
 * `Documents\` itself does not move -- it is already its own capability namespace. Only
 * 4 files physically relocate into it: `Events\{LoadClassMetadata,PostLoad,PreLoad}` (out
 * of the shared `Events\` root) and `Exceptions\MalformedInput` (out of the shared
 * `Exceptions\` root). The other 23 files in the census's "Documents (27 files)" table
 * keep their current FQCN unchanged -- 20 of them exactly as-is, and 3 renamed in place
 * (no namespace change): `TCreatedAt` -> `HasCreatedAt`, `TOwner` -> `HasOwner`,
 * `TUpdatedAt` -> `HasUpdatedAt` (collision table: the `CreatedAt`/`Owner`/`UpdatedAt`
 * interfaces are unprefixed and untouched). `Entities\SimpleAuth\TOwner` is a DIFFERENT
 * trait -- not in this map, not touched -- it moves with Security in #506.
 *
 * `normalize` clears the 321 naming-baseline `alias ... FastyBird\Core\Documents as
 * ... (expected CoreDocuments)` entries: every illegal alias of the (non-moving)
 * `FastyBird\Core\Documents` namespace is re-aliased to the legal two-segment
 * `CoreDocuments`, without moving a file.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Events\\LoadClassMetadata' => 'FastyBird\\Core\\Documents\\Events\\LoadClassMetadata',
		'FastyBird\\Core\\Events\\PostLoad' => 'FastyBird\\Core\\Documents\\Events\\PostLoad',
		'FastyBird\\Core\\Events\\PreLoad' => 'FastyBird\\Core\\Documents\\Events\\PreLoad',
		'FastyBird\\Core\\Exceptions\\MalformedInput' => 'FastyBird\\Core\\Documents\\Exceptions\\MalformedInput',
		'FastyBird\\Core\\Documents\\TCreatedAt' => 'FastyBird\\Core\\Documents\\HasCreatedAt',
		'FastyBird\\Core\\Documents\\TOwner' => 'FastyBird\\Core\\Documents\\HasOwner',
		'FastyBird\\Core\\Documents\\TUpdatedAt' => 'FastyBird\\Core\\Documents\\HasUpdatedAt',
	],
	'normalize' => [
		'FastyBird\\Core\\Documents',
	],
	'namespaces' => [],
	'files' => [],
];
