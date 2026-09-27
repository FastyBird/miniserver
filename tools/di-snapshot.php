<?php declare(strict_types = 1);

/**
 * DI snapshot: records the compiled Nette DI graph of every container this repository builds,
 * and diffs two such recordings.
 *
 * A green test suite proves that the services it happens to fetch exist. It says nothing about
 * the ones it never fetches, about setup order, about a tag nobody reads any more, or about an
 * argument that quietly changed. This tool records all of it, for every definition, so that a
 * refactoring of the DI wiring (Epic #459) can be proven not to have changed the graph.
 *
 * Usage (always in the application image, never on the host):
 *
 *   php tools/di-snapshot.php --list
 *       Enumerate the containers and print them, compiling nothing.
 *
 *   php tools/di-snapshot.php <outDir> [--jobs <n>] [--only <substring>]
 *       Compile every container, each in its own PHP subprocess, and write one canonical JSON
 *       file per container plus <outDir>/index.json (container id => file, sha256). <outDir>
 *       must not exist yet or must be empty. Put it under var/tools/di-snapshot/<label>/, which
 *       var/tools/.gitignore already ignores; snapshots are never committed.
 *
 *   php tools/di-snapshot.php --diff <baseDir> <headDir> [--map <file>]... [--allow-moves <file>]...
 *       Compare two snapshot directories. Exit 0 if they are identical, 1 on any difference,
 *       2 on a usage or input error. Differences are printed grouped by container and then by
 *       service. Every --map is applied to the BASE side, in the order given, before comparing.
 *       --allow-moves names definitions that may change position in the raw global order (see
 *       below); every other difference, every observable order included, still fails.
 *
 *   docker run --rm -v "$PWD":/app -w /app -e XDEBUG_MODE=off -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC \
 *       <application-image> php tools/di-snapshot.php var/tools/di-snapshot/head
 *
 * The container set is enumerated from the tree, not hard-coded:
 *
 *   - "production": Boot\Bootstrap::boot() with FB_APP_DIR at the repository root, exactly as
 *     bin/fb-console.php and public/index.php boot it, so config/common.neon and every
 *     extension it registers are compiled. FB_CONFIG_DIR points at an empty directory, so a
 *     developer's git-ignored config/local.neon never leaks into the recording. Like the
 *     application-scope test tier, it points contributte/vite at
 *     tests/cases/application/fixtures/vite-manifest.json, because the real manifest is a
 *     frontend build artefact that a checkout does not have. This is the census's
 *     prod:entity-mapping-test.
 *   - "production:dev": the same with APP_ENV=dev (debug mode compiles the stdout and console
 *     log handlers and the ConsoleHandler subscriber).
 *   - "production:sentry": the same with a fixed dummy FB_APP_PARAMETER__SENTRY_DSN (the only
 *     way the Sentry definitions and the Sentry pushHandler compile).
 *   - "test/<Type>/<Name>": one per package, replicated from the package's own
 *     tests/cases/unit/BaseTestCase.php and DbTestCase.php (the tests' Bootstrap::boot(), the
 *     package's tests/common.neon, its time zone and its <Extension>::register() call). If the
 *     two classes of one package ever build different containers, both are recorded.
 *   - "test/<Type>/<Name>+<overlay>": one per per-test NEON overlay, found as
 *     registerNeonConfigurationFile(__DIR__ . '...') or createContainer(__DIR__ . '...') in the
 *     package's *Test.php files, layered on that package's base container.
 *
 * A container whose compilation fails is not a failed run: it is recorded as
 * {"compiled": false, "error": {...}} and compared like any other recording, so "still fails
 * the same way" is a result. (On 2026-09-27 that is test/Plugin/CouchDb: no CouchDb test builds
 * its container, and it does not compile.) A crash of the recording itself does fail the run.
 *
 * The enumeration refuses to guess. A TestCase that builds its container in a way this file
 * does not recognise, or an overlay registered through anything but a literal path, stops the
 * run with an error naming the file, rather than silently leaving a container out.
 *
 * Every subprocess gets a controlled environment: TZ=UTC, PHP_DATE_TIMEZONE=UTC,
 * XDEBUG_MODE=off, no APP_ENV (except production:dev), no FB_APP_PARAMETER_* except a fixed
 * FB_APP_PARAMETER__SECURITY_SIGNATURE and production:sentry's fixed Sentry DSN (without one the production container registers none of
 * the Security services, and a random one would differ between runs). Nothing connects to a
 * database: the container is compiled (Configurator::loadContainer()), never instantiated.
 *
 * What is recorded, per container:
 *
 *   - "order": every definition name in the order it was added to the ContainerBuilder. It is
 *     kept apart from the definitions so that a diff can report "order changed" separately
 *     from "definition changed". Order is behaviour here: nettrine's EventPass and the event
 *     dispatcher register subscribers in definition order.
 *   - "aliases", and "extensions": every registered compiler extension, in the compiler's order.
 *   - "services", keyed by name and sorted by it, each with: kind (service, factory, accessor,
 *     locator, imported), resolved type, factory entity, arguments, setup statements IN ORDER,
 *     tags with their values, autowiring, exported, lazy, and implement for generated
 *     factories/accessors/locators (with the factory's result definition).
 *   - From the generated container class: "wiring" (per type, the service lists behind
 *     findByType()/getByType(), in compiled order), "tags" (per tag, the [service, value] list
 *     behind findByTag(), in compiled order) and "initialize" (the initialize() body, paths
 *     normalised). These are the runtime-observable orders.
 *
 * Every field is a hard diff criterion, with one exception: the raw global "order". It is strict
 * by default, but a refactoring may legitimately move a definition that sits in no observable
 * collection (census #553, section 5.5). --allow-moves <file> lists such names, one per line,
 * blank lines and # comments ignored, named as the head names them. The order is then compared
 * with those names taken out; their moves are printed as information, and a move of any other
 * name still fails. Because the setups, $wiring and $tags are compared regardless, an allowed
 * move that changes an observable order is still reported.
 *
 * A reference is recorded as {"@": "<service name>"}, never as the object it points at, so a
 * rename is visible and mappable. Absolute paths are normalised to %root%, %tempDir%,
 * %FB_TEMP_DIR%, %logsDir% and %configDir%, so two checkouts compare equal.
 *
 * The recording is taken in afterCompile() of an extension added last through
 * Configurator::$onCompile, after ContainerBuilder::complete(), so every definition is resolved.
 *
 * Map file format (--map), used by tools/di-maps/06-services.php and 07-tags.php:
 *
 *   <?php
 *   return [
 *       'services' => ['old.service.name' => 'new.service.name', ...],
 *       'tags' => ['old.tag' => 'new.tag', ...],
 *   ];
 *
 * Both keys are optional. A service rename rewrites the definition's key, its place in
 * "order", aliases (both sides), every {"@": ...} reference, every string argument that is
 * exactly the old name (nettrine's EventPass registers subscribers by service name as a plain
 * string), the names in "wiring" and "tags", and the quoted name in "initialize". A tag rename
 * rewrites tag keys (definitions and "tags"), a locator's "tagged", and every string argument
 * that is exactly the old tag. Renaming two old names to one new name is an error.
 */

