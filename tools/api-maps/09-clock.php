<?php declare(strict_types = 1);

/**
 * E5.9 (#641): adopt the PSR-20 clock (#460 §3.7). The change list format is documented in
 * tools/api-surface.php.
 *
 * Clock\Clock is deleted, not renamed: its replacement, Psr\Clock\ClockInterface, is not a
 * FastyBird\Core type, so the manifest does not record it. Removing it drops it from the two
 * clocks' interface lists; the 'changed' items put Psr\Clock\ClockInterface there and in the three
 * constructor parameters that took the old interface. getNow() becomes now() on both clocks, and
 * its declared return type narrows from DateTimeInterface to DateTimeImmutable. SystemClock
 * becomes final.
 */

return [
	'renamed' => [
		'FastyBird\\Core\\Clock\\FrozenClock::getNow()' => 'FastyBird\\Core\\Clock\\FrozenClock::now()',
		'FastyBird\\Core\\Clock\\SystemClock::getNow()' => 'FastyBird\\Core\\Clock\\SystemClock::now()',
	],
	'removed' => [
		'FastyBird\\Core\\Clock\\Clock',
	],
	'changed' => [
		'FastyBird\\Core\\Clock\\FrozenClock' => [
			'interfaces' => ['Psr\\Clock\\ClockInterface'],
		],
		'FastyBird\\Core\\Clock\\FrozenClock::now()' => [
			'return' => 'DateTimeImmutable',
		],
		'FastyBird\\Core\\Clock\\SystemClock' => [
			'final' => true,
			'interfaces' => ['Psr\\Clock\\ClockInterface'],
		],
		'FastyBird\\Core\\Clock\\SystemClock::now()' => [
			'return' => 'DateTimeImmutable',
		],
		'FastyBird\\Core\\Persistence\\Utilities\\DateTimeProvider::__construct()' => [
			'parameters.$clock.type' => 'Psr\\Clock\\ClockInterface',
		],
		'FastyBird\\Core\\Security\\Identity\\TokenBuilder::__construct()' => [
			'parameters.$clock.type' => 'Psr\\Clock\\ClockInterface',
		],
		'FastyBird\\Core\\Security\\Identity\\TokenValidator::__construct()' => [
			'parameters.$clock.type' => 'Psr\\Clock\\ClockInterface',
		],
	],
];
