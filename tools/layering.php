<?php declare(strict_types = 1);

/**
 * layering.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Tools
 * @since          1.0.0
 *
 * @date           12.09.26
 */

/**
 * THE DEPENDENCY-DIRECTION RULE MATRIX FOR THE 34 PACKAGES UNDER src/FastyBird.
 *
 * This file is DATA ONLY. It returns a plain array, it has no logic, no closures and no
 * dependencies. tools/check-layering.php is the thing that reads it. Both files run on a
 * bare checkout with no vendor/ and no autoloader, because the whole point is that the
 * boundary is enforced before `composer install` has a chance to matter.
 *
 * ---------------------------------------------------------------------------------------
 * WHY THIS FILE EXISTS AT ALL
 * ---------------------------------------------------------------------------------------
 *
 * Until now the dependency boundary between the extensions was supposedly described by the
 * 34 per-package composer.json manifests. Those manifests were measured and found to be
 * fiction: 117 undeclared dependency edges against 149 declared, 28 of the 34 manifests
 * wrong. Worse, the boundary was unenforceable BY CONSTRUCTION -- the root autoload is an
 * empty psr-4 map, every package is mirrored into vendor/, and Composer emits one flat
 * 310-prefix classmap, so nothing at runtime or at build time ever consulted a manifest to
 * decide whether Connector/Shelly may see Module/Ui. The manifests are going to be deleted.
 * This file plus tools/check-layering.php is what replaces them, and it landed first so
 * that enforcement never dropped to zero.
 *
 * SCOPE. This enforces DEPENDENCY DIRECTION between packages. It does not check composer
 * manifests, and it never will. An import that is legal by direction but undeclared in a
 * manifest is NOT a violation here.
 *
 * ---------------------------------------------------------------------------------------
 * WHY THE RULES ARE PER-PACKAGE AND NOT JUST PER-TYPE
 * ---------------------------------------------------------------------------------------
 *
 * A coarse "Connector may depend on Module" rule would be useless: it would bless
 * Connector/Shelly -> Module/Ui, which has never happened and must never happen. The
 * measured truth is far tighter than the type layering suggests, so the rules are tight:
 *
 *   every Connector reaches Module/Devices  and no other module (1201 refs, zero strays)
 *   every Automator reaches Module/Triggers and no other module (41 refs, zero strays)
 *
 * Those two live in the TYPE table below rather than as 12 per-package entries, because
 * they are properties of the type -- "a connector is a special plugin for the devices
 * module" is the definition of the word. So a new connector needs no edit to this file and
 * still gets the tight rule.
 *
 * Addon and Bridge cannot be described by their type at all. A bridge IS the pair it
 * bridges; its identity is its peer list. So those two types carry a MANDATORY per-package
 * entry (see 'packageRuleRequiredFor'), and adding a new one without declaring its peers is
 * a hard configuration error rather than a silent fallback to something permissive.
 *
 * ---------------------------------------------------------------------------------------
 * THE MAINTAINER'S DEFINITION OF EACH TYPE, WHICH THE MEASURED MATRIX CONFIRMS EXACTLY
 * ---------------------------------------------------------------------------------------
 *
 *   Library    code that would work in an application that is not this one
 *   Core       the cornerstone of the application
 *   Plugin     an application plugin: redis, web server, sockets
 *   Module     an application module
 *   Automator  a special plugin FOR THE TRIGGERS MODULE
 *   Connector  a special plugin FOR THE DEVICES MODULE, talking to the real world
 *   Addon      adds a special FUNCTION
 *   Bridge     a contract BETWEEN extensions
 *
 * Measured type -> type edge matrix (every `use` statement in src/ and tests/ of all 34
 * packages, self-imports excluded). There are ZERO upward and ZERO sideways crossings in
 * PHP -- no Core->Module, no Module->Plugin, no Plugin->Module, no Connector->Connector,
 * no Module->Module:
 *
 *   Library    -> nothing at all, 0 out-edges
 *   Core       -> Core 15, Library 20
 *   Plugin     -> Core 71, Library 27
 *   Module     -> Core 643, Library 239
 *   Automator  -> Core 49, Library 8, Module 41
 *   Connector  -> Core 888, Library 399, Module 1201
 *   Addon      -> Core 46, Library 18, Module 36, Connector 54
 *   Bridge     -> Core 188, Library 63, Module 203, Connector 177, Plugin 38, Addon 8
 *
 * In-degree of the bottom of the stack, for orientation: Core/Application 982,
 * Core/Tools 784, Library/Metadata 774, Core/Exchange 134.
 */