// phpcs:ignoreFile

const FB_DI_SNAPSHOT_SIGNATURE = 'di-snapshot-fixed-signature';

const FB_DI_SNAPSHOT_SENTRY_DSN = 'https://di-snapshot@sentry.invalid/1';

const FB_DI_SNAPSHOT_EXTENSION = 'fbDiSnapshot';

const FB_DI_SNAPSHOT_FORMAT = 2;

/**
 * The only methods a package TestCase may call on its Configurator. Anything else changes the
 * container in a way this tool does not replicate, so it stops the enumeration.
 */
const FB_DI_SNAPSHOT_KNOWN_CONFIGURATOR_CALLS = [
	'setTempDirectory',
	'addStaticParameters',
	'addConfig',
	'setTimeZone',
	'createContainer',
];

exit(fbDiSnapshotMain($argv));

/**
 * @param list<string> $argv
 */
function fbDiSnapshotMain(array $argv): int
{
	$args = array_slice($argv, 1);

	try {
		if (($args[0] ?? null) === '--worker') {
			return fbDiSnapshotWorker($args[1] ?? '');
		}

		if (($args[0] ?? null) === '--list') {
			foreach (fbDiSnapshotEnumerate(fbDiSnapshotRoot()) as $spec) {
				fwrite(STDOUT, $spec['id'] . PHP_EOL);
			}

			return 0;
		}

		if (($args[0] ?? null) === '--diff') {
			return fbDiSnapshotDiffCommand(array_slice($args, 1));
		}

		if (($args[0] ?? '') === '' || str_starts_with($args[0], '--')) {
			fwrite(STDERR, fbDiSnapshotUsage());

			return 2;
		}

		return fbDiSnapshotSnapshotCommand($args);
	} catch (FbDiSnapshotUsageError $ex) {
		fwrite(STDERR, 'di-snapshot: ' . $ex->getMessage() . PHP_EOL . PHP_EOL . fbDiSnapshotUsage());

		return 2;
	}
}

final class FbDiSnapshotUsageError extends RuntimeException
{

}

final class FbDiSnapshotDumpError extends RuntimeException
{

}

function fbDiSnapshotUsage(): string
{
	return <<<'TXT'
		Usage:
		  php tools/di-snapshot.php --list
		  php tools/di-snapshot.php <outDir> [--jobs <n>] [--only <substring>]
		  php tools/di-snapshot.php --diff <baseDir> <headDir> [--map <file>]...

		See the header of tools/di-snapshot.php.

		TXT;
}

function fbDiSnapshotRoot(): string
{
	$root = realpath(__DIR__ . '/..');

	if ($root === false) {
		throw new FbDiSnapshotUsageError('Cannot resolve the repository root');
	}

	return $root;
}

/* ------------------------------------------------------------------------------------------ */
/* Enumeration                                                                                */
/* ------------------------------------------------------------------------------------------ */

/**
 * @return list<array{id: string, kind: string, root: string, appDir?: string, configs?: list<string>, registers?: list<string>, timeZone?: string|null}>
 */
function fbDiSnapshotEnumerate(string $root): array
{
	// Production, as bin/fb-console.php and EntityMappingTest compile it, plus the two
	// environment variants that compile Core definitions the plain one does not: debug mode
	// (APP_ENV=dev: the stdout and console log handlers and the ConsoleHandler subscriber) and a
	// Sentry DSN (the 4 Sentry definitions and the Sentry pushHandler). Fixed values, so the
	// recordings are deterministic.
	$containers = [
		['id' => 'production', 'kind' => 'production', 'root' => $root, 'env' => []],
		['id' => 'production:dev', 'kind' => 'production', 'root' => $root, 'env' => ['APP_ENV' => 'dev']],
		[
			'id' => 'production:sentry',
			'kind' => 'production',
			'root' => $root,
			'env' => ['FB_APP_PARAMETER__SENTRY_DSN' => FB_DI_SNAPSHOT_SENTRY_DSN],
		],
	];

	$packageDirs = glob($root . '/src/FastyBird/*/*', GLOB_ONLYDIR);
	assert(is_array($packageDirs));
	sort($packageDirs, SORT_STRING);

	foreach ($packageDirs as $packageDir) {
		$package = substr($packageDir, strlen($root . '/src/FastyBird/'));

		/** @var array<string, array{id: string, kind: string, root: string, appDir: string, configs: list<string>, registers: list<string>, timeZone: string|null}> $bases */
		$bases = [];

		foreach (['BaseTestCase', 'DbTestCase'] as $class) {
			$file = $packageDir . '/tests/cases/unit/' . $class . '.php';

			if (is_file($file)) {
				$bases[$class] = fbDiSnapshotParseTestCase($root, $package, $file);
			}
		}

		if ($bases === []) {
			continue;
		}

		// One container per distinct recipe. BaseTestCase and DbTestCase build the same one in
		// every package today; if that ever stops being true, both are recorded.
		$distinct = [];

		foreach ($bases as $class => $base) {
			$recipe = json_encode([$base['appDir'], $base['configs'], $base['registers'], $base['timeZone']]);
			$distinct[$recipe][] = $class;
		}

		$byClass = [];

		foreach ($distinct as $classes) {
			$base = $bases[$classes[0]];
			$base['id'] = 'test/' . $package . (count($distinct) > 1 ? '#' . implode(',', $classes) : '');

			foreach ($classes as $class) {
				$byClass[$class] = $base;
			}

			$containers[] = $base;
		}

		foreach (fbDiSnapshotFindOverlays($packageDir) as [$testFile, $overlay]) {
			$parent = fbDiSnapshotParentTestCase($testFile, array_keys($byClass));
			$base = $byClass[$parent];
			$overlayPath = fbDiSnapshotResolveLiteralPath(dirname($testFile), $overlay, $testFile);

			$base['id'] .= '+' . substr($overlayPath, strlen($packageDir . '/tests/'));
			$base['configs'][] = $overlayPath;

			$containers[$base['id']] = $base;
		}
	}

	$unique = [];

	foreach ($containers as $container) {
		$unique[$container['id']] = $container;
	}

	return array_values($unique);
}

/**
 * @return array{id: string, kind: string, root: string, appDir: string, configs: list<string>, registers: list<string>, timeZone: string|null}
 */
