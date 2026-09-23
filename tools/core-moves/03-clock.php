<?php declare(strict_types = 1);

/**
 * E3.3 (#496): Clock.
 *
 * Written by hand from the approved census, section "Clock (3 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, and nothing else.
 * `Clock\Clock` is the accepted temporary stutter (#458 §3.10); #460 replaces the
 * interface with PSR-20.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Services\\DateTimeFactory\\Clock' => 'FastyBird\\Core\\Clock\\Clock',
		'FastyBird\\Core\\Services\\DateTimeFactory\\FrozenClock' => 'FastyBird\\Core\\Clock\\FrozenClock',
		'FastyBird\\Core\\Services\\DateTimeFactory\\SystemClock' => 'FastyBird\\Core\\Clock\\SystemClock',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
