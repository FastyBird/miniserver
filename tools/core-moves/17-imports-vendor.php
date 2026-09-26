<?php declare(strict_types = 1);

/**
 * #541: normalize the vendor (non-FastyBird) namespaces that still carry a stale alias, or a
 * bare import that collides with a same-kind sibling's short name, in `tools/naming-baseline.txt`
 * at `ec848b756` -- exactly the six namespaces below, re-derived from the baseline itself:
 *
 *   Nette\DI                            -- bare, collides with `FastyBird\Core\DI as CoreDI`
 *   Nette\Caching                       -- bare, collides with `... Caching as ...Caching`
 *   Symfony\Bridge\Monolog              -- illegal alias `SymfonyMonolog`
 *   Symfony\Component\EventDispatcher   -- bare, collides with a Core sibling in one file
 *   Symfony\Contracts\EventDispatcher   -- illegal alias `SymfonyEventDispatcherContracts`
 *   Psr\EventDispatcher                 -- illegal alias `WsServerEventDispatcher`
 *
 * `classes`, `namespaces` and `files` are empty -- none of these move, they are only
 * (re-)aliased wherever `make naming`'s check 4 requires it. Normalizing a vendor namespace is
 * repository-wide by nature (#541's plan comment 3): every file importing one of these six
 * namespaces is a candidate, but the tool only rewrites a file where the import actually needs
 * work (an illegal alias, or a bare import that collides) -- see
 * fbMoveNormalizedImportNeedsWork() in tools/move-core-symbols.php.
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [],
	'normalize' => [
		'Nette\\DI',
		'Nette\\Caching',
		'Symfony\\Bridge\\Monolog',
		'Symfony\\Component\\EventDispatcher',
		'Symfony\\Contracts\\EventDispatcher',
		'Psr\\EventDispatcher',
	],
	'namespaces' => [],
	'files' => [],
];
