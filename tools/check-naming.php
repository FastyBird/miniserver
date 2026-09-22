<?php declare(strict_types = 1);

/**
 * No file under src/FastyBird may name a library that fastybird/miniserver-core was
 * assembled from.
 *
 * WHY THIS IS A GATE
 *
 * Core was merged from 15 packages. The merge preserved their identities in six layers, and
 * the largest by far was import aliases: 3,076 `use FastyBird\Core\... as <OldName>;`
 * statements, 131 distinct forms, with FastyBird\Core\Exceptions alone aliased 11 different
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
 * The same rule applies to `use FastyBird\Core as X;` (one segment after the root): the
 * expected alias is still the last two segments joined, `FastyBirdCore`. There is no special
 * case for the root -- `array_slice(..., -2)` degrades correctly because a Core import always
 * has at least two segments (`FastyBird`, `Core`).
 *
 * The alias detector recognises every legal `use` form PHP has, not just the plain
 * `use X as Y;` case: leading whitespace (a class-body `use Trait;` or a re-indent), grouped
 * imports (`use FastyBird\Core\{Documents as D, Http as H};`), comma lists
 * (`use FastyBird\Core\A as X, FastyBird\Core\B as Y;`), a leading `\`, an uppercase `AS`, and
 * `use function` / `use const`. `make cs` independently rejects several of these forms, but
 * only under `src` (see Makefile) -- `public/`, `bin/`, `migrations/` and the root `tests/`
 * have no second line of defence, and this gate is the only thing that scans them.
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
 * Splits on top-level commas only -- a comma inside a `{...}` group (grouped `use`, or a
 * trait-adaptation block) does not separate imports.
 *
 * @return array<string>
 */
function fbSplitTopLevel(string $text): array
{
	$parts = [];
	$depth = 0;
	$current = '';

	foreach (str_split($text) as $char) {
		if ($char === '{') {
			$depth++;
		} elseif ($char === '}') {
			$depth--;
		}

		if ($char === ',' && $depth === 0) {
			$parts[] = trim($current);
			$current = '';

			continue;
		}

		$current .= $char;
	}

	if (trim($current) !== '') {
		$parts[] = trim($current);
	}

	return $parts;
}

/**
 * Splits a single import item -- `Name` or `Name as Alias` -- allowing an uppercase `AS`.
 *
 * @return array{0: string, 1: string|null}
 */
function fbSplitAsClause(string $item): array
{
	if (preg_match('/^(.+?)\s+as\s+(\w+)$/is', trim($item), $matches) === 1) {
		return [trim($matches[1]), $matches[2]];
	}

	return [trim($item), null];
}

/**
 * Splits the body of a single `use` statement -- everything between the keyword (and an
 * optional `function`/`const`) and the terminating `;` -- into its individual imports. PHP
 * allows either a bare comma list (`A as X, B as Y`) or one grouped clause
 * (`Prefix\{A as X, B}`), never both in the same statement.
 *
 * @return array<array{0: string, 1: string|null}>
 */
function fbSplitUseBody(string $body): array
{
	$body = trim($body);

	if (str_contains($body, '{')) {
		$openPos = strpos($body, '{');
		$closePos = strrpos($body, '}');

		if ($openPos === false || $closePos === false || $closePos < $openPos) {
			// Not a grouped import -- most likely a trait-adaptation block
			// (`use A, B { A::foo as bar; }`) whose statement body was truncated at the
			// block's first inner `;`. Nothing legible to check; skip rather than guess.
			return [];
		}

		$prefix = rtrim(trim(substr($body, 0, $openPos)), '\\');
		$inner = substr($body, $openPos + 1, $closePos - $openPos - 1);

		$results = [];

		foreach (fbSplitTopLevel($inner) as $item) {
			[$name, $alias] = fbSplitAsClause($item);

			if ($name === '') {
				continue;
			}

			$results[] = [$prefix . '\\' . ltrim($name, '\\'), $alias];
		}

		return $results;
	}

	$results = [];

	foreach (fbSplitTopLevel($body) as $item) {
		[$name, $alias] = fbSplitAsClause($item);

		if ($name === '') {
			continue;
		}

		$results[] = [$name, $alias];
	}

	return $results;
}

