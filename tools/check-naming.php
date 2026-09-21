<?php declare(strict_types = 1);

/**
 * No file under src/FastyBird may name a library that fastybird/miniserver-core was
 * assembled from.
 *
 * WHY THIS IS A GATE
 *
 * Core was merged from 15 packages. The merge preserved their identities in six layers, and
 * the largest by far was import aliases: 3,333 `use FastyBird\Core\... as <OldName>;`
 * statements, 139 distinct forms, with FastyBird\Core\Exceptions alone aliased 11 different
 * ways depending on which library the importing file came from.
 *
 * They exist because nothing checked for them, and they will re-form without a gate, because
 * aliasing is the cheapest way to move a class without rewriting the file body. Renaming
 * directories does not fix this on its own: the alias is what a reader actually sees in the
 * body of the code.
 *
 * Aliases are checked by a POSITIVE rule rather than a denylist of old names. An alias is
 * legal only when it equals the last two segments of the imported namespace joined together:
 *
 *   use FastyBird\Core\Api\Schemas as ApiSchemas;      legal
 *   use FastyBird\Core\Documents as CoreDocuments;     legal
 *   use FastyBird\Core\Documents as ExchangeDocuments; ILLEGAL
 *
 * A denylist can only ban the names someone already thought of. The positive rule bans every
 * name that is not derived from where the symbol actually lives, which is the property we
 * actually want, and it needs no maintenance as capabilities are renamed.
 *
 * Namespaces and declared type names do use denylists, because there the offending token is a
 * known, closed set of former package names. The two lists differ on purpose: `Application`,
 * `Metadata` and `Tools` are banned as NAMESPACE SEGMENTS (they are former package names being
 * used as grouping layers) but permitted inside a declared TYPE name, where they are ordinary
 * English words -- Ratchet's WampApplication is not a reference to FastyBird:Application!.
 *
 * Exit codes follow tools/check-layering.php:
 *   0  clean
 *   1  at least one violation, or the baseline has gone stale
 *   2  the tool could not do its job
 *
 * Plain PHP, no dependencies; neither vendor/ nor an autoloader is required.
 */

/**
 * Bail out for reasons that are the TOOL's problem rather than the repository's, so a CI log
 * distinguishes "the invariant broke" from "the gate broke".
 */
function fbFail(string $message): never
{
	fwrite(STDERR, 'check-naming: ' . $message . PHP_EOL);

	exit(2);
}

const FB_NAMESPACE_DENYLIST = [
	'SimpleAuth',
	'SlimRouter',
	'DoctrineCrud',
	'DoctrineOrmQuery',
	'DoctrineTimestampable',
	'DoctrinePhone',
	'JsonApi',
	'JsonApiDocument',
	'Metadata',
	'Tools',
	'Application',
	'DateTimeFactory',
	'WebServer',
	'WsServer',
	'HttpServer',
	'IPub',
];

const FB_TYPE_DENYLIST = [
	'SimpleAuth',
	'SlimRouter',
	'DoctrineCrud',
	'DoctrineOrmQuery',
	'DoctrineTimestampable',
	'DoctrinePhone',
	'IPub',
	'IPublikuj',
];

$repoRoot = dirname(__DIR__);

if (!is_dir($repoRoot . '/src/FastyBird')) {
	fbFail(sprintf('"%s/src/FastyBird" is not a directory', $repoRoot));
}

/**
 * Every .php file in the repository that is ours: package sources and tests, plus the
 * root-level bootstrap and configuration files.
 *
 * The root-level ones matter. During PR #454 a consumer sweep scoped to src/FastyBird/**
 * missed public/index.php, which still referenced a namespace the sweep had removed; the
 * production Docker smoke test in CI was the only thing that caught it.
 *
 * @return array<string>
 */