function fbDiSnapshotParseTestCase(string $root, string $package, string $file): array
{
	$source = file_get_contents($file);
	assert(is_string($source));

	$fail = static function (string $why) use ($file): never {
		throw new FbDiSnapshotUsageError(sprintf(
			'%s: %s. The container this TestCase builds cannot be replicated; teach tools/di-snapshot.php its shape.',
			$file,
			$why,
		));
	};

	if (!str_contains($source, 'Boot\Bootstrap::boot()')) {
		$fail('no Boot\Bootstrap::boot() call');
	}

	if (!str_contains($source, "\$rootDir = __DIR__ . '/../..';")) {
		$fail('$rootDir is not __DIR__ . \'/../..\'');
	}

	if (!str_contains($source, "addStaticParameters(['appDir' => \$rootDir, 'wwwDir' => \$rootDir, 'vendorDir' => \$vendorDir])")) {
		$fail('the static parameters are not exactly appDir/wwwDir/vendorDir');
	}

	preg_match_all('/\$config->(\w+)\(/', $source, $calls);

	foreach (array_unique($calls[1]) as $call) {
		if (!in_array($call, FB_DI_SNAPSHOT_KNOWN_CONFIGURATOR_CALLS, true)) {
			$fail(sprintf('unrecognised Configurator call $config->%s()', $call));
		}
	}

	preg_match_all('/\$config->addConfig\(([^;]*)\);/', $source, $addConfigs);

	$configs = [];

	foreach ($addConfigs[1] as $argument) {
		if (preg_match("/^__DIR__ \\. '([^']+)'$/", $argument, $literal) === 1) {
			$configs[] = fbDiSnapshotResolveLiteralPath(dirname($file), $literal[1], $file);
		} elseif (!in_array($argument, ['$neonFile', '$additionalConfig'], true)) {
			$fail(sprintf('unrecognised addConfig(%s)', $argument));
		}
	}

	if (count($configs) !== 1) {
		$fail(sprintf('expected exactly one literal base configuration, found %d', count($configs)));
	}

	preg_match_all('/([\\\\\w]+)::register\(\$config\);/', $source, $registerCalls);

	$registers = [];

	foreach ($registerCalls[1] as $reference) {
		$registers[] = fbDiSnapshotResolveClass($source, $reference);
	}

	preg_match_all("/->setTimeZone\\('([^']+)'\\)/", $source, $timeZones);

	if (count(array_unique($timeZones[1])) > 1) {
		$fail('more than one time zone');
	}

	return [
		'id' => 'test/' . $package,
		'kind' => 'test',
		'root' => $root,
		// Exactly the string the TestCase builds, not its realpath: it becomes %appDir%.
		'appDir' => dirname($file) . '/../..',
		'configs' => $configs,
		'registers' => $registers,
		'timeZone' => $timeZones[1][0] ?? null,
	];
}

function fbDiSnapshotResolveLiteralPath(string $dir, string $relative, string $origin): string
{
	$path = realpath($dir . $relative);

	if ($path === false) {
		throw new FbDiSnapshotUsageError(sprintf('%s: configuration "%s" does not exist', $origin, $relative));
	}

	return $path;
}

function fbDiSnapshotResolveClass(string $source, string $reference): string
{
	if (str_starts_with($reference, '\\')) {
		return ltrim($reference, '\\');
	}

	$segments = explode('\\', $reference);
	$first = array_shift($segments);

	if (preg_match_all('/^use\s+([\\\\\w]+)(?:\s+as\s+(\w+))?;/m', $source, $uses, PREG_SET_ORDER) > 0) {
		foreach ($uses as $use) {
			$alias = $use[2] ?? '';
			$alias = $alias !== '' ? $alias : substr((string) strrchr('\\' . $use[1], '\\'), 1);

			if ($alias === $first) {
				return implode('\\', array_merge([$use[1]], $segments));
			}
		}
	}

	if (preg_match('/^namespace\s+([\\\\\w]+);/m', $source, $namespace) === 1) {
		return $namespace[1] . '\\' . $reference;
	}

	return $reference;
}

/**
 * @return list<array{string, string}> [test file, literal overlay path relative to it]
 */
function fbDiSnapshotFindOverlays(string $packageDir): array
{
	$casesDir = $packageDir . '/tests/cases';

	if (!is_dir($casesDir)) {
		return [];
	}

	$files = [];

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($casesDir, FilesystemIterator::SKIP_DOTS),
	);

	foreach ($iterator as $file) {
		assert($file instanceof SplFileInfo);

		if (!$file->isLink() && str_ends_with($file->getFilename(), 'Test.php')) {
			$files[] = $file->getPathname();
		}
	}

	sort($files, SORT_STRING);

	$overlays = [];

	foreach ($files as $file) {
		$source = file_get_contents($file);
		assert(is_string($source));

		if (str_contains($source, '->addConfig(')) {
			throw new FbDiSnapshotUsageError(sprintf(
				'%s builds a container through addConfig() directly; teach tools/di-snapshot.php about it',
				$file,
			));
		}

		preg_match_all('/(?:registerNeonConfigurationFile|createContainer)\(([^)]*)\)/', $source, $calls);

		foreach ($calls[1] as $argument) {
			$argument = trim($argument);

			if ($argument === '') {
				continue;
			}

			if (preg_match("/^__DIR__ \\. '([^']+)'$/", $argument, $literal) !== 1) {
				throw new FbDiSnapshotUsageError(sprintf(
					'%s registers a configuration overlay through a non-literal path (%s); teach tools/di-snapshot.php about it',
					$file,
					$argument,
				));
			}

			$overlays[] = [$file, $literal[1]];
		}
	}

	return $overlays;
}

/**
 * @param list<string> $available
 */
function fbDiSnapshotParentTestCase(string $testFile, array $available): string
{
	$source = file_get_contents($testFile);
	assert(is_string($source));

	foreach ($available as $class) {
		if (preg_match('/extends\s+[\\\\\w]*\b' . $class . '\b/', $source) === 1) {
			return $class;
		}
	}

	throw new FbDiSnapshotUsageError(sprintf(
		'%s registers a configuration overlay but extends none of %s',
		$testFile,
		implode(', ', $available),
	));
}

/* ------------------------------------------------------------------------------------------ */
/* Snapshot                                                                                   */
/* ------------------------------------------------------------------------------------------ */

/**
 * @param list<string> $args
 */