/**
 * Every aliased import of a FastyBird\Core symbol in the file, in whichever of the seven
 * legal `use` forms it was written: leading whitespace, grouped, comma list, leading `\`,
 * uppercase `AS`, `use function`/`use const`, and the bare root (`FastyBird\Core as X`).
 *
 * @return array<array{imported: string, alias: string}>
 */
function fbFindCoreAliases(string $code): array
{
	$aliases = [];

	// The body is captured non-greedily up to the first following `;`. `s` (DOTALL) lets it
	// span the multiple lines a grouped import can be written across; that is safe even so,
	// because the match still stops at the nearest `;`, and no legal `use` body contains one.
	preg_match_all('/^[ \t]*use\s+(?:(?:function|const)\s+)?(.+?);/ms', $code, $statementMatches);

	foreach ($statementMatches[1] as $body) {
		foreach (fbSplitUseBody($body) as [$name, $alias]) {
			if ($alias === null) {
				continue;
			}

			$imported = ltrim($name, '\\');

			if ($imported === 'FastyBird\\Core' || str_starts_with($imported, 'FastyBird\\Core\\')) {
				$aliases[] = ['imported' => $imported, 'alias' => $alias];
			}
		}
	}

	return $aliases;
}

/**
 * @return array<string>
 */
function fbCheckFile(string $path, string $code, string $relative): array
{
	$violations = [];

	// Matches both `namespace X;` and brace-syntax `namespace X { ... }` -- and stops right
	// after the name in both cases, so it can never swallow into the block body and produce a
	// violation string containing a newline (which --generate-baseline would then write as two
	// lines, permanently un-matchable). preg_match_all rather than preg_match, because a file
	// using brace syntax can legally declare more than one namespace.
	preg_match_all('/^[ \t]*namespace\s+([A-Za-z0-9_\\\\]+)\s*[;{]/m', $code, $namespaceMatches);

	$namespaces = $namespaceMatches[1];
	$isCore = false;

	foreach ($namespaces as $namespace) {
		if ($namespace === 'FastyBird\\Core' || str_starts_with($namespace, 'FastyBird\\Core\\')) {
			$isCore = true;

			// 1. Namespace segments, Core only.
			foreach (explode('\\', $namespace) as $segment) {
				if (in_array($segment, FB_NAMESPACE_DENYLIST, true)) {
					$violations[] = sprintf("namespace\t%s\t%s in %s", $relative, $segment, $namespace);
				}
			}
		}
	}

	// 2. Declared type names, Core only. `[ \t]*` so an indented declaration (nested inside a
	// brace-syntax namespace block, for instance) is still seen.
	if ($isCore) {
		preg_match_all(
			'/^[ \t]*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
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
	//
	// The "expected" value is the last-two-segments rule, and it is only a meaningful
	// SUGGESTION for a class-shaped import (class/interface/trait/enum), where every segment
	// is a namespace part. For `use function`/`use const` the final segment is a symbol name,
	// not a namespace part -- e.g. `use function FastyBird\Core\Helpers\format as X;` computes
	// "expected Helpersformat", which is not a name anyone should actually use. The violation
	// itself still fails closed and is real signal (the alias genuinely does not match the
	// convention), so this is not a hole -- just do not treat the printed "expected" text as
	// an authoritative replacement for a function or const alias; pick one by hand instead.
	foreach (fbFindCoreAliases($code) as $match) {
		$imported = $match['imported'];
		$alias = $match['alias'];
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
	$coreImports += preg_match_all('/^[ \t]*use\s+(?:(?:function|const)\s+)?\\\\?FastyBird\\\\Core\b/m', $code);

	foreach (fbCheckFile($path, $code, $relative) as $violation) {
		$violations[] = $violation;
	}
}

// Self-check. A regex that silently stops matching would otherwise report success over zero
// findings, which is the exact false-green this repository has been bitten by before. The
// floors are deliberately far below today's measured numbers (3,437 files in scope, 4,082
// FastyBird\Core imports, re-measured after the seven-form alias fix) so ordinary churn does
// not trip them, while a broken matcher does.
if (count($files) < 2_000) {
	fbFail(sprintf('scanned only %d PHP files; expected at least 2000', count($files)));
}

if ($coreImports < 1_500) {
	fbFail(sprintf('found only %d FastyBird\\Core imports; expected at least 1500', $coreImports));
}

sort($violations);

$baselinePath = $repoRoot . '/tools/naming-baseline.txt';

if (in_array('--generate-baseline', $argv, true)) {
	$allowGrowth = in_array('--allow-growth', $argv, true);
	$existingCount = 0;

	if (is_file($baselinePath)) {
		$existingRaw = file($baselinePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if ($existingRaw === false) {
			fbFail('could not read tools/naming-baseline.txt');
		}

		$existingCount = count($existingRaw);
	}

	$newCount = count($violations);

	// The baseline "may only shrink" (docs/conventions.md), but nothing enforced that beyond
	// the sentence -- every entry carries a file path, so any rename stales entries, and the
	// tool's own failure message below tells the engineer to regenerate. That absorbs a new
	// violation introduced in the same change as silently as it absorbs the stale ones being
	// cleaned up. Growth is sometimes legitimate (a guard fix making previously invisible
	// violations visible, as here), so it is not banned outright -- just gated behind a flag
	// an engineer has to choose on purpose, and behind saying so in the commit message.
	if ($newCount > $existingCount && !$allowGrowth) {
		fwrite(
			STDERR,
			sprintf(
				"Refusing to write a larger baseline: %d existing violations, %d new.\n\n"
				. "The baseline may only shrink (see docs/conventions.md). If this growth is\n"
				. "legitimate -- for example a guard fix that makes previously invisible\n"
				. "violations visible -- pass --allow-growth and say so in the commit message.\n",
				$existingCount,
				$newCount,
			),
		);

		exit(2);
	}

	file_put_contents($baselinePath, implode(PHP_EOL, $violations) . PHP_EOL);

	printf(
		"Wrote %d violations to tools/naming-baseline.txt (was %d).\n",
		$newCount,
		$existingCount,
	);

	exit(0);
}

if (!is_file($baselinePath)) {
	fbFail('tools/naming-baseline.txt is missing; regenerate it with --generate-baseline');
}

$baselineRaw = file($baselinePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($baselineRaw === false) {
	fbFail('could not read tools/naming-baseline.txt');
}

$baseline = array_values($baselineRaw);

$introduced = array_values(array_diff($violations, $baseline));
$fixed = array_values(array_diff($baseline, $violations));

if ($introduced !== []) {
	fwrite(STDERR, sprintf("%d NEW naming violations, not in the baseline:\n\n", count($introduced)));

	foreach ($introduced as $violation) {
		fwrite(STDERR, '  ' . str_replace("\t", '  ', $violation) . PHP_EOL);
	}

	fwrite(
		STDERR,
		PHP_EOL
		. "The baseline records what the Core identity refactor has not reached yet. It may\n"
		. "only ever shrink. See docs/conventions.md and the comment at the top of\n"
		. "tools/check-naming.php.\n",
	);

	exit(1);
}

// A baseline entry that no longer matches anything is a failure, not a courtesy. It is what
// forces the baseline to shrink as each epic lands, and it is also the real self-check: if the
// matcher breaks, every entry goes stale at once and the tool fails loudly instead of
// reporting a clean tree.
if ($fixed !== []) {
	fwrite(
		STDERR,
		sprintf("%d baseline entries are no longer violated. Remove them:\n\n", count($fixed)),
	);

	foreach ($fixed as $violation) {
		fwrite(STDERR, '  ' . str_replace("\t", '  ', $violation) . PHP_EOL);
	}

	fwrite(STDERR, PHP_EOL . "Run: php tools/check-naming.php --generate-baseline\n");

	exit(1);
}

printf(
	"Checked %d PHP files; %d known violations remain in the baseline, no new ones.\n",
	count($files),
	count($baseline),
);

exit(0);