function fbCollectFiles(string $repoRoot): array
{
	$files = [];

	$roots = [
		$repoRoot . '/src/FastyBird',
		$repoRoot . '/public',
		$repoRoot . '/bin',
		$repoRoot . '/tests',
		$repoRoot . '/migrations',
	];

	foreach ($roots as $root) {
		if (!is_dir($root)) {
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
				// pnpm materialises a node_modules per workspace package, each symlinking a
				// whole package root, so the same file is reachable many times over. Prune
				// them wherever they appear, not just at the repository root -- the same
				// reason tools/layering.php and tools/phpcs.xml do.
				static fn (SplFileInfo $entry): bool => $entry->getFilename() !== 'node_modules'
					&& $entry->getFilename() !== 'vendor',
			),
		);

		foreach ($iterator as $entry) {
			assert($entry instanceof SplFileInfo);

			if ($entry->isFile() && $entry->getExtension() === 'php') {
				$files[] = $entry->getPathname();
			}
		}
	}

	sort($files);

	return $files;
}

/**
 * @return array<string>
 */
function fbCheckFile(string $path, string $code, string $relative): array
{
	$violations = [];

	preg_match('/^namespace\s+([^;]+);/m', $code, $namespaceMatch);
	$namespace = isset($namespaceMatch[1]) ? trim($namespaceMatch[1]) : '';
	$isCore = str_starts_with($namespace, 'FastyBird\\Core');

	// 1. Namespace segments, Core only.
	if ($isCore) {
		foreach (explode('\\', $namespace) as $segment) {
			if (in_array($segment, FB_NAMESPACE_DENYLIST, true)) {
				$violations[] = sprintf("namespace\t%s\t%s in %s", $relative, $segment, $namespace);
			}
		}
	}

	// 2. Declared type names, Core only.
	if ($isCore) {
		preg_match_all(
			'/^(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
			$code,
			$typeMatches,
		);

		foreach ($typeMatches[1] as $typeName) {
			foreach (FB_TYPE_DENYLIST as $token) {
				if (str_contains($typeName, $token)) {
					$violations[] = sprintf("type\t%s\t%s contains %s", $relative, $typeName, $token);
				}
			}
		}
	}

	// 3. Import aliases of a FastyBird\Core symbol, everywhere in the repository.
	preg_match_all(
		'/^use\s+(FastyBird\\\\Core\\\\[A-Za-z0-9_\\\\]+)\s+as\s+(\w+)\s*;/m',
		$code,
		$aliasMatches,
		PREG_SET_ORDER,
	);

	foreach ($aliasMatches as $match) {
		$imported = $match[1];
		$alias = $match[2];
		$segments = explode('\\', $imported);
		$expected = implode('', array_slice($segments, -2));

		if ($alias !== $expected) {
			$violations[] = sprintf(
				"alias\t%s\t%s as %s (expected %s)",
				$relative,
				$imported,
				$alias,
				$expected,
			);
		}
	}

	return $violations;
}

$files = fbCollectFiles($repoRoot);
$violations = [];
$coreImports = 0;

foreach ($files as $path) {
	$code = file_get_contents($path);

	if ($code === false) {
		fbFail(sprintf('could not read "%s"', $path));
	}

	$relative = substr($path, strlen($repoRoot) + 1);
	$coreImports += preg_match_all('/^use\s+FastyBird\\\\Core\\\\/m', $code);

	foreach (fbCheckFile($path, $code, $relative) as $violation) {
		$violations[] = $violation;
	}
}

// Self-check. A regex that silently stops matching would otherwise report success over zero
// findings, which is the exact false-green this repository has been bitten by before. The
// floors are deliberately far below today's measured numbers (3,431 files in scope, 4,045
// FastyBird\Core imports) so ordinary churn does not trip them, while a broken matcher does.
if (count($files) < 2_000) {
	fbFail(sprintf('scanned only %d PHP files; expected at least 2000', count($files)));
}

if ($coreImports < 1_500) {
	fbFail(sprintf('found only %d FastyBird\\Core imports; expected at least 1500', $coreImports));
}

sort($violations);

if ($violations !== []) {
	fwrite(STDERR, sprintf("%d naming violations:\n\n", count($violations)));

	foreach ($violations as $violation) {
		fwrite(STDERR, '  ' . str_replace("\t", '  ', $violation) . PHP_EOL);
	}

	exit(1);
}

printf("Checked %d PHP files; no former library names in namespaces, type names or aliases.\n", count($files));

exit(0);
