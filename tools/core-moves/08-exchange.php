<?php declare(strict_types = 1);

/**
 * E3.8 (#501): Exchange.
 *
 * Written by hand from the approved census, section "Exchange (13 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md, plus the collision
 * table's `Publisher\Publisher`/`Publisher\Async\Publisher` stutter row, and nothing
 * else.
 *
 * All 13 files physically relocate: the 5 `*Message*`/`ExchangeError` events out of the
 * shared `Events\` root, and the 8 `Messaging\Exchange\*` files out of `Messaging\` (the
 * `Messaging\` namespace root itself does not survive this move -- nothing else lives
 * under it). Two interfaces are renamed in place to resolve the `Publisher\Publisher`
 * and `Publisher\Async\Publisher` stutters (collision table): both become
 * `MessagePublisher`, one per sub-namespace (`Exchange\Publisher\MessagePublisher` and
 * `Exchange\Publisher\Async\MessagePublisher`) -- not a collision with each other since
 * they live in different namespaces, but importers that need both must alias one
 * (two-segment alias per docs/conventions.md). `Consumers\Consumer` is a near-miss
 * (`Consumers` != `Consumer`) and is left alone per the census.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Events\\AfterMessageConsumed' => 'FastyBird\\Core\\Exchange\\Events\\AfterMessageConsumed',
		'FastyBird\\Core\\Events\\AfterMessagePublished' => 'FastyBird\\Core\\Exchange\\Events\\AfterMessagePublished',
		'FastyBird\\Core\\Events\\BeforeMessageConsumed' => 'FastyBird\\Core\\Exchange\\Events\\BeforeMessageConsumed',
		'FastyBird\\Core\\Events\\BeforeMessagePublished' => 'FastyBird\\Core\\Exchange\\Events\\BeforeMessagePublished',
		'FastyBird\\Core\\Events\\ExchangeError' => 'FastyBird\\Core\\Exchange\\Events\\ExchangeError',
		'FastyBird\\Core\\Messaging\\Exchange\\Consumers\\Consumer' => 'FastyBird\\Core\\Exchange\\Consumers\\Consumer',
		'FastyBird\\Core\\Messaging\\Exchange\\Consumers\\Container' => 'FastyBird\\Core\\Exchange\\Consumers\\Container',
		'FastyBird\\Core\\Messaging\\Exchange\\Consumers\\Info' => 'FastyBird\\Core\\Exchange\\Consumers\\Info',
		'FastyBird\\Core\\Messaging\\Exchange\\Factory' => 'FastyBird\\Core\\Exchange\\Factory',
		'FastyBird\\Core\\Messaging\\Exchange\\Publisher\\Async\\Container' => 'FastyBird\\Core\\Exchange\\Publisher\\Async\\Container',
		'FastyBird\\Core\\Messaging\\Exchange\\Publisher\\Async\\Publisher' => 'FastyBird\\Core\\Exchange\\Publisher\\Async\\MessagePublisher',
		'FastyBird\\Core\\Messaging\\Exchange\\Publisher\\Container' => 'FastyBird\\Core\\Exchange\\Publisher\\Container',
		'FastyBird\\Core\\Messaging\\Exchange\\Publisher\\Publisher' => 'FastyBird\\Core\\Exchange\\Publisher\\MessagePublisher',
	],
	'normalize' => [],
	'namespaces' => [],
	'files' => [],
];
