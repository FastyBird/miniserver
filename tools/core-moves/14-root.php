<?php declare(strict_types = 1);

/**
 * E3.14 (#507): dissolve Core's root namespaces.
 *
 * Written by hand from the approved census, section "Root (dissolved) (19 files)" of
 * docs/superpowers/plans/2026-09-23-core-e3-capability-census.md. The census lists 19 files;
 * `Boot/Bootstrap.php` and `Boot/Configurator.php` stay exactly where they are (`Boot\` is one
 * of the Definition of Done's allowed root namespaces, like `DI\`), and `DI/CoreExtension.php`
 * stays at its current FQCN too (it is the map's own home namespace) -- 16 entries move. The
 * issue also lists `Subscribers\Application\Console.php`, already moved to
 * `Logging\Subscribers\Console` by #524 (census decision 7); it is untouched here, per the
 * dispatch's resolved exception.
 *
 * - `Caching\Application\{MemoryAdapterStorage,MemoryStorage}` (2) -> `Caching\*` -- names
 *   unchanged; both already `Caching\Application`'s only members, so that sub-namespace fully
 *   vacates.
 * - `Configuration\Configuration` -> `Configuration` (census decision 5: a one-class
 *   sub-namespace flattens into a root-level class, no role name invented).
 * - `Constants\Constants` -> `Constants` (same decision).
 * - `EventLoop\Application\{Status,Wrapper}` (2) -> `EventLoop\*` -- names unchanged;
 *   `EventLoop\Application` fully vacates.
 * - `Events\{EventLoopStarted,EventLoopStopped,EventLoopStopping}` (3) -> `EventLoop\Events\*`
 *   (Events/Exceptions assignment) -- the last 3 `EventLoop*` events out of the shared
 *   `Events\` root.
 * - `Events\{PresenterRequest,PresenterResponse}` (2) -> `Presenters\Events\*` (census
 *   decision 3) -- the last 2 files out of the shared `Events\` root, which is gone after this
 *   PR (all 36 of its files are now assigned across every E3 PR).
 * - `Presenters\Application\{BasePresenter,DefaultPresenter}` (2) -> `Presenters\*` -- names
 *   unchanged; `Presenters\Application` fully vacates.
 * - `Routing\AppRouter` -> `Presenters\AppRouter` (the `Routing\` three-way split, #504's
 *   map's docblock) -- the last file under `Routing\`, which no longer exists after this PR.
 * - `Subscribers\Application\EventLoopLifeCycle` -> `EventLoop\Subscribers\EventLoopLifeCycle`
 *   (census decision 8) -- the last file under `Subscribers\`, which no longer exists after
 *   this PR (its `Console.php` sibling already left in #524).
 * - `UI\Application\TemplateFactory` -> `UI\TemplateFactory` -- name unchanged; `UI\Application`
 *   fully vacates.
 *
 * `normalize` clears the naming-baseline `alias ... FastyBird\Core\Boot as ... (expected
 * CoreBoot)` (73 entries) and `alias ... FastyBird\Core\Constants as ... (expected
 * CoreConstants)` (81 entries) groups: every illegal alias of the (non-moving) `FastyBird\
 * Core\Boot` namespace and of the (moving, but whose new class FQCN is textually identical to
 * its own old namespace) `FastyBird\Core\Constants` namespace is re-aliased to whatever
 * tools/check-naming.php's rule now computes as legal for that file -- bare unless a sibling
 * import in that same file also collides at two segments, same mechanism as `FastyBird\Core\
 * Http` in #504's map. `FastyBird\Core\Configuration` needs no such entry: every consumer is
 * inside Core itself (4 files) and already imports it bare, so no alias-group exists for it.
 *
 * Tool change: two fixes to tools/move-core-symbols.php, both required by `Configuration` and
 * `Constants` landing directly under Core's own root namespace (`FastyBird\Core\Configuration`,
 * `FastyBird\Core\Constants`) rather than under a capability sub-namespace -- the first time
 * any E3 map's target has done that:
 *
 * 1. fbMoveVacatedNamespaces(): a class's old namespace (`FastyBird\Core\Constants`, home only
 *    to `Constants\Constants`) would normally be marked vacated once its one member moves out.
 *    Here the member's OWN new FQCN (`FastyBird\Core\Constants`) is textually that same
 *    namespace -- the name lives on, now as a class. Without the fix, every existing `use
 *    FastyBird\Core\Constants [as X];` (81 files) and `use FastyBird\Core\Configuration;` (4
 *    files) would be misreported as a stale reference to a dead namespace, and the stale-
 *    reference report -- which greps the whole tree for vacated-namespace text -- would flag
 *    every remaining, entirely correct, post-move `Configuration::`/`Constants::` mention as a
 *    finding. Fixed by excluding a namespace from "vacated" when some class's own new FQCN
 *    reoccupies it exactly.
 * 2. fbMoveRewritePhp()'s $express closure: for a target whose namespace is a capability
 *    (`FastyBird\Core\EventLoop\Wrapper`, namespace `FastyBird\Core\EventLoop`), the existing
 *    fallback imports that namespace and writes the reference relative to it
 *    (`EventLoop\Wrapper`) -- correct, and how every prior E3 map's targets have always
 *    resolved. For a target whose namespace IS Core's own root (`FastyBird\Core\Constants`,
 *    hit by the 3 files that import the class directly rather than the namespace, e.g.
 *    `use FastyBird\Core\Constants\Constants as MetadataConstants;`), the same fallback would
 *    import `FastyBird\Core` itself and write `Core\Constants` -- there is no capability
 *    sub-namespace left to strip a segment from. Fixed by importing the class itself in that
 *    case, exactly like the "existing import of the class itself" branch just above it.
 *
 * Proven with `bash ~/.cache/e3plan/e3-regress.sh <sha>` against all 11 prior maps: ALL
 * REPRODUCED (see the PR description for the run).
 *
 * Applied by tools/move-core-symbols.php; see that file for the format.
 */
