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
 * EXTENDED RULE: when the plain two-segment form would collide
 *
 * Two capabilities can each own a sub-namespace whose last two segments read the same --
 * `FastyBird\Core\Persistence\Mapping\Driver` and `FastyBird\Core\Security\Mapping\Driver`
 * both reduce to `MappingDriver`. When that happens inside one file, the two-segment alias
 * is no longer legal for EITHER import: the alias climbs to the last THREE segments,
 * `PersistenceMappingDriver` and `SecurityMappingDriver`. This is symmetric -- when two
 * imports' k-segment forms would be equal, BOTH escalate to k+1, never just one of them --
 * and it repeats one segment at a time for as long as the collision persists (`tools/
 * move-core-symbols.php`'s alias assignment applies the write side of the same rule when it
 * picks a fresh alias for a moved reference; this is its read side, checked structurally
 * rather than moved).
 *
 *   use FastyBird\Core\Persistence\Mapping\Driver as PersistenceMappingDriver; legal (collides
 *   use FastyBird\Core\Security\Mapping\Driver as SecurityMappingDriver;       with the above at 2 segments, so both climb to 3)
 *
 * A longer alias is legal ONLY when a same-kind sibling import in the same file justifies it
 * this way -- a bare `k > 2` alias with no colliding sibling is still illegal (gratuitous),
 * and so is a `k == 2` alias left in place while a sibling that does collide with it escalated
 * (asymmetric: the collision applies to both or neither). `use`, `use function` and
 * `use const` each keep their own alias namespace in PHP, so only same-kind imports can
 * collide with each other for this purpose.
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
 * CHECK 4: every import collision group, repository-wide (#541)
 *
 * The alias check above (3.) only ever looked at `FastyBird\Core\...` imports. But the same
 * problem -- a bare import left in place while a same-named sibling from elsewhere gets
 * aliased -- happens just as often between two ordinary, non-Core namespaces: a package's own
 * `Documents` left bare next to `Devices\Documents as DevicesDocuments`, or `Nette\Caching`
 * bare next to a module's own `Caching` also bare. Check 4 generalises the rule to every
 * `use` import in the files this gate already scans, for every KIND (class, `use function`,
 * `use const` each keep their own alias namespace and are never compared across kinds):
 *
 *   - imports are grouped, per file and per kind, by the lower-cased LAST segment of the
 *     imported name (`fbLastSegments($fqcn, 1)`) -- a GROUP is any such bucket holding two or
 *     more DISTINCT fully-qualified names (the same FQCN imported twice under two different
 *     local bindings, bare and aliased, is not a collision with itself);
 *   - a single-segment import (`use Casbin;`, `use Monolog;`, `use Exception;`) has no
 *     two-segment form and MUST stay bare even inside a group -- this is the Casbin exception
 *     already approved for Core, generalised to every import in the repository;
 *   - FB_ALIAS_EXCEPTIONS is a second, much narrower exception, a single FQCN => alias pair,
 *     not a generic mechanism: `Doctrine\ORM\Mapping` MUST be aliased exactly `ORM` when it is
 *     in a group (bare, or any other alias, is still a violation there) -- Doctrine's own
 *     documented attribute convention (`#[ORM\Entity]`), which the last-two-segments rule
 *     cannot produce (it would want `ORMMapping`) and which the 54 entity files that import it
 *     with no collision at all already use everywhere;
 *   - every other member of a group -- and `Doctrine\ORM\Mapping`'s own group-mates -- must
 *     carry EXACTLY `fbExpectedAlias($fqcn, $siblings)` -- the same symmetric,
 *     climb-on-collision function check 3 already uses, given every same-kind import of the
 *     file as `$siblings` (not just the Core ones -- see below);
 *   - a bare member of a group, or one whose alias does not match, is a violation; a member
 *     that is already correct produces nothing; an import that is not in any group is entirely
 *     out of scope (a standalone `use Doctrine\ORM\Mapping as ORM;` with no colliding sibling
 *     is never touched by this check either way -- it needs no exception to pass).
 *
 * Only a top-level import counts. A class-body `use TraitName;` is not an import -- it names a
 * trait to compose into the class, and its own `as` clause renames a method's visibility or
 * name, an entirely different thing PHP happens to spell the same way. Every legal top-level
 * `use` import in a namespaced file appears before the file's first `class`/`interface`/
 * `trait`/`enum` declaration (that is a language rule, not a style choice), so "before the
 * first declaration" is used as the structural test rather than trying to track brace depth: a
 * `use` match at or after that offset is a class body and is ignored. The same boundary, and
 * the same regex, is now shared with check 3's Core-only collector, so the two checks can never
 * disagree about which `use` statements exist in a file. A closure's `use (...)` is never
 * mistaken for an import either, because a real import never has `(` where a namespace name
 * would be, and the shared regex requires it not to.
 *
 * SIBLINGS: check 3 now shares check 4's rule. Before #541, check 3 computed each Core
 * import's expected alias using only the file's OTHER Core imports as siblings, because
 * nothing else was being checked. That is no longer sufficient: `FastyBird\Core\DI` and
 * `Nette\DI` reduce to the same short name, so a file can contain a Core import that check 3
 * alone would consider correctly aliased (no OTHER Core import collides with it) while check 4
 * requires the SAME import to escalate, because a non-Core sibling does collide with it -- two
 * checks silently disagreeing about the one import. Check 3 and check 4 now compute every
 * expected alias from the identical function call over the identical, whole-file, same-kind
 * sibling list, so the two can never reach a different answer for the same import. The only
 * remaining coordination needed is not reporting the same bad import twice: if check 3 already
 * flagged an import's alias as wrong, check 4 skips it rather than repeating the finding under
 * the `import` category.
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

/**
 * A named exception to check 4's collision rule, next to (not instead of) the single-segment
 * exception -- NOT a generic "vendor idiom" mechanism, just this one FQCN => alias pair. `ORM`
 * is Doctrine's own documented attribute convention (`#[ORM\Entity]`); 54 entity files use it
 * with no collision at all, and the handful that also import a same-named `...\Mapping`
 * (`FastyBird\Core\Persistence\Mapping`, for a discriminator map) would otherwise be forced
 * onto `ORMMapping` alone, splitting entity files across two styles for no reader benefit.
 * Only this exact pair is exempt: `Doctrine\ORM\Mapping` aliased anything else (`Orm`,
 * `ORMMapping`) or left bare, while inside a collision group, is still a violation, and its
 * expected text is `ORM`, not whatever fbExpectedAlias() would otherwise climb to. Every OTHER
 * member of the same group is unaffected and still gets its normal fbExpectedAlias().
 */
const FB_ALIAS_EXCEPTIONS = [
	'Doctrine\\ORM\\Mapping' => 'ORM',
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
 * The offset of the file's first `class`/`interface`/`trait`/`enum` declaration, or PHP_INT_MAX
 * when it has none. Every legal top-level `use` import statement in a PHP file must appear
 * before the first such declaration -- PHP does not allow one afterward -- so this offset is a
 * reliable, purely structural boundary between "these `use` statements are imports" and "these
 * are class-body trait composition", without having to track brace depth through the rest of
 * the file.
 */
function fbFindImportBoundary(string $code): int
{
	if (
		preg_match(
			'/^[ \t]*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+\w+/m',
			$code,
			$matches,
			PREG_OFFSET_CAPTURE,
		) === 1
	) {
		return $matches[0][1];
	}

	return PHP_INT_MAX;
}

/**
 * Every top-level `use` IMPORT in the file (i.e. before $boundary, see fbFindImportBoundary),
 * aliased or not, in whichever of the seven legal forms it was written: leading whitespace,
 * grouped, comma list, leading `\`, uppercase `AS`, `use function`/`use const`, and the bare
 * root (`FastyBird\Core as X`). A bare (never aliased) import is included too: it still names
 * an FQCN that can be the OTHER half of a collision an aliased sibling escalates to avoid, and
 * -- since #541 -- can itself be the half that is missing a required alias.
 *
 * `kind` is `class`, `function` or `const` -- PHP keeps a separate alias namespace per kind
 * (`use Foo;` and `use function Foo;` never collide with each other), so only same-kind
 * imports are ever compared against one another for the collision rule below.
 *
 * `(?!\()` immediately after `use\s+` rejects a closure's `use ($foo)`: a real import never has
 * `(` where a namespace name would be, and without the exclusion a closure written as
 *
 *   function ()
 *       use ($foo) {
 *
 * would put `use` at the very start of a line, the only thing that otherwise distinguishes an
 * import from the rest of a statement.
 *
 * @return array<array{imported: string, alias: string|null, kind: string}>
 */
function fbFindAllImports(string $code, int $boundary): array
{
	$imports = [];

	// The body is captured non-greedily up to the first following `;`. `s` (DOTALL) lets it
	// span the multiple lines a grouped import can be written across; that is safe even so,
	// because the match still stops at the nearest `;`, and no legal `use` body contains one.
	preg_match_all(
		'/^[ \t]*use\s+(?!\()((?:function|const)\s+)?(.+?);/ms',
		$code,
		$statementMatches,
		PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
	);

	foreach ($statementMatches as $statement) {
		if ($statement[0][1] >= $boundary) {
			// At or after the file's first class-like declaration -- a class-body trait `use`,
			// not an import.
			continue;
		}

		$kind = trim($statement[1][0]) !== '' ? strtolower(trim($statement[1][0])) : 'class';

		foreach (fbSplitUseBody($statement[2][0]) as [$name, $alias]) {
			$imported = ltrim($name, '\\');

			$imports[] = ['imported' => $imported, 'alias' => $alias, 'kind' => $kind];
		}
	}

	return $imports;
}

/**
 * Narrows fbFindAllImports()'s result to FastyBird\Core imports only -- check 3's original
 * scope, now expressed as a filter over the shared collector rather than its own regex, so
 * check 3 and check 4 can never disagree about which `use` statements a file contains.
 *
 * @param array<array{imported: string, alias: string|null, kind: string}> $allImports
 *
 * @return array<array{imported: string, alias: string|null, kind: string}>
 */
function fbFilterCoreImports(array $allImports): array
{
	return array_values(array_filter(
		$allImports,
		static fn (array $import): bool => $import['imported'] === 'FastyBird\\Core'
			|| str_starts_with($import['imported'], 'FastyBird\\Core\\'),
	));
}

/**
 * Groups $fqcns (one kind's worth of a file's imports, duplicates allowed) by the lower-cased
 * last segment, and keeps only the groups holding two or more DISTINCT names -- a name imported
 * twice under two different local bindings is not a collision with itself.
 *
 * @param array<string> $fqcns
 *
 * @return array<string, array<string>> lower-cased short name => distinct FQCNs
 */
function fbGroupCollisions(array $fqcns): array
{
	$byShortName = [];

	foreach ($fqcns as $fqcn) {
		$short = strtolower(fbLastSegments($fqcn, 1));
		$byShortName[$short][$fqcn] = true;
	}

	$groups = [];

	foreach ($byShortName as $short => $members) {
		if (count($members) >= 2) {
			$groups[$short] = array_keys($members);
		}
	}

	return $groups;
}

/**
 * The last $count segments of a qualified name, joined without a separator -- the shape a
 * legal alias takes beyond a bare import.
 */
function fbLastSegments(string $name, int $count): string
{
	return implode('', array_slice(explode('\\', $name), -$count));
}

/**
 * Whether some OTHER name among $siblings reduces to the same string as $name does at
 * $level segments -- i.e. whether a plain $level-segment alias of $name would collide.
 *
 * @param array<string> $siblings
 */
function fbHasSiblingCollision(string $name, array $siblings, int $level): bool
{
	$target = fbLastSegments($name, $level);

	foreach ($siblings as $sibling) {
		if ($sibling !== $name && fbLastSegments($sibling, $level) === $target) {
			return true;
		}
	}

	return false;
}

/**
 * The alias tools/check-naming.php requires for $name: the last two segments joined, UNLESS
 * that would collide with another same-kind Core import in the same file also reducing to it
 * there ($siblings, every OTHER Core import's FQCN of that kind) -- then the last three, and
 * so on, one segment at a time, for as long as the collision persists or the name runs out of
 * segments. This is the read side of the symmetric rule tools/move-core-symbols.php's alias
 * assignment applies on the write side: two imports whose k-segment forms would be equal both
 * escalate to k+1, never just one of them, so this climbs past any level still shared with a
 * sibling and stops the moment it reaches one that is not.
 *
 * @param array<string> $siblings
 */
function fbExpectedAlias(string $name, array $siblings): string
{
	$max = count(explode('\\', $name));
	$level = 2;

	while ($level < $max && fbHasSiblingCollision($name, $siblings, $level)) {
		$level++;
	}

	return fbLastSegments($name, $level);
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

	// Every top-level `use` import in the file, all kinds, Core and non-Core alike -- shared by
	// checks 3 and 4 so the two can never disagree about which imports exist or what any one of
	// them is expected to be aliased as (see "SIBLINGS" in the file docblock).
	$boundary = fbFindImportBoundary($code);
	$allImports = fbFindAllImports($code, $boundary);

	$importsByKind = [];

	foreach ($allImports as $import) {
		$importsByKind[$import['kind']][] = $import['imported'];
	}

	// 3. Import aliases of a FastyBird\Core symbol, everywhere in the repository.
	//
	// The "expected" value is the last-two-segments rule (extended, see the file docblock, to
	// climb past a segment level a same-kind sibling import in this file also reduces to), and
	// it is only a meaningful SUGGESTION for a class-shaped import (class/interface/trait/
	// enum), where every segment is a namespace part. For `use function`/`use const` the final
	// segment is a symbol name, not a namespace part -- e.g. `use function FastyBird\Core\
	// Helpers\format as X;` computes "expected Helpersformat", which is not a name anyone
	// should actually use. The violation itself still fails closed and is real signal (the
	// alias genuinely does not match the convention), so this is not a hole -- just do not
	// treat the printed "expected" text as an authoritative replacement for a function or
	// const alias; pick one by hand instead.
	//
	// Siblings are every same-kind import of the file (see above), not just the other Core
	// ones: since #541, a Core import can be forced to escalate past two segments by a
	// non-Core sibling that reduces to the same text (`FastyBird\Core\DI` alongside
	// `Nette\DI`), and check 4 below computes the very same expected value for that import, so
	// the two must start from the same sibling list or they could reach different answers.
	$fileCoreImports = fbFilterCoreImports($allImports);

	// Tracks which (kind, imported) pairs this loop already reported, so check 4 does not
	// repeat the same finding under the `import` category.
	$check3Flagged = [];

	foreach ($fileCoreImports as $import) {
		if ($import['alias'] === null) {
			continue;
		}

		$imported = $import['imported'];
		$alias = $import['alias'];
		$expected = fbExpectedAlias($imported, $importsByKind[$import['kind']]);

		if ($alias !== $expected) {
			$violations[] = sprintf(
				"alias\t%s\t%s as %s (expected %s)",
				$relative,
				$imported,
				$alias,
				$expected,
			);

			$check3Flagged[$import['kind'] . ':' . $imported] = true;
		}
	}

	// 4. Every import collision group, repository-wide (#541). See the file docblock for the
	// rule; this is its implementation.
	foreach ($importsByKind as $kind => $fqcns) {
		$groups = fbGroupCollisions($fqcns);

		if ($groups === []) {
			continue;
		}

		foreach ($allImports as $import) {
			if ($import['kind'] !== $kind) {
				continue;
			}

			$imported = $import['imported'];
			$short = strtolower(fbLastSegments($imported, 1));

			if (!isset($groups[$short])) {
				// Not part of any collision group in this file -- out of scope, e.g. a
				// standalone `use Doctrine\ORM\Mapping as ORM;` with no colliding sibling.
				continue;
			}

			if (isset($check3Flagged[$kind . ':' . $imported])) {
				// Already reported by check 3 above -- same import, same underlying problem.
				continue;
			}

			$alias = $import['alias'];
			$isSingleSegment = !str_contains($imported, '\\');

			if ($isSingleSegment) {
				// No two-segment form exists (`Casbin`, `Monolog`, `Exception`) -- it must
				// stay bare even though it collides; its siblings still take their aliases.
				if ($alias !== null) {
					$violations[] = sprintf(
						"import\t%s\t%s as %s (expected bare)",
						$relative,
						$imported,
						$alias,
					);
				}

				continue;
			}

			$expected = FB_ALIAS_EXCEPTIONS[$imported] ?? fbExpectedAlias($imported, $fqcns);

			if ($alias === null) {
				$violations[] = sprintf(
					"import\t%s\t%s bare (expected %s)",
					$relative,
					$imported,
					$expected,
				);
			} elseif ($alias !== $expected) {
				$violations[] = sprintf(
					"import\t%s\t%s as %s (expected %s)",
					$relative,
					$imported,
					$alias,
					$expected,
				);
			}
		}
	}

	return $violations;
}

$files = fbCollectFiles($repoRoot);
$violations = [];
$coreImports = 0;
$allImportLines = 0;

foreach ($files as $path) {
	$code = file_get_contents($path);

	if ($code === false) {
		fbFail(sprintf('could not read "%s"', $path));
	}

	$relative = substr($path, strlen($repoRoot) + 1);
	$coreImports += preg_match_all('/^[ \t]*use\s+(?:(?:function|const)\s+)?\\\\?FastyBird\\\\Core\b/m', $code);
	// Independent of fbFindAllImports() on purpose (see the comment below) -- a plain count of
	// every line that opens a `use` import, closures excluded, with no boundary/trait
	// filtering. #541's check 4 self-check floor.
	$allImportLines += preg_match_all('/^[ \t]*use\s+(?!\()(?:(?:function|const)\s+)?\S/m', $code);

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

// #541: same reasoning, for every import checks 3 and 4 now share, not just the Core ones.
if ($allImportLines < 20_000) {
	fbFail(sprintf('found only %d total import lines; expected at least 20000', $allImportLines));
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
