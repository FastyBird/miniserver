<?php declare(strict_types = 1);

/**
 * E3.4 (#497): Logging.
 *
 * Written by hand from the approved census, section "Logging (3 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, and nothing else.
 * The census's decision 7 reassigns `Subscribers\Application\Console` here (a
 * content-driven correction: its body wires a Monolog\Logger and a
 * Symfony\Bridge\Monolog\Handler\ConsoleHandler in response to ConsoleEvents::COMMAND,
 * a Logging concern by content, not a generic "Application" one), so this map moves
 * 3 files, not the 2 the pre-census issue text named. `Helpers\Tools` also holds
 * `Database`, which stays (#458 E3.9); files importing `Helpers\Tools` for both keep
 * that import and its baseline entry.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Helpers\\Tools\\Logger' => 'FastyBird\\Core\\Logging\\Logger',
		'FastyBird\\Core\\Helpers\\Tools\\Sentry' => 'FastyBird\\Core\\Logging\\Sentry',
		'FastyBird\\Core\\Subscribers\\Application\\Console' => 'FastyBird\\Core\\Logging\\Subscribers\\Console',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