return [
	'classes' => [
		'FastyBird\\Core\\Caching\\Application\\MemoryAdapterStorage' => 'FastyBird\\Core\\Caching\\MemoryAdapterStorage',
		'FastyBird\\Core\\Caching\\Application\\MemoryStorage' => 'FastyBird\\Core\\Caching\\MemoryStorage',
		'FastyBird\\Core\\Configuration\\Configuration' => 'FastyBird\\Core\\Configuration',
		'FastyBird\\Core\\Constants\\Constants' => 'FastyBird\\Core\\Constants',
		'FastyBird\\Core\\EventLoop\\Application\\Status' => 'FastyBird\\Core\\EventLoop\\Status',
		'FastyBird\\Core\\EventLoop\\Application\\Wrapper' => 'FastyBird\\Core\\EventLoop\\Wrapper',
		'FastyBird\\Core\\Events\\EventLoopStarted' => 'FastyBird\\Core\\EventLoop\\Events\\EventLoopStarted',
		'FastyBird\\Core\\Events\\EventLoopStopped' => 'FastyBird\\Core\\EventLoop\\Events\\EventLoopStopped',
		'FastyBird\\Core\\Events\\EventLoopStopping' => 'FastyBird\\Core\\EventLoop\\Events\\EventLoopStopping',
		'FastyBird\\Core\\Events\\PresenterRequest' => 'FastyBird\\Core\\Presenters\\Events\\PresenterRequest',
		'FastyBird\\Core\\Events\\PresenterResponse' => 'FastyBird\\Core\\Presenters\\Events\\PresenterResponse',
		'FastyBird\\Core\\Presenters\\Application\\BasePresenter' => 'FastyBird\\Core\\Presenters\\BasePresenter',
		'FastyBird\\Core\\Presenters\\Application\\DefaultPresenter' => 'FastyBird\\Core\\Presenters\\DefaultPresenter',
		'FastyBird\\Core\\Routing\\AppRouter' => 'FastyBird\\Core\\Presenters\\AppRouter',
		'FastyBird\\Core\\Subscribers\\Application\\EventLoopLifeCycle' => 'FastyBird\\Core\\EventLoop\\Subscribers\\EventLoopLifeCycle',
		'FastyBird\\Core\\UI\\Application\\TemplateFactory' => 'FastyBird\\Core\\UI\\TemplateFactory',
	],
	'normalize' => [
		'FastyBird\\Core\\Boot',
		'FastyBird\\Core\\Constants',
	],
	'namespaces' => [],
	'files' => [],
];