function fbDiSnapshotSnapshotCommand(array $args): int
{
	$outDir = array_shift($args);
	assert(is_string($outDir));
	$jobs = max(1, (int) trim((string) @shell_exec('nproc 2>/dev/null')) ?: 4);
	$only = null;

	while ($args !== []) {
		$option = array_shift($args);

		if ($option === '--jobs' && $args !== []) {
			$jobs = max(1, (int) array_shift($args));
		} elseif ($option === '--only' && $args !== []) {
			$only = (string) array_shift($args);
		} else {
			throw new FbDiSnapshotUsageError(sprintf('Unknown option "%s"', $option));
		}
	}

	$root = fbDiSnapshotRoot();

	if (is_dir($outDir) && (scandir($outDir) ?: []) !== ['.', '..']) {
		throw new FbDiSnapshotUsageError(sprintf('Output directory "%s" is not empty', $outDir));
	}

	if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
		throw new FbDiSnapshotUsageError(sprintf('Cannot create output directory "%s"', $outDir));
	}

	$outDir = (string) realpath($outDir);
	$workDir = $outDir . '/.work';

	$containers = fbDiSnapshotEnumerate($root);

	if ($only !== null) {
		$containers = array_values(array_filter(
			$containers,
			static fn (array $container): bool => str_contains($container['id'], $only),
		));
	}

	$kinds = array_count_values(array_map(
		static fn (array $container): string => $container['kind'] === 'production'
			? 'production'
			: (str_contains($container['id'], '+') ? 'overlay' : 'base'),
		$containers,
	));

	fwrite(STDOUT, sprintf(
		"Compiling %d containers (production %d, base test %d, per-test overlay %d) with %d jobs\n",
		count($containers),
		$kinds['production'] ?? 0,
		$kinds['base'] ?? 0,
		$kinds['overlay'] ?? 0,
		$jobs,
	));

	$env = fbDiSnapshotChildEnvironment();
	$queue = $containers;
	$running = [];
	$failed = [];
	$notCompiling = [];
	$index = [];

	while ($queue !== [] || $running !== []) {
		while ($queue !== [] && count($running) < $jobs) {
			$container = array_shift($queue);
			$slug = fbDiSnapshotSlug($container['id']);
			$dir = $workDir . '/' . $slug;
			@mkdir($dir . '/temp', 0777, true);
			@mkdir($dir . '/logs', 0777, true);
			@mkdir($dir . '/config', 0777, true);

			$container['output'] = $outDir . '/' . $slug . '.json';
			$container['workDir'] = $dir;
			file_put_contents($dir . '/spec.json', json_encode($container, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			file_put_contents($dir . '/worker.log', '');

			$process = proc_open(
				[PHP_BINARY, __FILE__, '--worker', $dir . '/spec.json'],
				[0 => ['file', '/dev/null', 'r'], 1 => ['file', $dir . '/worker.log', 'a'], 2 => ['file', $dir . '/worker.log', 'a']],
				$pipes,
				$root,
				array_merge($env, $container['env'] ?? []),
			);

			if ($process === false) {
				throw new RuntimeException('Cannot start a worker for ' . $container['id']);
			}

			$running[] = [$process, $container];
		}

		foreach ($running as $key => [$process, $container]) {
			$status = proc_get_status($process);

			if ($status['running']) {
				continue;
			}

			proc_close($process);
			unset($running[$key]);

			$ok = $status['exitcode'] === 0 && is_file($container['output']);
			$compiled = $ok && (fbDiSnapshotLoadSnapshot($container['output'])['compiled'] ?? false) === true;

			fwrite(STDOUT, sprintf(
				"  %s %s\n",
				$ok ? ($compiled ? 'ok        ' : 'NO-COMPILE') : 'FAILED    ',
				$container['id'],
			));

			if (!$ok) {
				$failed[] = $container;

				continue;
			}

			if (!$compiled) {
				$notCompiling[] = $container['id'];
			}

			$index[$container['id']] = [
				'file' => basename($container['output']),
				'compiled' => $compiled,
				'sha256' => hash_file('sha256', $container['output']),
			];
		}

		usleep(100_000);
	}

	foreach ($failed as $container) {
		$log = (string) @file_get_contents($container['workDir'] . '/worker.log');
		fwrite(STDERR, sprintf("\n== %s failed; last lines of its log:\n%s\n", $container['id'], implode(
			"\n",
			array_slice(explode("\n", trim($log)), -30),
		)));
	}

	ksort($index, SORT_STRING);

	file_put_contents($outDir . '/index.json', json_encode([
		'format' => FB_DI_SNAPSHOT_FORMAT,
		'containers' => $index,
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

	if ($failed !== []) {
		fwrite(STDERR, sprintf("\n%d of %d containers failed to compile; work files kept in %s\n", count($failed), count($containers), $workDir));

		return 1;
	}

	fbDiSnapshotRemoveTree($workDir);

	fwrite(STDOUT, sprintf("Wrote %d snapshots to %s\n", count($index), $outDir));

	if ($notCompiling !== []) {
		// Not a failure of this run: the error is the recording, and --diff compares it
		fwrite(STDOUT, sprintf(
			"%d container(s) do not compile and were recorded with their error: %s\n",
			count($notCompiling),
			implode(', ', $notCompiling),
		));
	}

	return 0;
}

/**
 * @return array<string, string>
 */
function fbDiSnapshotChildEnvironment(): array
{
	$env = [
		'TZ' => 'UTC',
		'PHP_DATE_TIMEZONE' => 'UTC',
		'XDEBUG_MODE' => 'off',
		'FB_APP_PARAMETER__SECURITY_SIGNATURE' => FB_DI_SNAPSHOT_SIGNATURE,
	];

	// Only what PHP itself needs to start the way the image starts it. Everything that the
	// application reads (APP_ENV, FB_*) is deliberately left out.
	foreach (['PATH', 'HOME', 'PHP_INI_SCAN_DIR', 'PHP_INI_DIR'] as $name) {
		$value = getenv($name);

		if (is_string($value)) {
			$env[$name] = $value;
		}
	}

	return $env;
}

function fbDiSnapshotSlug(string $id): string
{
	return (string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $id);
}

function fbDiSnapshotRemoveTree(string $dir): void
{
	if (!is_dir($dir)) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST,
	);

	foreach ($iterator as $file) {
		assert($file instanceof SplFileInfo);

		$file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}

	rmdir($dir);
}

/* ------------------------------------------------------------------------------------------ */
/* Worker: compiles one container in its own process                                           */
/* ------------------------------------------------------------------------------------------ */

function fbDiSnapshotWorker(string $specFile): int
{
	$spec = json_decode((string) file_get_contents($specFile), true, flags: JSON_THROW_ON_ERROR);
	assert(is_array($spec));

	// Vendor deprecations on PHP 8.4 are noise here, exactly as in tools/php.d/tests.ini
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
	ini_set('display_errors', 'stderr');

	$root = $spec['root'];
	$workDir = $spec['workDir'];
	$tempDir = $workDir . '/temp';
	$logsDir = $workDir . '/logs';

	require $root . '/vendor/autoload.php';

	if ($spec['kind'] === 'production') {
		// As bin/fb-console.php: Bootstrap derives FB_APP_DIR from the vendor directory. The
		// configuration directory is an empty one, so a local.neon never leaks in.
		define('FB_APP_DIR', $root);
		define('FB_PUBLIC_DIR', $root . '/public');
		define('FB_CONFIG_DIR', $workDir . '/config');
		define('FB_TEMP_DIR', $tempDir);
		define('FB_LOGS_DIR', $logsDir);

		$configurator = FastyBird\Core\Boot\Bootstrap::boot();

		// contributte/vite refuses to compile without a Vite manifest, which is a frontend
		// build artefact and not tracked. Same override, same fixture, as the application-scope
		// test tier (tests/cases/application/bootstrap-production-scope.php); it changes only
		// the Vite service's manifest path, identically on both sides of any diff.
		$configurator->addConfig([
			'contributteVite' => ['manifestFile' => $root . '/tests/cases/application/fixtures/vite-manifest.json'],
		]);

		$containerTempDir = $tempDir;
		$configDir = $workDir . '/config';
	} else {
		// As tools/phpunit-bootstrap.php, including the test runner Bootstrap::boot() detects
		define('PHPUNIT_COMPOSER_INSTALL', $root . '/vendor/autoload.php');
		define('FB_APP_DIR', realpath($root . '/tests'));
		define('FB_CONFIG_DIR', $root . '/tools/../tests/config');
		define('FB_VENDOR_DIR', realpath($root . '/vendor'));
		define('FB_TEMP_DIR', $tempDir);
		define('FB_LOGS_DIR', $logsDir);

		DG\BypassFinals::enable();

		$configurator = FastyBird\Core\Boot\Bootstrap::boot();

		// The TestCase uses FB_TEMP_DIR . '/' . md5($rootDir); only the value differs, and it
		// is normalised to %tempDir% anyway.
		$containerTempDir = $tempDir . '/container';
		$configurator->setTempDirectory($containerTempDir);
		$configurator->addStaticParameters([
			'appDir' => $spec['appDir'],
			'wwwDir' => $spec['appDir'],
			'vendorDir' => FB_VENDOR_DIR,
		]);

		foreach ($spec['configs'] as $config) {
			$configurator->addConfig($config);
		}

		if ($spec['timeZone'] !== null) {
			$configurator->setTimeZone($spec['timeZone']);
		}

		foreach ($spec['registers'] as $class) {
			$class::register($configurator);
		}

		$configDir = FB_CONFIG_DIR;
	}

	$replacements = [
		$containerTempDir => '%tempDir%',
		$tempDir => '%FB_TEMP_DIR%',
		$logsDir => '%logsDir%',
		$configDir => '%configDir%',
		$root => '%root%',
	];
	uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

	$output = $spec['output'];
	$id = $spec['id'];

	// Added last, so every other onCompile callback (the package's register()) has run first.
	// Its afterCompile() runs after ContainerBuilder::complete(), so every definition it sees
	// is resolved.
	$recorder = fbDiSnapshotDumpExtension($id, $replacements);

	$configurator->onCompile[] = static function (
		Nette\Bootstrap\Configurator $configurator,
		Nette\DI\Compiler $compiler,
	) use ($recorder): void {
		$compiler->addExtension(FB_DI_SNAPSHOT_EXTENSION, $recorder);
	};

	try {
		$containerClass = $configurator->loadContainer();
	} catch (FbDiSnapshotDumpError $ex) {
		throw $ex;
	} catch (Throwable $ex) {
		// A container that does not compile is recorded as that, with its normalised error, so
		// that "still fails the same way" is a comparable result and not a hole in the set.
		// Only a failure inside the compiler lands here; a crash of the recording itself is
		// rethrown above and fails the run.
		$message = strtr(strtok($ex->getMessage(), "\n") ?: '', $replacements);

		file_put_contents($output, json_encode([
			'format' => FB_DI_SNAPSHOT_FORMAT,
			'container' => $id,
			'compiled' => false,
			'error' => ['class' => $ex::class, 'message' => $message],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");

		fwrite(STDERR, 'Container does not compile: ' . $ex::class . ': ' . $message . "\n");

		return 0;
	}

	$snapshot = $recorder->snapshot;

	if ($snapshot === null) {
		fwrite(STDERR, "The container was not compiled (loaded from a cache?), nothing was recorded\n");

		return 1;
	}

	// What the generated class holds, as the runtime reads it: $wiring backs findByType() and
	// getByType(), $tags backs findByTag(), each list in its compiled order. initialize() runs
	// every extension's initialization code. Read from the generated class, not the builder,
	// because they are only final once every extension has contributed to the class.
	$reflection = new ReflectionClass($containerClass);
	$defaults = $reflection->getDefaultProperties();

	$wiring = $defaults['wiring'] ?? [];
	assert(is_array($wiring));
	ksort($wiring, SORT_STRING);
	$snapshot['wiring'] = $wiring;

	$tags = [];

	foreach (is_array($defaults['tags'] ?? null) ? $defaults['tags'] : [] as $tag => $services) {
		$pairs = [];

		foreach ($services as $service => $value) {
			$pairs[] = [(string) $service, $recorder->recordValue([$value])[0]];
		}

		$tags[(string) $tag] = $pairs;
	}

	ksort($tags, SORT_STRING);
	$snapshot['tags'] = $tags;

	$initialize = [];

	if ($reflection->hasMethod('initialize') && $reflection->getMethod('initialize')->getDeclaringClass()->getName() === $reflection->getName()) {
		$method = $reflection->getMethod('initialize');
		$lines = file((string) $method->getFileName(), FILE_IGNORE_NEW_LINES);
		assert(is_array($lines));

		foreach (array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1) as $line) {
			$initialize[] = $recorder->normalise(rtrim($line));
		}
	}

	$snapshot['initialize'] = $initialize;

	file_put_contents($output, json_encode(
		$snapshot,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		| JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
	) . "\n");

	return 0;
}

/**
 * @param array<string, string> $replacements
 */
function fbDiSnapshotDumpExtension(string $id, array $replacements): Nette\DI\CompilerExtension
{
	return new class ($id, $replacements) extends Nette\DI\CompilerExtension {

		/**
		 * @param array<string, string> $replacements
		 */
		public function __construct(
			private readonly string $id,
			private readonly array $replacements,
		)
		{
		}

		/** @var array<string, mixed>|null */
		public array|null $snapshot = null;

		public function afterCompile(Nette\PhpGenerator\ClassType $class): void
		{
			try {
				$this->record();
			} catch (Throwable $ex) {
				throw new FbDiSnapshotDumpError('Recording the container failed: ' . $ex->getMessage(), 0, $ex);
			}
		}

		public function normalise(string $value): string
		{
			return strtr($value, $this->replacements);
		}

		private function record(): void
		{
			$builder = $this->getContainerBuilder();

			$services = [];
			$order = [];

			foreach ($builder->getDefinitions() as $name => $definition) {
				$order[] = (string) $name;
				$services[(string) $name] = $this->definition($definition);
			}

			ksort($services, SORT_STRING);

			$aliases = $builder->getAliases();
			ksort($aliases, SORT_STRING);

			// The generated container's $wiring, $tags and initialize() are added by the worker
			// once the class exists; see fbDiSnapshotWorker().
			$this->snapshot = [
				'format' => FB_DI_SNAPSHOT_FORMAT,
				'container' => $this->id,
				'compiled' => true,
				'extensions' => array_map('strval', array_keys($this->compiler->getExtensions())),
				'order' => $order,
				'aliases' => $aliases,
				'services' => $services,
			];
		}

		/**
		 * @param array<mixed> $value
		 */
		public function recordValue(array $value): mixed
		{
			return $this->value($value);
		}

		/**
		 * @return array<string, mixed>
		 */
		private function definition(Nette\DI\Definitions\Definition $definition): array
		{
			$record = [
				'kind' => match (true) {
					$definition instanceof Nette\DI\Definitions\ServiceDefinition => 'service',
					$definition instanceof Nette\DI\Definitions\FactoryDefinition => 'factory',
					$definition instanceof Nette\DI\Definitions\AccessorDefinition => 'accessor',
					$definition instanceof Nette\DI\Definitions\LocatorDefinition => 'locator',
					$definition instanceof Nette\DI\Definitions\ImportedDefinition => 'imported',
					default => $definition::class,
				},
				'type' => $definition->getType(),
			];

			if ($definition instanceof Nette\DI\Definitions\ServiceDefinition) {
				$record += $this->serviceBody($definition);
				$record['lazy'] = $definition->lazy;
			} elseif ($definition instanceof Nette\DI\Definitions\FactoryDefinition) {
				$record['implement'] = $definition->getImplement();
				$result = $definition->getResultDefinition();
				$record['result'] = ['type' => $result->getType()]
					+ ($result instanceof Nette\DI\Definitions\ServiceDefinition ? $this->serviceBody($result) : [])
					+ ['tags' => $this->tags($result)];
			} elseif ($definition instanceof Nette\DI\Definitions\AccessorDefinition) {
				$record['implement'] = $definition->getImplement();
				$record['reference'] = $this->value($definition->getReference());
			} elseif ($definition instanceof Nette\DI\Definitions\LocatorDefinition) {
				$record['implement'] = $definition->getImplement();
				$record['references'] = $this->value($definition->getReferences());
				$record['tagged'] = $definition->getTagged();
			}

			$record['tags'] = $this->tags($definition);
			$record['autowired'] = $this->value($definition->getAutowired());
			$record['exported'] = $definition->isExported();

			return $record;
		}

		/**
		 * @return array<string, mixed>
		 */
		private function serviceBody(Nette\DI\Definitions\ServiceDefinition $definition): array
		{
			$creator = $definition->getCreator();

			return [
				'factory' => $this->value($creator->getEntity()),
				'arguments' => $this->value($creator->arguments),
				'setup' => array_map(
					fn (Nette\DI\Definitions\Statement $setup): mixed => $this->value($setup),
					array_values($definition->getSetup()),
				),
			];
		}

		/**
		 * @return array<string, mixed>|object
		 */
		private function tags(Nette\DI\Definitions\Definition $definition): array|object
		{
			$tags = [];

			foreach ($definition->getTags() as $tag => $value) {
				$tags[(string) $tag] = $this->value($value);
			}

			ksort($tags, SORT_STRING);

			return $tags === [] ? new stdClass() : $tags;
		}

		private function value(mixed $value, int $depth = 0): mixed
		{
			if ($depth > 64) {
				return ['!' => 'nesting too deep'];
			}

			return match (true) {
				$value === null, is_bool($value), is_int($value) => $value,
				is_float($value) => is_finite($value) ? $value : ['float' => (string) $value],
				is_string($value) => strtr($value, $this->replacements),
				is_array($value) => $this->arrayValue($value, $depth),
				$value instanceof Nette\DI\Definitions\Reference => ['@' => $value->getValue()],
				$value instanceof Nette\DI\Definitions\Definition => ['@' => $value->getName()],
				$value instanceof Nette\DI\Definitions\Statement => [
					'entity' => $this->value($value->getEntity(), $depth + 1),
					'arguments' => $this->value($value->arguments, $depth + 1),
				],
				$value instanceof Nette\PhpGenerator\Literal => ['literal' => strtr((string) $value, $this->replacements)],
				$value instanceof UnitEnum => ['enum' => $value::class . '::' . $value->name],
				$value instanceof Closure => ['closure' => true],
				is_object($value) => [
					'object' => $value::class,
					'properties' => $this->value(get_object_vars($value), $depth + 1),
				],
				default => ['!' => get_debug_type($value)],
			};
		}

		/**
		 * @param array<mixed> $value
		 */
		private function arrayValue(array $value, int $depth): mixed
		{
			$result = [];

			foreach ($value as $key => $item) {
				$result[is_string($key) ? strtr($key, $this->replacements) : $key] = $this->value($item, $depth + 1);
			}

			// Keep the distinction between an empty list and an empty map out of the recording:
			// both are []. Key order is kept, it is part of what gets passed.
			return array_is_list($result) ? $result : (object) $result;
		}

	};
}

/* ------------------------------------------------------------------------------------------ */
/* Diff                                                                                       */
/* ------------------------------------------------------------------------------------------ */

/**
 * @param list<string> $args
 */
function fbDiSnapshotDiffCommand(array $args): int
{
	$dirs = [];
	$maps = [];
	$allowedMoves = [];

	while ($args !== []) {
		$arg = array_shift($args);

		if ($arg === '--allow-moves') {
			$file = array_shift($args);

			if ($file === null) {
				throw new FbDiSnapshotUsageError('--allow-moves needs a file');
			}

			$allowedMoves = array_merge($allowedMoves, fbDiSnapshotLoadAllowedMoves($file));
		} elseif ($arg === '--map') {
			$file = array_shift($args);

			if ($file === null) {
				throw new FbDiSnapshotUsageError('--map needs a file');
			}

			$maps[] = fbDiSnapshotLoadMap($file);
		} elseif (str_starts_with($arg, '--')) {
			throw new FbDiSnapshotUsageError(sprintf('Unknown option "%s"', $arg));
		} else {
			$dirs[] = $arg;
		}
	}

	if (count($dirs) !== 2) {
		throw new FbDiSnapshotUsageError('--diff needs exactly two snapshot directories: <baseDir> <headDir>');
	}

	[$baseDir, $headDir] = $dirs;
	$baseIndex = fbDiSnapshotLoadIndex($baseDir);
	$headIndex = fbDiSnapshotLoadIndex($headDir);

	$ids = array_unique(array_merge(array_keys($baseIndex), array_keys($headIndex)));
	sort($ids, SORT_STRING);

	$differing = 0;
	$identical = 0;
	$common = 0;
	$informational = 0;

	foreach ($ids as $id) {
		if (!isset($headIndex[$id])) {
			fwrite(STDOUT, sprintf("== %s\n  container only in base\n\n", $id));
			$differing++;

			continue;
		}

		if (!isset($baseIndex[$id])) {
			fwrite(STDOUT, sprintf("== %s\n  container only in head\n\n", $id));
			$differing++;

			continue;
		}

		$common++;

		$base = fbDiSnapshotLoadSnapshot($baseDir . '/' . $baseIndex[$id]['file']);
		$head = fbDiSnapshotLoadSnapshot($headDir . '/' . $headIndex[$id]['file']);

		foreach ($maps as $map) {
			$base = fbDiSnapshotApplyMap($base, $map);
		}

		[$lines, $info] = fbDiSnapshotCompare($base, $head, $allowedMoves);

		if ($lines === []) {
			$identical++;

			if ($info !== []) {
				$informational++;
				fwrite(STDOUT, '== ' . $id . " (identical; informational only)\n" . implode("\n", $info) . "\n\n");
			}

			continue;
		}

		$differing++;
		fwrite(STDOUT, '== ' . $id . "\n" . implode("\n", array_merge($lines, $info)) . "\n\n");
	}

	fwrite(STDOUT, sprintf(
		"%d containers in base, %d in head, %d in both: %d identical%s%s, %d differ.\n",
		count($baseIndex),
		count($headIndex),
		$common,
		$identical,
		$maps !== [] ? sprintf(' under %d map(s)', count($maps)) : '',
		$informational > 0 ? sprintf(' (%d with allowed moves only)', $informational) : '',
		$differing,
	));

	return $differing === 0 ? 0 : 1;
}

/**
 * One definition name per line; blank lines and lines starting with # are ignored. Names are
 * matched after any --map, i.e. as the head names them.
 *
 * @return list<string>
 */
function fbDiSnapshotLoadAllowedMoves(string $file): array
{
	if (!is_file($file)) {
		throw new FbDiSnapshotUsageError(sprintf('Allowed-moves file "%s" does not exist', $file));
	}

	$names = [];

	foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
		$line = trim($line);

		if ($line !== '' && !str_starts_with($line, '#')) {
			$names[] = $line;
		}
	}

	return $names;
}

/**
 * @return array<string, array{file: string, sha256: string}>
 */
function fbDiSnapshotLoadIndex(string $dir): array
{
	$file = $dir . '/index.json';

	if (!is_file($file)) {
		throw new FbDiSnapshotUsageError(sprintf('"%s" is not a snapshot directory (no index.json)', $dir));
	}

	$index = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

	if (!is_array($index) || ($index['format'] ?? null) !== FB_DI_SNAPSHOT_FORMAT || !is_array($index['containers'] ?? null)) {
		throw new FbDiSnapshotUsageError(sprintf('"%s" was written by an incompatible version of this tool', $file));
	}

	return $index['containers'];
}

/**
 * @return array<string, mixed>
 */
function fbDiSnapshotLoadSnapshot(string $file): array
{
	$snapshot = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
	assert(is_array($snapshot));

	return $snapshot;
}

/**
 * @return array{services: array<string, string>, tags: array<string, string>}
 */
function fbDiSnapshotLoadMap(string $file): array
{
	if (!is_file($file)) {
		throw new FbDiSnapshotUsageError(sprintf('Map "%s" does not exist', $file));
	}

	$map = require $file;

	if (!is_array($map) || array_diff(array_keys($map), ['services', 'tags']) !== []) {
		throw new FbDiSnapshotUsageError(sprintf('Map "%s" must return an array with only "services" and "tags" keys', $file));
	}

	$result = ['services' => [], 'tags' => []];

	foreach (['services', 'tags'] as $kind) {
		$entries = $map[$kind] ?? [];

		if (!is_array($entries)) {
			throw new FbDiSnapshotUsageError(sprintf('Map "%s": "%s" must be an array', $file, $kind));
		}

		foreach ($entries as $old => $new) {
			if (!is_string($old) || !is_string($new) || $old === '' || $new === '') {
				throw new FbDiSnapshotUsageError(sprintf('Map "%s": "%s" must map non-empty strings to strings', $file, $kind));
			}
		}

		if (count(array_unique($entries)) !== count($entries)) {
			throw new FbDiSnapshotUsageError(sprintf('Map "%s": two "%s" entries rename to the same name', $file, $kind));
		}

		$result[$kind] = $entries;
	}

	return $result;
}

/**
 * @param array<string, mixed> $snapshot
 * @param array{services: array<string, string>, tags: array<string, string>} $map
 *
 * @return array<string, mixed>
 */
function fbDiSnapshotApplyMap(array $snapshot, array $map): array
{
	if (($snapshot['compiled'] ?? false) !== true) {
		return $snapshot;
	}

	$services = $map['services'];
	$tags = $map['tags'];
	$strings = $services + $tags;

	$rename = static fn (string $name): string => $services[$name] ?? $name;

	$rewrite = static function (mixed $value) use (&$rewrite, $services, $strings): mixed {
		if (is_string($value)) {
			return $strings[$value] ?? $value;
		}

		if (!is_array($value)) {
			return $value;
		}

		if (count($value) === 1 && isset($value['@']) && is_string($value['@'])) {
			return ['@' => $services[$value['@']] ?? $value['@']];
		}

		foreach ($value as $key => $item) {
			$value[$key] = $rewrite($item);
		}

		return $value;
	};

	$snapshot['order'] = array_map($rename, $snapshot['order']);

	$aliases = [];

	foreach ($snapshot['aliases'] as $alias => $target) {
		$aliases[$rename((string) $alias)] = $rename($target);
	}

	ksort($aliases, SORT_STRING);
	$snapshot['aliases'] = $aliases;

	$renamed = [];

	foreach ($snapshot['services'] as $name => $service) {
		$newName = $rename((string) $name);

		if (isset($renamed[$newName])) {
			throw new FbDiSnapshotUsageError(sprintf('The map renames two services to "%s"', $newName));
		}

		foreach (['tags', 'result'] as $holder) {
			if ($holder === 'result' && !isset($service['result'])) {
				continue;
			}

			$tagSet = $holder === 'tags' ? $service['tags'] : ($service['result']['tags'] ?? []);
			$newTags = [];

			foreach ($tagSet as $tag => $value) {
				$newTags[$tags[$tag] ?? $tag] = $value;
			}

			ksort($newTags, SORT_STRING);

			if ($holder === 'tags') {
				$service['tags'] = $newTags;
			} else {
				$service['result']['tags'] = $newTags;
			}
		}

		if (isset($service['tagged']) && is_string($service['tagged'])) {
			$service['tagged'] = $tags[$service['tagged']] ?? $service['tagged'];
		}

		foreach (['factory', 'arguments', 'setup', 'reference', 'references', 'result'] as $field) {
			if (array_key_exists($field, $service)) {
				$service[$field] = $rewrite($service[$field]);
			}
		}

		$renamed[$newName] = $service;
	}

	ksort($renamed, SORT_STRING);
	$snapshot['services'] = $renamed;

	// The generated container: every service name in $wiring and $tags, tag names as $tags
	// keys, and quoted names in the initialize() body.
	$wiring = [];

	foreach ($snapshot['wiring'] ?? [] as $type => $lists) {
		foreach ($lists as $key => $names) {
			$lists[$key] = array_map($rename, $names);
		}

		$wiring[$type] = $lists;
	}

	$snapshot['wiring'] = $wiring;

	$generatedTags = [];

	foreach ($snapshot['tags'] ?? [] as $tag => $pairs) {
		$generatedTags[$tags[$tag] ?? $tag] = array_map(
			static fn (array $pair): array => [$rename($pair[0]), $pair[1]],
			$pairs,
		);
	}

	ksort($generatedTags, SORT_STRING);
	$snapshot['tags'] = $generatedTags;

	$quoted = [];

	foreach ($strings as $old => $new) {
		$quoted["'" . $old . "'"] = "'" . $new . "'";
	}

	$snapshot['initialize'] = array_map(
		static fn (string $line): string => strtr($line, $quoted),
		$snapshot['initialize'] ?? [],
	);

	return $snapshot;
}

/**
 * @param array<string, mixed> $base
 * @param array<string, mixed> $head
 * @param list<string> $allowedMoves
 *
 * @return array{list<string>, list<string>} [differences, informational lines]
 */
function fbDiSnapshotCompare(array $base, array $head, array $allowedMoves = []): array
{
	$lines = [];
	$info = [];
	$json = static fn (mixed $value): string => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION) ?: '?';

	if (($base['compiled'] ?? false) !== true || ($head['compiled'] ?? false) !== true) {
		if (($base['compiled'] ?? false) === ($head['compiled'] ?? false) && ($base['error'] ?? null) === ($head['error'] ?? null)) {
			return [[], []];
		}

		return [[
			'  compile result changed',
			'    base: ' . (($base['compiled'] ?? false) === true ? 'compiles' : $json($base['error'] ?? null)),
			'    head: ' . (($head['compiled'] ?? false) === true ? 'compiles' : $json($head['error'] ?? null)),
		], []];
	}

	// Definition order, over the names both sides have: additions and removals are reported
	// with the services below, not as an order change. A name listed in --allow-moves may
	// change position: the order is compared with those names taken out, and their moves are
	// only reported. Every observable order (setups, $wiring, $tags) is still compared below.
	$commonNames = array_flip(array_intersect($base['order'], $head['order']));
	$baseOrder = array_values(array_filter($base['order'], static fn (string $n): bool => isset($commonNames[$n])));
	$headOrder = array_values(array_filter($head['order'], static fn (string $n): bool => isset($commonNames[$n])));

	if ($baseOrder !== $headOrder) {
		$allowed = array_flip($allowedMoves);
		$strictBase = array_values(array_filter($baseOrder, static fn (string $n): bool => !isset($allowed[$n])));
		$strictHead = array_values(array_filter($headOrder, static fn (string $n): bool => !isset($allowed[$n])));

		if ($strictBase === $strictHead) {
			$headPositions = array_flip($headOrder);
			$moved = [];

			foreach ($baseOrder as $position => $name) {
				if (isset($allowed[$name]) && $headPositions[$name] !== $position) {
					$moved[] = sprintf('%s #%d -> #%d', $name, $position, $headPositions[$name]);
				}
			}

			$info[] = sprintf('  info: %d allowed definition(s) moved in the global order; nothing else did', count($moved));

			foreach ($moved as $move) {
				$info[] = '    ' . $move;
			}
		} else {
			$moved = 0;
			$first = null;

			foreach ($strictBase as $position => $name) {
				if (($strictHead[$position] ?? null) !== $name) {
					$first ??= $position;
					$moved++;
				}
			}

			$lines[] = sprintf(
				'  order changed: %d of %d common definitions%s are at a different position; first at #%d',
				$moved,
				count($strictBase),
				$allowedMoves !== [] ? ' outside --allow-moves' : '',
				(int) $first,
			);
			$lines[] = '    base: ' . implode(', ', array_slice($strictBase, max(0, (int) $first - 2), 8));
			$lines[] = '    head: ' . implode(', ', array_slice($strictHead, max(0, (int) $first - 2), 8));
		}
	}

	foreach (['extensions' => 'extension order', 'aliases' => 'aliases'] as $field => $label) {
		if (($base[$field] ?? null) !== ($head[$field] ?? null)) {
			$lines[] = '  ' . $label . ' changed';
			$lines[] = '    base: ' . $json($base[$field] ?? null);
			$lines[] = '    head: ' . $json($head[$field] ?? null);
		}
	}

	// The generated container's runtime collections, in their compiled order
	foreach (['wiring' => 'wiring[%s]', 'tags' => 'tags[%s]'] as $field => $label) {
		$baseItems = $base[$field] ?? [];
		$headItems = $head[$field] ?? [];
		$keys = array_unique(array_merge(array_keys($baseItems), array_keys($headItems)));
		sort($keys, SORT_STRING);

		foreach ($keys as $key) {
			if (($baseItems[$key] ?? null) !== ($headItems[$key] ?? null)) {
				$lines[] = '  ' . sprintf($label, $key) . ' changed';
				$lines[] = '    base: ' . (isset($baseItems[$key]) ? $json($baseItems[$key]) : '(none)');
				$lines[] = '    head: ' . (isset($headItems[$key]) ? $json($headItems[$key]) : '(none)');
			}
		}
	}

	if (($base['initialize'] ?? []) !== ($head['initialize'] ?? [])) {
		$baseInit = $base['initialize'] ?? [];
		$headInit = $head['initialize'] ?? [];
		$lines[] = sprintf('  initialize() changed (%d lines in base, %d in head)', count($baseInit), count($headInit));

		foreach (array_values(array_diff($baseInit, $headInit)) as $line) {
			$lines[] = '    - ' . trim($line);
		}

		foreach (array_values(array_diff($headInit, $baseInit)) as $line) {
			$lines[] = '    + ' . trim($line);
		}

		if (array_diff($baseInit, $headInit) === [] && array_diff($headInit, $baseInit) === []) {
			$lines[] = '    (same lines, different order)';
		}
	}

	$names = array_unique(array_merge(array_keys($base['services']), array_keys($head['services'])));
	sort($names, SORT_STRING);

	foreach ($names as $name) {
		$baseService = $base['services'][$name] ?? null;
		$headService = $head['services'][$name] ?? null;

		if ($headService === null) {
			$lines[] = sprintf('  service %s: only in base (%s)', $name, $baseService['type'] ?? '?');

			continue;
		}

		if ($baseService === null) {
			$lines[] = sprintf('  service %s: only in head (%s)', $name, $headService['type'] ?? '?');

			continue;
		}

		if ($baseService === $headService) {
			continue;
		}

		$lines[] = sprintf('  service %s: changed', $name);

		$fields = array_unique(array_merge(array_keys($baseService), array_keys($headService)));

		foreach ($fields as $field) {
			$baseValue = $baseService[$field] ?? null;
			$headValue = $headService[$field] ?? null;

			if ($baseValue === $headValue) {
				continue;
			}

			if ($field === 'setup' && is_array($baseValue) && is_array($headValue)) {
				$lines[] = sprintf('    setup (%d in base, %d in head):', count($baseValue), count($headValue));

				for ($i = 0; $i < max(count($baseValue), count($headValue)); $i++) {
					if (($baseValue[$i] ?? null) !== ($headValue[$i] ?? null)) {
						$lines[] = sprintf('      #%d base: %s', $i, isset($baseValue[$i]) ? $json($baseValue[$i]) : '(none)');
						$lines[] = sprintf('      #%d head: %s', $i, isset($headValue[$i]) ? $json($headValue[$i]) : '(none)');
					}
				}

				continue;
			}

			$lines[] = sprintf('    %s base: %s', $field, $json($baseValue));
			$lines[] = sprintf('    %s head: %s', $field, $json($headValue));
		}
	}

	return [$lines, $info];
}
