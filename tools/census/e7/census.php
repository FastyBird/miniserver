<?php declare(strict_types = 1);

/**
 * E7.0 census (#694): tables T1-T9 of docs/superpowers/plans/2026-10-10-e7-census.md, for the 28
 * packages outside Core (#462 §4.1). Run it through tools/census/e7/php.sh, in the application
 * image, on a fresh, non-symlinked mirror (lib.php refuses anything else).
 *
 *   php tools/census/e7/census.php <table> [--package <Type>[/<Name>]]... [--<input> <file>]
 *
 *   index   the parse index's size: files, declarations, parse errors
 *   t1      every I-prefixed interface and T-prefixed trait, its users, the type its bare name
 *           would collide with, and the proposed role name from names.php
 *   t2      every interface declared in a package's src/: production implementers (transitive,
 *           tests excluded), R1 roots, K1-K4 evidence and the decision under #462 D6
 *   t3      every get/set accessor pair (E5's T6 rule, tools/census/e5/members.php), in scope or
 *           excluded (entity, entity trait, orisai MappedObject, fluent setter)
 *   t4      every non-final concrete class: the final candidates, and every class left open with
 *           its reason (extended at file:line, Doctrine-mapped); a class mocked by name in tests
 *           is flagged (E2 reverted three such `final`s, #492)
 *   t5      `readonly class` candidates among the classes that are or will be final (T4)
 *   t6      every Nette\SmartObject class and trait: live magic (`$this->onX()` through __call,
 *           `@property` with no declared property, magic isset/unset) and every subclass in
 *           another package; plus every `@property` line in the packages, classified
 *   t7      every class constant overridden in another package, and the type each family takes
 *   t8      the runtime service locators: Nette\DI\Container taken outside DI\ and a lookup on it
 *   t9      per-type target counts; needs --phpcs <json> (a phpcs JSON report of
 *           ForbiddenAnnotations and ClassConstantTypeHint with the E7 carve-outs removed) and
 *           --rector <txt> (a dry run of tools/rector/e7-override.php)
 *
 * Output is Markdown, one table per type (Plugin, Automator, Addon, Module, Bridge, Connector),
 * with file:line evidence relative to the repository root. --package narrows every table to the
 * named types or packages; the evidence (an extends, a catch, a lookup) is still searched in the
 * whole tree.
 */

require __DIR__ . '/lib.php';

[$positional, $filters, $options] = e7Arguments($argv);
$table = $positional[0] ?? e7Fail('usage: census.php index|t1|t2|t3|t4|t5|t6|t7|t8|t9 [--package <Type>[/<Name>]]...');

e7CheckMirrors();

$packages = e7Packages($filters);
$index = e7Index();
$decls = $index['decls'];

/**
 * The declarations of a package's production code (src/), named types only.
 *
 * @return array<string, array<string, mixed>>
 */
function e7SrcDecls(string $package): array
{
	return array_filter(
		e7Index()['decls'],
		static fn (array $d): bool => !$d['anonymous'] && e7PackageOf($d['file']) === $package && e7AreaOf($d['file']) === 'src',
	);
}

function e7Loc(string $file, int $line): string
{
	return '`' . $file . ':' . $line . '`';
}

/**
 * @param list<string> $header
 * @param list<list<string|int>> $rows
 */
function e7Table(array $header, array $rows): void
{
	echo '| ', implode(' | ', $header), " |\n";
	echo '|', str_repeat('---|', count($header)), "\n";

	foreach ($rows as $row) {
		echo '| ', implode(' | ', array_map(static fn ($c): string => str_replace(["\n", '|'], [' ', '\\|'], (string) $c), $row)), " |\n";
	}

	echo "\n";
}

/**
 * Groups the selected packages by type, in E7's type order.
 *
 * @param array<string, array<string, mixed>> $packages
 *
 * @return array<string, list<string>>
 */
function e7ByType(array $packages): array
{
	$byType = [];

	foreach ($packages as $key => $package) {
		$byType[$package['type']][] = $key;
	}

	return array_filter(array_replace(array_fill_keys(E7_TYPES, []), $byType));
}

/**
 * Loads a type through the autoloader without letting a broken one stop the census.
 */
function e7Reflect(string $fqcn): ReflectionClass|null
{
	try {
		if (class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn) || enum_exists($fqcn)) {
			return new ReflectionClass($fqcn);
		}
	} catch (Throwable) {
	}

	return null;
}

// ---------------------------------------------------------------------------------------------
// Shared analyses

/**
 * Production implementers of every interface: concrete classes and enums under a package's or
 * Core's src/ whose interface closure holds it. R1 roots: the topmost class of each implementing
 * chain (an implementer whose ancestor also implements it is not a root), abstract or not.
 *
 * @return array<string, array{concrete: list<string>, roots: list<string>}>
 */
