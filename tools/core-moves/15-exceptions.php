<?php declare(strict_types = 1);

/**
 * E3.15 (#508): shared Exceptions imports.
 *
 * Written by hand from the approved census, section "Exceptions (8 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md.
 *
 * `FastyBird\Core\Exceptions` is the shared root (#458 §3.9, §4 E3.15) -- it does not move
 * and none of its 8 members (`Exception`, `InvalidArgument`, `InvalidController`,
 * `InvalidLink`, `InvalidState`, `Logic`, `Runtime`, `UnexpectedValue`) move or rename. This
 * map only normalizes every illegal alias of that namespace across the repository (~671
 * referencing files, per the census) to the legal bare import or two-segment alias.
 *
 * This is the last capability PR of Epic E3 (#458 §4): after it every Core namespace has
 * either moved or been normalized, and tools/naming-baseline.txt is expected to be empty.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [],
	'normalize' => [
		'FastyBird\\Core\\Exceptions',
	],
	'namespaces' => [],
	'files' => [],
];