return [

	/*
	 * Where the packages live, relative to the repository root. A package is any directory at
	 * depth 2 under this path. Discovery is NOT driven by composer.json (those are being
	 * deleted, which is why this tool exists), NOT by the root autoload (an empty psr-4 map)
	 * and NOT by a hardcoded list of 34 names.
	 *
	 * Discovery used to additionally REQUIRE a src/ subdirectory, which turned out to be an
	 * opt-out: a directory holding only config/ and tests/ -- an entirely ordinary way to
	 * start a bridge, DI wiring first -- was not scanned at all, and because the
	 * mandatory-peer check below iterates discovered packages, it was exempt from that too.
	 * A package without src/ is now a hard configuration error instead.
	 */
	'packagesRoot' => 'src/FastyBird',

	'scan' => [
		/*
		 * File extensions that are read.
		 *
		 * `neon` is the load-bearing entry and it is not optional. EVERY layer inversion
		 * anyone has ever found in this repository lives in a .neon file, not in a PHP
		 * `use` statement. A PHP-only checker -- deptrac and the PHPStan Rule API included
		 * -- reports this tree 100% clean. Drop 'neon' from this list and the checker goes
		 * permanently, silently green. The self-check floors below exist specifically to
		 * make that mistake fail loudly instead.
		 *
		 * `phpt` and `phtml` are here because a naive `*.php` glob misses four real files:
		 * two Module/Ui test files are .phpt and carry cross-package `use` lines, and two
		 * Module/Accounts templates are .phtml.
		 *
		 * `json` is here for the entity-mapping and capability files under resources/:
		 * Bridge/VieraConnectorHomeKitConnector/resources/mapping.json and its three
		 * siblings hold `"class": "\\FastyBird\\..."` values that Mapping/Builder.php reads
		 * and instantiates, so they are class-name reference sites in the same sense a NEON
		 * service definition is. They were excluded at first because .json would also drag
		 * in every composer.json; the excludeFiles list below is the one line that solves
		 * that, and reading a manifest would in any case contradict this tool's scope.
		 *
		 * Deliberately NOT scanned, each for a measured reason:
		 *   *.md      -- three per-package docs/Home.md files contain copy-pasted sibling
		 *                namespaces in prose (Plugin/WebServer, Plugin/WsServer,
		 *                Connector/Virtual). They are documentation typos, not dependency
		 *                edges, and they would force three cosmetic entries into the
		 *                exception list below for zero enforcement value.
		 *   *.ts/*.vue -- the frontend couples by `@fastybird/<name>` npm specifier, never
		 *                by PHP namespace, so this scanner would see nothing. The frontend
		 *                boundary is clean today and is a separate tool if it is ever
		 *                wanted.
		 */
		'extensions' => ['php', 'phpt', 'phtml', 'neon', 'json'],

		/*
		 * Files skipped by NAME at any depth, whatever their extension.
		 *
		 * The manifests are here because this tool checks dependency DIRECTION and never
		 * composer manifests -- reading one to harvest namespaces out of an autoload map
		 * would blur exactly the line the scope statement draws, and those 34 files are
		 * being deleted anyway.
		 *
		 * The PHPStan configs are here because a baseline is a list of ERROR MESSAGES, not
		 * wiring: tools/phpstan-baseline.neon alone is 241 KB of `FastyBird\...` patterns.
		 * It sits outside src/FastyBird today, so it is not reachable by this scan -- but
		 * splitting a 241 KB baseline per package is an entirely ordinary refactor, and on
		 * the day somebody does it the gate would go red on hundreds of non-dependencies.
		 * Excluding the names now costs nothing and removes that trap in advance.
		 */
		'excludeFiles' => [
			'composer.json',
			'composer.lock',
			'package.json',
			'package-lock.json',
			'yarn.lock',
			'phpstan.neon',
			'phpstan.neon.dist',
			'phpstan.tests.neon',
			'phpstan-baseline.neon',
			'phpstan-baseline.tests.neon',
		],

		/*
		 * Directory names pruned DURING traversal, not filtered out afterwards. Half the
		 * tree under src/FastyBird is untracked build output: four packages carry a
		 * node_modules (Module/Accounts alone holds 1428 .js files) and six carry a dist/.
		 * 10101 files on disk versus 4868 tracked. Descending into node_modules would cost
		 * more than the entire rest of the scan.
		 *
		 * THERE ARE TWO LISTS, and the split is the whole point. A single list matched by
		 * bare name at any depth is fast, but it doubles as a hiding place: a file under
		 * <pkg>/src/dist/ would be skipped without a word, which is a silent false negative
		 * in a tool whose entire job is to not have any.
		 *
		 *   pruneDirsAnywhere   names that cannot contain this repository's source at any
		 *                       depth and that legitimately nest. node_modules inside
		 *                       node_modules is normal; a FastyBird package inside one is
		 *                       not. Descending would also be ruinous: with .json now
		 *                       scanned, one node_modules is tens of thousands of files.
		 *                       A prune from this list that happens BELOW a package root is
		 *                       still reported as a note, so even here nothing is silent.
		 *   pruneDirsAtPackageRoot
		 *                       generated output that lives at the package root by
		 *                       convention. Anchoring means <pkg>/src/dist/Foo.php is
		 *                       scanned and checked like any other file, while the six real
		 *                       dist/ directories still cost nothing.
		 *
		 * `tmp`, `temp`, `coverage` and `.phpunit.cache` are in the anchored list for a
		 * specific reason. None exists in the tree today and the repository's compiled Nette
		 * containers land in the root-level var/ (see tools/phpunit-bootstrap.php) which is
		 * outside this scan entirely -- but a compiled container names every package in the
		 * graph, so one written under a package root by a local experiment would bury a real
		 * report under a wall of phantom violations.
		 */
		'pruneDirsAnywhere' => ['node_modules', 'node_modules.bak', 'vendor', '.git'],

		'pruneDirsAtPackageRoot' => ['dist', 'var', 'tmp', 'temp', 'coverage', '.phpunit.cache'],
	],

	/*
	 * Second-segment namespace roots that belong to EXTERNAL composer packages which happen
	 * to share the FastyBird vendor namespace. They are not packages in this repository and
	 * must not be treated as one.
	 *
	 * This list is not used to allow anything -- a reference whose second segment is not a
	 * discovered TYPE directory can never resolve to a package anyway. It exists so the
	 * checker can tell the difference between "external, fine" and "resolves to nothing,
	 * which probably means somebody wrote a stale namespace". Four module config/example.neon
	 * files still register `FastyBird\WebServer\DI\WebServerExtension`; the real class has
	 * been `FastyBird\Plugin\WebServer\DI\WebServerExtension` since the namespace
	 * reorganisation. Without this list that stale spelling would be dropped without a word.
	 *
	 * Those four notes are advisory and correcting them is now SAFE: all four files are
	 * listed under 'compositionRoots' below, so the corrected spelling is permitted. Before
	 * that list existed the tool reported the stale name and then failed the build on the
	 * one-word fix it had just asked for.
	 *
	 * A second segment that IS a real type but whose package segment is missing or names no
	 * package is a different matter entirely and is NOT advisory -- it exits 1. That is the
	 * branch that catches `use FastyBird\Module as Modules;`, a group `use` cut above the
	 * package segment, and `sprintf('FastyBird\Module\%s\...')`.
	 */
	'externalNamespaces' => ['JsonApi', 'SimpleAuth', 'DateTimeFactory'],

	/*
	 * -----------------------------------------------------------------------------------
	 * TYPE RULES
	 * -----------------------------------------------------------------------------------
	 *
	 * Targets are `Type/*` (any package of that type) or `Type/Name` (exactly that
	 * package). Nothing else -- no regexes, no negation, no wildcards on the type side.
	 *
	 * A package may ALWAYS reference itself, including its own Tests\ sub-namespace; that
	 * is not an edge and is never checked.
	 */
	'types' => [

		/*
		 * Library is the floor. "Code that would work in an application that is not this
		 * one" cannot, by definition, reach anything in this one. Measured: 0 out-edges of
		 * any kind. This is the only type whose target list is empty, and that emptiness is
		 * the strongest single statement in the file -- Library/Metadata has an in-degree
		 * of 774 and an out-degree of 0.
		 */
		'Library' => [],

		/*
		 * Core is the cornerstone: Core/Application (in-degree 982) is what every other
		 * package boots on. Core may reach Library and it may reach its two siblings
		 * (measured Core -> Core 15, all of them Core/Exchange -> Core/Application).
		 * Core/Application itself has ZERO out-edges -- it is the true bottom alongside
		 * Library/Metadata. Core reaching a Module is the sharpest possible inversion and
		 * there is exactly one in the tree; see the exception list.
		 */
		'Core' => ['Core/*', 'Library/*'],

		/*
		 * Plugins are infrastructure the application wires in: redis, web server, sockets,
		 * api keys. They are consumed by modules, never the other way round. Note there is
		 * deliberately no `Plugin/*` here: Plugin -> Plugin is sideways and measured zero.
		 * The Plugin/RedisDb <-> Module coupling that people expect to find lives in two
		 * BRIDGE packages, which is exactly what bridges are for.
		 */
		'Plugin' => ['Core/*', 'Library/*'],

		/*
		 * Modules are the application. They sit on Core and Library and on nothing else --
		 * no Module -> Module (measured zero, which is why there is no `Module/*` target
		 * here; Devices and Ui are joined by Bridge/DevicesModuleUiModule instead) and no
		 * Module -> Plugin (also measured zero in PHP, though see the stale
		 * FastyBird\WebServer spelling noted under 'externalNamespaces').
		 */
		'Module' => ['Core/*', 'Library/*'],

		/*
		 * "A special plugin FOR THE TRIGGERS MODULE." The singular is the rule. Both
		 * automators reach Module/Triggers and no other module, 41 refs, zero strays --
		 * so the tight target goes here on the type rather than being repeated per package.
		 * An automator reaches devices through the exchange bus using Library/Metadata
		 * documents, never by linking Module/Devices.
		 */
		'Automator' => ['Core/*', 'Library/*', 'Module/Triggers'],

		/*
		 * "A special plugin FOR THE DEVICES MODULE, talking to the real world." Same shape
		 * as Automator and the single most valuable rule in this file: all ten connectors
		 * reach Module/Devices and NOT ONE of the 1201 references touches another module.
		 * Because this is a type rule, adding an eleventh connector requires no edit here
		 * and it still cannot reach Module/Ui.
		 *
		 * Connector -> Connector is sideways and measured zero; connectors that must know
		 * about each other do it through a Bridge.
		 */
		'Connector' => ['Core/*', 'Library/*', 'Module/Devices'],

		/*
		 * "Adds a special FUNCTION." An addon is built on top of some connector and some
		 * module, but WHICH ones is the addon's identity, not its type -- so the type rule
		 * grants only the universal floor and the real peers are mandatory per-package.
		 */
		'Addon' => ['Core/*', 'Library/*'],

		/*
		 * "A contract BETWEEN extensions." A bridge is defined entirely by the pair it
		 * bridges, so the type rule grants only the universal floor. Bridge is the only
		 * type that legitimately reaches Plugin and Addon, and it does so one named package
		 * at a time. Mandatory per-package, see below.
		 */
		'Bridge' => ['Core/*', 'Library/*'],
	],

	/*
	 * Types whose packages MUST each carry an entry in 'packages' below. A missing entry is
	 * a configuration error (exit 2), not a silent fallback to the type rule.
	 *
	 * Rationale: for a Connector or a Module the type genuinely describes the dependencies.
	 * For a Bridge or an Addon it cannot -- "bridges two things" says nothing about which
	 * two -- and defaulting them to the permissive type baseline would let a brand new
	 * bridge quietly reach whatever it liked. Making it a hard error costs one line of data
	 * at the moment the package is created, which is when the author knows the answer.
	 */
	'packageRuleRequiredFor' => ['Addon', 'Bridge'],

	/*
	 * -----------------------------------------------------------------------------------
	 * PER-PACKAGE RULES
	 * -----------------------------------------------------------------------------------
	 *
	 * These ADD to the package's type rule. They never subtract. Do not "improve" this into
	 * replacement semantics: every real case in this tree is "this bridge ADDITIONALLY
	 * reaches these two packages", and Core/* + Library/* are granted by every type that
	 * has any grant at all, so there is no tightening use case today. If one ever appears,
	 * add a 'deny' key at that point -- inventing the mechanism before there is a user for
	 * it is how a config format rots.
	 *
	 * The peer sets below are the MEASURED out-edges of each package, not an aspiration.
	 *
	 * DESIGN DISAGREEMENT, RESOLVED HERE. One of the three designs proposed that the
	 * checker compute the reflexive-transitive CLOSURE of these peer sets before comparing,
	 * so that Bridge/VirtualThermostatAddonHomeKitConnector would inherit Connector/Virtual
	 * for free via Addon/VirtualThermostat. The other two proposed direct peers plus an
	 * explicit exception. THIS FILE USES DIRECT PEERS ONLY, deliberately:
	 *
	 *   1. Closure makes the list below a lie. A reader would see three peers and the tool
	 *      would be permitting five. The entire value of a per-package matrix is that the
	 *      declared set IS the permitted set.
	 *   2. Closure grants edges transitively through any future peer. Add one peer to a
	 *      bridge and you silently inherit everything that peer can reach, which is how a
	 *      per-package rule decays back into the coarse type rule it was meant to replace.
	 *   3. The single edge closure would have fixed is test-only DI wiring that the
	 *      bridge's production code never names. An exception entry with a written reason
	 *      records that fact where a human reads it; graph closure hides it.
	 */
	'packages' => [

		/*
		 * The only addon that exists. It implements a thermostat as a virtual device, so it
		 * is built on the Virtual connector (its entities extend the connector's entities:
		 * Device, Preset, Sensors, Configuration, State and Actors all import
		 * FastyBird\Connector\Virtual\Entities, 52 refs) and it registers those entities
		 * with the devices module. Measured: Connector/Virtual 54, Module/Devices 36.
		 */
		'Addon/VirtualThermostat' => ['Connector/Virtual', 'Module/Devices'],

		/*
		 * Devices module <-> UI module. This is the package that exists so that Module ->
		 * Module never has to. Measured: Module/Devices, Module/Ui.
		 */
		'Bridge/DevicesModuleUiModule' => ['Module/Devices', 'Module/Ui'],

		/*
		 * Persists devices-module state through the RedisDb plugin. Exists so that
		 * Module -> Plugin never has to. Measured: Module/Devices, Plugin/RedisDb.
		 */
		'Bridge/RedisDbPluginDevicesModule' => ['Module/Devices', 'Plugin/RedisDb'],

		/*
		 * The same contract for the triggers module. Measured: Module/Triggers,
		 * Plugin/RedisDb.
		 */
		'Bridge/RedisDbPluginTriggersModule' => ['Module/Triggers', 'Plugin/RedisDb'],

		/*
		 * Exposes Shelly devices over HomeKit. This is the package that exists so that
		 * Connector -> Connector never has to. Measured: Connector/HomeKit,
		 * Connector/Shelly, Module/Devices.
		 */
		'Bridge/ShellyConnectorHomeKitConnector' => ['Connector/HomeKit', 'Connector/Shelly', 'Module/Devices'],

		/*
		 * The same for Panasonic Viera televisions. Measured: Connector/HomeKit,
		 * Connector/Viera, Module/Devices.
		 */
		'Bridge/VieraConnectorHomeKitConnector' => ['Connector/HomeKit', 'Connector/Viera', 'Module/Devices'],

		/*
		 * Exposes the virtual thermostat addon over HomeKit. Measured PHP out-edges:
		 * Addon/VirtualThermostat 7, Connector/HomeKit 29, Module/Devices 13. Its PHP never
		 * names Connector/Virtual -- but its test container registers the Virtual
		 * connector's DI extension, because the addon's entities extend the connector's and
		 * Doctrine needs the whole inheritance chain mapped. That one .neon line is the
		 * subject of the last exception below, and it is recorded there rather than being
		 * added here, because adding it here would claim the bridge depends on the Virtual
		 * connector, which its code does not.
		 */
		'Bridge/VirtualThermostatAddonHomeKitConnector' => [
			'Addon/VirtualThermostat',
			'Connector/HomeKit',
			'Module/Devices',
		],
	],

	/*
	 * -----------------------------------------------------------------------------------
	 * COMPOSITION ROOTS -- files that wire an application rather than belong to a package
	 * -----------------------------------------------------------------------------------
	 *
	 * A package's dependency rule describes what that package's CODE may reach. It is the
	 * wrong instrument for a file whose entire job is to assemble an application out of
	 * several packages: an application legitimately registers a module and a web-server
	 * plugin and a Doctrine bridge in one container, and none of that says the module
	 * depends on the plugin.
	 *
	 * The four files below are exactly that. Each is a standalone example configuration for
	 * booting one module on its own, and each registers Contributte, Nettrine, IPub and
	 * FastyBird DI extensions side by side. Judging them by their owning module's rule is a
	 * category error, and it was a live trap before this list existed: all four still spell
	 * the web server plugin's pre-reorganisation namespace `FastyBird\WebServer\DI\...`,
	 * the checker reports that as an unresolved coordinate, and correcting the spelling --
	 * a one-word edit -- would have turned the build red for a Module -> Plugin edge that
	 * an example application is supposed to have. The tool invited a fix it then rejected.
	 *
	 * Cross-package references in these files are counted, appear in --list-edges and are
	 * reported in the summary under `composition`, so they stay visible. They are simply
	 * never violations.
	 *
	 * EXACT PATHS, NEVER A GLOB. A glob over "any config/example.neon" would be shorter and
	 * would quietly exempt every future file named that way. Four lines that each
	 * carry a reason is the point. ANTI-ROT: a listed file that stops making any
	 * cross-package reference is a build failure, exactly like a stale exception, because
	 * it is then an ordinary file of its package and the entry is licensing nothing.
	 */
	'compositionRoots' => [
		[
			'path' => 'src/FastyBird/Module/Accounts/config/example.neon',
			'reason' => 'Standalone example application config for booting the accounts module on its own. '
				. 'It registers Contributte, Nettrine, IPub, SimpleAuth, JsonApi and the web server plugin '
				. 'in one container, which is what an application does and what the module itself must '
				. 'never do. Delete this entry if the file stops being a bootable container and becomes '
				. 'module-scoped configuration.',
		],
		[
			'path' => 'src/FastyBird/Module/Devices/config/example.neon',
			'reason' => 'Standalone example application config for booting the devices module on its own; '
				. 'same composition-root argument as the accounts module. Delete this entry if the file '
				. 'stops being a bootable container and becomes module-scoped configuration.',
		],
		[
			'path' => 'src/FastyBird/Module/Triggers/config/example.neon',
			'reason' => 'Standalone example application config for booting the triggers module on its own; '
				. 'same composition-root argument as the accounts module. Delete this entry if the file '
				. 'stops being a bootable container and becomes module-scoped configuration.',
		],
		[
			'path' => 'src/FastyBird/Module/Ui/config/example.neon',
			'reason' => 'Standalone example application config for booting the UI module on its own; same '
				. 'composition-root argument as the accounts module. Delete this entry if the file stops '
				. 'being a bootable container and becomes module-scoped configuration.',
		],
	],

	/*
	 * -----------------------------------------------------------------------------------
	 * EXCEPTIONS -- the grandfather list, and it is designed to shrink
	 * -----------------------------------------------------------------------------------
	 *
	 * Keyed on (from, to, path, symbols) and pinned to an exact `sites` count. Each choice
	 * is load-bearing:
	 *
	 *   NOT line-keyed  -- a line number churns on every unrelated edit above it, so the
	 *                      list would need re-validating constantly and people would start
	 *                      blanket-updating it. File granularity is stable. One entry may
	 *                      suppress several sites in its file (Connector/Virtual covers
	 *                      both :91 and :97).
	 *   NOT pair-keyed  -- `from`+`to` alone would whitelist the pair across all ~3200
	 *                      files, so a test-harness exception would silently license the
	 *                      same inversion in production src/. Both ConnectionWrapper cases
	 *                      below would, under pair-only keying, permit a real
	 *                      Core -> Module import.
	 *   symbols + sites -- and (from, to, path) alone is not enough either, which is the
	 *                      one thing the first version of this file got wrong. An entry
	 *                      grandfathering one Doctrine ConnectionWrapper line also
	 *                      grandfathered EVERY other reference from that package to that
	 *                      package anywhere in that file, forever, and the anti-rot
	 *                      detector could not see it because the entry still matched more
	 *                      than zero sites. Adding an unrelated repository service to
	 *                      Core/Application's test container re-committed the sharpest
	 *                      inversion in the repository for free. `symbols` names the exact
	 *                      namespaces excused; `sites` pins how many places may use them,
	 *                      so a second copy of an already-excused line fails too.
	 *   exact path, no glob -- globs let scope creep in without anyone re-reading `reason`.
	 *
	 * `reason` is mandatory and must be at least 40 characters, enforced by the checker. It
	 * must name WHAT WOULD HAVE TO CHANGE for the entry to be deleted, not merely restate
	 * what the code does. "wip" and "legacy" must not be expressible.
	 *
	 * ANTI-ROT. An entry that suppresses nothing, whose pair the rules already allow, whose
	 * `sites` count no longer matches, or that lists a symbol nothing uses, is a BUILD
	 * FAILURE (exit 1), not a warning, and there is no flag to downgrade that -- the stale
	 * scan runs even under --ignore-exceptions. If an entry is stale the fix is one deleted
	 * array element. Any opt-out here would become permanent, which is the precise failure
	 * mode this list is trying to avoid.
	 *
	 * WHY THE LIST IS NOT EMPTY ON DAY ONE. Five of the six sites below are real inversions
	 * that should be fixed, and four of those are provably one-line copy-paste bugs whose
	 * correct value already exists in the same package. They are not fixed in this change
	 * for one reason: fixing them alters Nette DI wiring and Doctrine entity mapping in four
	 * test containers, and that cannot be validated without MariaDB and Redis on the test
	 * process's own loopback. Shipping the gate green with written-down, self-expiring debts
	 * is honest; shipping it red on the first commit is a broken CI job that somebody
	 * disables. Each `reason` names the fix, so deleting these is a small, separate,
	 * testable change. The last entry is the exception to that: it is not a debt at all, and
	 * its reason says so.
	 */
	'exceptions' => [
		[
			'from' => 'Core/Application',
			'to' => 'Module/Accounts',
			'path' => 'src/FastyBird/Core/Application/tests/common.neon',
			'symbols' => ['FastyBird\Module\Accounts\Tests\Tools\ConnectionWrapper'],
			'sites' => 1,
			'reason' => 'Copy-paste in the test container: `wrapperClass` points at Module/Accounts\' test '
				. 'Doctrine ConnectionWrapper. This is the sharpest inversion in the repository -- the '
				. 'bottom package of the stack naming the top layer. Core/Application already ships an '
				. 'identical src/FastyBird/Core/Application/tests/tools/ConnectionWrapper.php; point the '
				. 'line at \FastyBird\Core\Application\Tests\Tools\ConnectionWrapper, run the '
				. 'Core/Application suite, and delete this entry.',
		],
		[
			'from' => 'Plugin/ApiKey',
			'to' => 'Module/Devices',
			'path' => 'src/FastyBird/Plugin/ApiKey/tests/common.neon',
			'symbols' => ['FastyBird\Module\Devices\Tests\Tools\ConnectionWrapper'],
			'sites' => 1,
			'reason' => 'The same copy-paste as Core/Application: `wrapperClass` points at Module/Devices\' '
				. 'test ConnectionWrapper. Plugin/ApiKey already ships its own identical '
				. 'tests/tools/ConnectionWrapper.php; point the line at '
				. '\FastyBird\Plugin\ApiKey\Tests\Tools\ConnectionWrapper, run the ApiKey suite, and '
				. 'delete this entry. 23 of the 25 packages that carry a test ConnectionWrapper already '
				. 'reference their own.',
		],
		[
			'from' => 'Automator/DevicesModule',
			'to' => 'Module/Devices',
			'path' => 'src/FastyBird/Automator/DevicesModule/tests/common.neon',
			'symbols' => ['FastyBird\Module\Devices\DI\DevicesExtension'],
			'sites' => 1,
			'reason' => 'The test container registers FastyBird\Module\Devices\DI\DevicesExtension, which '
				. 'the package has no code dependency on: its 30 source files touch only Triggers, Core '
				. 'and Library, its SQL fixture inserts only into fb_triggers_module_* tables, and its '
				. 'device reference is a plain UUID column rather than a Doctrine association. The '
				. 'sibling Automator/DateTime does not register it. Delete the line, run the automator '
				. 'suite, then delete this entry. If the maintainer decides the edge is intended '
				. 'instead, move Module/Devices into the Automator/DevicesModule package rule.',
		],
		[
			'from' => 'Connector/Virtual',
			'to' => 'Addon/VirtualThermostat',
			'path' => 'src/FastyBird/Connector/Virtual/tests/common.neon',
			'symbols' => ['FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy'],
			'sites' => 2,
			'reason' => 'A genuine upward edge, and a live functional bug rather than only a layering '
				. 'smell. Lines 91 and 97 map the namespace prefix FastyBird\Addon\VirtualThermostat\\'
				. 'Tests\Fixtures\Dummy onto %appDir%/fixtures/dummy, but the four classes actually at '
				. 'that path declare FastyBird\Connector\Virtual\Tests\Fixtures\Dummy -- so Doctrine '
				. 'maps the ADDON\'s dummy entities and the connector\'s own are never registered. Line '
				. '45 of the same file already spells the correct prefix. Fix both lines to the '
				. 'connector\'s own namespace, expect the suite\'s behaviour to change for the better, '
				. 'then delete this entry. This must never be blessed into the rules: a connector may '
				. 'not depend on an addon.',
		],
		[
			'from' => 'Bridge/VirtualThermostatAddonHomeKitConnector',
			'to' => 'Connector/Virtual',
			'path' => 'src/FastyBird/Bridge/VirtualThermostatAddonHomeKitConnector/tests/common.neon',
			'symbols' => ['FastyBird\Connector\Virtual\DI\VirtualExtension'],
			'sites' => 1,
			'reason' => 'NOT A DEBT, and unlike the four entries above it there is nothing here to pay off '
				. '-- do not go looking for the bug. Test DI wiring registering the transitive closure '
				. 'of an allowed peer: the bridge depends on Addon/VirtualThermostat, whose entities '
				. 'extend Connector/Virtual\'s, so the test container MUST register VirtualExtension for '
				. 'Doctrine to resolve the inheritance chain. The line is correct, necessary and '
				. 'permanent for as long as that inheritance holds. It is recorded here rather than as a '
				. 'fourth peer in the package rule because the package rule states what the CODE depends '
				. 'on, and the bridge\'s PHP never names the connector. Delete this entry if the addon '
				. 'stops extending the connector\'s entities; promote it to a peer if the bridge\'s PHP '
				. 'ever does name the connector.',
		],
	],

	/*
	 * -----------------------------------------------------------------------------------
	 * SELF-CHECK FLOORS -- the guard against a green result that means nothing
	 * -----------------------------------------------------------------------------------
	 *
	 * A checker that scans nothing exits 0. That is the single most likely way this tool
	 * fails, and it fails silently. Every number below is a hard floor: if the scan comes
	 * in under it, the checker exits 2 and REFUSES to report success at all -- it does not
	 * say "OK but".
	 *
	 * EVERY KEY HERE IS MANDATORY and the checker verifies that before it scans anything. A
	 * missing or misspelt key reads as null, `$value < null` is always false, and the floor
	 * would be silently void while the summary still printed "self-check PASS" -- which is
	 * precisely the false green these floors exist to prevent. A key the checker does not
	 * read is rejected too, because it enforces nothing and reads as though it does.
	 *
	 * The measured values from the tree this landed on are in the comments. The floors sit
	 * roughly 25-35% below, which is far enough to survive normal churn and nowhere near
	 * zero. There is deliberately NO upper bound: growth is expected and a ceiling would
	 * just be a future false red.
	 *
	 * The last four are the ones that matter most, and they are not decoration:
	 *
	 *   minNeonFiles / minNeonReferences / minNeonCrossReferences
	 *       ALL SIX known violations in this repository are .neon-only. Remove 'neon' from
	 *       scan.extensions, or let a prune rule start matching a tests/ directory, and the
	 *       tool would be permanently and silently green while the only inversions in the
	 *       repository sat untouched. These three floors are what turns that into exit 2.
	 *   minJsonFiles
	 *       The same argument one notch weaker. The four resources/*.json entity-mapping
	 *       files name classes that get instantiated at runtime, and .json is the easiest
	 *       entry to drop from scan.extensions because 900 of the 904 files it pulls in are
	 *       fixtures with nothing in them. This floor makes dropping it exit 2.
	 *   minTestsReferences
	 *       Narrowing the scan to <pkg>/src is a tempting "optimisation" that would destroy
	 *       the tool's entire value, since nothing in any src/ has ever violated the rules.
	 *   minFilesPerPackage
	 *       Catches a single package silently dropping out of the graph -- renamed
	 *       directory, missing src/ -- which would otherwise make all of its references
	 *       unattributable. The smallest package today holds 12 in-scope files, so a floor
	 *       of 1 is a real floor and not a formality.
	 */
	'selfCheck' => [
		'minPackages' => 25, // measured 34
		'minFiles' => 3000, // measured 4142
		'minNeonFiles' => 40, // measured 65
		'minJsonFiles' => 600, // measured 904
		'minReferences' => 9000, // measured 13762 (self-references included)
		'minCrossReferences' => 3000, // measured 4534
		'minPackagePairs' => 100, // measured 150 distinct ordered pairs
		'minNeonReferences' => 100, // measured 161
		'minNeonCrossReferences' => 60, // measured 97
		'minTestsReferences' => 1200, // measured 1910 from files under <pkg>/tests/
		'minSrcReferences' => 8000, // measured 11799 from files under <pkg>/src/
		'minFilesPerPackage' => 1, // measured minimum 12, Bridge/RedisDbPluginTriggersModule
	],
];