function e7Implementers(): array
{
	static $result = null;

	if ($result !== null) {
		return $result;
	}

	$decls = e7Index()['decls'];
	$closure = e7InterfaceClosure();
	$result = [];

	foreach ($decls as $fqcn => $decl) {
		if ($decl['kind'] === 'interface' || $decl['kind'] === 'trait' || $decl['anonymous'] || e7AreaOf($decl['file']) !== 'src') {
			continue;
		}

		foreach ($closure[$fqcn] ?? [] as $interface) {
			if (!$decl['abstract']) {
				$result[$interface]['concrete'][] = $fqcn;
			}

			$isRoot = true;

			foreach (e7Ancestors($fqcn) as $ancestor) {
				if (in_array($interface, $closure[$ancestor] ?? [], true) && e7AreaOf($decls[$ancestor]['file'] ?? '') === 'src') {
					$isRoot = false;

					break;
				}
			}

			if ($isRoot) {
				$result[$interface]['roots'][] = $fqcn;
			}
		}
	}

	foreach ($result as &$entry) {
		$entry += ['concrete' => [], 'roots' => []];
		sort($entry['concrete']);
		sort($entry['roots']);
	}

	return $result;
}

/**
 * Every place a type is named as a DI substitution point: a findByType()/getByType()/
 * getDefinitionByType()/setImplement() argument in PHP (src/ only), or its FQCN in a NEON file.
 *
 * @return array<string, list<string>> FQCN => evidence
 */
function e7K3Evidence(): array
{
	static $evidence = null;

	if ($evidence !== null) {
		return $evidence;
	}

	$evidence = [];

	foreach (e7Index()['lookups'] as [$method, $class, $receiver, $file, $line]) {
		if ($class === null || e7AreaOf($file) !== 'src' || !in_array($method, ['findByType', 'getByType', 'getDefinitionByType', 'setImplement'], true)) {
			continue;
		}

		$evidence[$class][] = $method . '() ' . e7Loc($file, $line);
	}

	$neon = [];
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(e7Root() . '/src', FilesystemIterator::SKIP_DOTS));

	foreach ($iterator as $f) {
		if ($f->getExtension() === 'neon' && !str_contains($f->getPathname(), '/node_modules/')) {
			$neon[] = $f->getPathname();
		}
	}

	foreach (glob(e7Root() . '/config/*.neon') ?: [] as $f) {
		$neon[] = $f;
	}

	sort($neon);

	foreach ($neon as $path) {
		foreach (file($path) ?: [] as $i => $text) {
			if (preg_match_all('~\\\\?(FastyBird(?:\\\\[A-Za-z0-9_]+)+)~', $text, $m) > 0) {
				foreach ($m[1] as $fqcn) {
					$evidence[$fqcn][] = 'NEON ' . e7Loc(substr($path, strlen(e7Root()) + 1), $i + 1);
				}
			}
		}
	}

	return $evidence;
}

/**
 * Every class (src/, tests/, bin/, public/, migrations/, anonymous ones included) that names
 * $fqcn as its parent.
 *
 * @return list<string> "file:line"
 */
function e7ExtendedAt(string $fqcn): array
{
	static $byParent = null;

	if ($byParent === null) {
		$byParent = [];

		foreach (e7Index()['decls'] as $child => $decl) {
			if ($decl['kind'] === 'class') {
				foreach ($decl['extends'] as $parent) {
					$byParent[$parent][] = ['child' => $child, 'file' => $decl['file'], 'line' => $decl['line']];
				}
			}
		}
	}

	return $byParent[$fqcn] ?? [];
}

// ---------------------------------------------------------------------------------------------

switch ($table) {
	case 'index':
		$kinds = array_count_values(array_column($decls, 'kind'));
		printf("files %d, declarations %d (%s), parse errors %d\n", count($index['files']), count($decls), json_encode($kinds), count($index['errors']));

		foreach ($index['errors'] as [$file, $error]) {
			printf("  parse error %s: %s\n", $file, $error);
		}

		break;
	case 't1':
		require __DIR__ . '/t1.php';

		break;
	case 't2':
		require __DIR__ . '/t2.php';

		break;
	case 't3':
		require __DIR__ . '/t3.php';

		break;
	case 't4':
	case 't5':
		require __DIR__ . '/t4.php';

		break;
	case 't6':
		require __DIR__ . '/t6.php';

		break;
	case 't7':
		require __DIR__ . '/t7.php';

		break;
	case 't8':
		require __DIR__ . '/t8.php';

		break;
	case 't9':
		require __DIR__ . '/t9.php';

		break;
	default:
		e7Fail(sprintf('unknown table "%s"', $table));
}
