<?php declare(strict_types = 1);

/**
 * Moves fastybird/miniserver-core types into their capability and rewrites every reference
 * to them, from a committed move map. Epic E3 of the Core identity refactor (#458 §3.3).
 *
 * USAGE
 *
 *   php tools/move-core-symbols.php tools/core-moves/NN-<capability>.php
 *       Applies the map, then prints the stale-reference report.
 *
 *   php tools/move-core-symbols.php --report-only <map>...
 *       Prints the stale-reference report only. Exit 1 when it is not empty.
 *
 *   php tools/move-core-symbols.php --verify-baselines=<git-ref> <map>...
 *       Applies the inverse map to both PHPStan baselines and compares them, as multisets of
 *       entries, with the baselines at <git-ref>. Exit 1 on any added or lost entry.
 *
 * Several maps may be given; they are merged. Run it on the host (it needs `git`); it is
 * plain PHP with no dependency on vendor/.
 *
 * THE MAP (tools/core-moves/NN-<capability>.php) returns an array with four keys, all
 * required, any of them empty:
 *
 *   'classes'    => [oldFqcn => newFqcn]  A class, interface, trait or enum of Core. Its file
 *                                         moves with it (PSR-4, from Core's composer.json,
 *                                         autoload and autoload-dev), its `namespace` line
 *                                         and, when the short name changes, its declared name
 *                                         are rewritten, and so is every reference to it.
 *   'normalize'  => [namespace, ...]     A namespace that does not move but belongs to the
 *                                         capability: every import of it with an illegal alias
 *                                         is re-aliased to the legal one, duplicates merge.
 *   'namespaces' => [oldNs => newNs]     A namespace named in a string or a config file
 *                                         rather than through a class (e.g. an ORM mapping
 *                                         namespace). Applied to string literals and to
 *                                         NEON/XML/JSON only; code is rewritten per class.
 *   'files'      => [oldPath => newPath] A repository-relative file move that is not a class
 *                                         move -- the file keeps whatever it declares.
 *
 * WHAT IS REWRITTEN
 *
 *   PHP, in every tracked file `make naming` scans (src/FastyBird, public, bin, tests,
 *   migrations) plus tools/, except this tool and its maps:
 *     - code tokens, resolved through the file's imports and namespace;
 *     - docblock types, in the type position of these tags only: `@var`, `@param`,
 *       `@return`, `@throws`, `@property*`, `@method`, `@template*`, `@extends`,
 *       `@implements`, `@mixin`, `@see`, `@phpstan-*` and `@psalm-*` (not
 *       PHPStan's ignore directives). Never another tag, never a description;
 *     - string literals naming a moved class, in both `\` and `\\` escaping.
 *   NEON, XML and JSON: class and namespace names in every escaping, and the old path of a
 *   moved file -- which covers the `message:` and `path:` of both PHPStan baselines.
 *
 *   A `@Secured` annotation line is never modified; the tool verifies that per file and
 *   aborts rather than write one (#458 §1.5: those lines ARE the authorization rules). This
 *   file never spells that annotation with its backslash, so the repository's byte-identity
 *   check over those lines (`git grep` of the annotation) does not count this tool.
 *
 * HOW A REFERENCE IS WRITTEN
 *
 *   Through a namespace import, the way this codebase imports (`use FastyBird\Core\Clock;`
 *   ... `Clock\SystemClock`): an existing import of the target namespace is reused; an
 *   import that would name the file's own namespace is not added (the name is written
 *   relative instead, which is what UseFromSameNamespace wants); a fully qualified name stays
 *   fully qualified. A new import is bare unless its short name collides in that file --
 *   with another import, a declared type or a relative name -- and then it takes the last two
 *   segments of the namespace joined (tools/check-naming.php's rule). When two imports share
 *   a short name, BOTH take the two-segment alias: a file never ends up with one of them bare
 *   and the other aliased. Imports whose every use was rewritten away are dropped.
 *
 *   An import that only a docblock still names outside a rewritable type (prose, an unlisted
 *   tag) after the rewrite, where that mention names a moved type, is never left behind as it
 *   was: it is retargeted when that is exact -- it imports a moved class whose short name does
 *   not change and whose alias stays legal -- and otherwise the tool stops, naming each line.
 *
 * GUARANTEES
 *
 *   All or nothing: every rewrite is computed in memory from the untouched tree first, and
 *   every condition the tool refuses is detected there; only then are files moved and written.
 *   A failed run leaves the tree exactly as it was. Deterministic: files are processed in
 *   `git ls-files` order and nothing depends on the environment. Idempotent: a second run finds
 *   nothing to do. Afterwards `make csf` should only re-sort imports; anything else it changes
 *   is a bug in this tool.
 *
 * THE STALE-REFERENCE REPORT lists every remaining occurrence, in any tracked file outside
 * docs/superpowers/, CHANGELOG.md and tools/core-moves/ (the maps name the old classes by
 * design), of an old FQCN, an old namespace no class is left in, an old file path, or -- in
 * the PHP files the tool rewrites -- a name, or a docblock mention outside a rewritable type,
 * that still resolves to one of them. For a namespace the map normalizes, it lists every
 * import still carrying an alias tools/check-naming.php would reject. A map that moves
 * nothing is checked for exactly that and nothing else.
 *
 * Exit codes follow tools/check-naming.php: 0 done / clean, 1 a finding, 2 the tool failed.
 *
 * Deleted with the maps in E8 (#463).
 */

const FB_MOVE_CORE_PACKAGE = 'src/FastyBird/Core/Core/';

const FB_MOVE_PHP_ROOTS = ['src/FastyBird/', 'public/', 'bin/', 'tests/', 'migrations/', 'tools/'];

const FB_MOVE_CONFIG_EXTENSIONS = ['neon', 'xml', 'json'];

const FB_MOVE_OWN_FILES = ['tools/move-core-symbols.php', 'tools/core-moves/'];

const FB_MOVE_REPORT_EXCLUDED = ['docs/superpowers/', 'tools/core-moves/'];

/**
 * Pseudo-types and reserved words that can never be an imported class, in code or a docblock.
 */
const FB_MOVE_BUILTINS = [
	'array', 'bool', 'boolean', 'callable', 'double', 'false', 'float', 'int', 'integer',
	'iterable', 'list', 'mixed', 'never', 'null', 'numeric', 'object', 'parent', 'resource',
	'scalar', 'self', 'static', 'string', 'true', 'void',
];

function fbMoveFail(string $message): never
{
	fwrite(STDERR, 'move-core-symbols: ' . $message . PHP_EOL);

	exit(2);
}

/**
 * The authorization annotation with its backslash, built rather than spelled: the repository's
 * byte-identity check greps every PHP file for that text, and this file must not add to it.
 */
function fbMoveSecuredMarker(): string
{
	return '@Secured' . chr(92);
}

/**
 * @param list<string> $command
 */
function fbMoveRun(array $command, string $cwd): string
{
	$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);

	if (!is_resource($process)) {
		fbMoveFail('could not run ' . implode(' ', $command));
	}

	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	if (proc_close($process) !== 0 || $stdout === false) {
		fbMoveFail(sprintf('`%s` failed: %s', implode(' ', $command), trim((string) $stderr)));
	}

	return $stdout;
}

/**
 * @return list<string>
 */
function fbMoveTrackedFiles(string $root): array
{
	$files = array_values(array_filter(
		explode("\0", fbMoveRun(['git', 'ls-files', '-z'], $root)),
		static fn (string $file): bool => $file !== '' && is_file($root . '/' . $file),
	));

	sort($files, SORT_STRING);

	return $files;
}

function fbMoveRead(string $path): string
{
	$content = file_get_contents($path);

	if ($content === false) {
		fbMoveFail(sprintf('could not read "%s"', $path));
	}

	return $content;
}

function fbMoveWrite(string $path, string $content): void
{
	if (file_put_contents($path, $content) === false) {
		fbMoveFail(sprintf('could not write "%s"', $path));
	}
}

function fbMoveIsFqcn(string $name): bool
{
	return preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+$/', $name) === 1;
}

function fbMoveNamespaceOf(string $name): string
{
	$pos = strrpos($name, '\\');

	return $pos === false ? '' : substr($name, 0, $pos);
}

function fbMoveShortOf(string $name): string
{
	$pos = strrpos($name, '\\');

	return $pos === false ? $name : substr($name, $pos + 1);
}

/**
 * The alias tools/check-naming.php accepts: the last two segments of the imported name.
 */
function fbMoveTwoSegmentAlias(string $name): string
{
	return implode('', array_slice(explode('\\', $name), -2));
}

/**
 * Whether tools/check-naming.php accepts an import's alias: none at all, or exactly the last
 * two segments of the imported name joined.
 */
function fbMoveAliasIsLegal(string $name, string $alias, bool $explicit): bool
{
	return !$explicit || $alias === fbMoveTwoSegmentAlias($name);
}

/**
 * Slevomat's AlphabeticallySortedUses order, case-insensitive, segment by segment.
 */
function fbMoveCompareNames(string $a, string $b): int
{
	$aParts = explode('\\', $a);
	$bParts = explode('\\', $b);

	for ($i = 0; $i < min(count($aParts), count($bParts)); $i++) {
		$comparison = strcasecmp($aParts[$i], $bParts[$i]);

		if ($comparison !== 0) {
			return $comparison;
		}
	}

	return count($aParts) <=> count($bParts);
}

function fbMoveLongestFirst(string $a, string $b): int
{
	$byLength = strlen($b) <=> strlen($a);

	return $byLength !== 0 ? $byLength : strcmp($a, $b);
}

function fbMoveHasPrefix(string $path, string $prefix): bool
{
	return str_ends_with($prefix, '/') ? str_starts_with($path, $prefix) : $path === $prefix;
}

/**
 * @param list<string> $prefixes
 */
function fbMoveHasAnyPrefix(string $path, array $prefixes): bool
{
	foreach ($prefixes as $prefix) {
		if (fbMoveHasPrefix($path, $prefix)) {
			return true;
		}
	}

	return false;
}

/**
 * @param list<string> $paths
 *
 * @return array{
 *     classes: array<string, string>,
 *     normalize: list<string>,
 *     namespaces: array<string, string>,
 *     files: array<string, string>,
 * }
 */
function fbMoveLoadMaps(array $paths): array
{
	$merged = ['classes' => [], 'normalize' => [], 'namespaces' => [], 'files' => []];

	foreach ($paths as $path) {
		if (!is_file($path)) {
			fbMoveFail(sprintf('map "%s" does not exist', $path));
		}

		$map = require $path;

		if (!is_array($map) || array_keys($map) !== ['classes', 'normalize', 'namespaces', 'files']) {
			fbMoveFail(sprintf('map "%s" must return exactly: classes, normalize, namespaces, files', $path));
		}

		foreach (['classes', 'namespaces', 'files'] as $key) {
			if (!is_array($map[$key])) {
				fbMoveFail(sprintf('map "%s": "%s" must be an array', $path, $key));
			}

			foreach ($map[$key] as $from => $to) {
				if (!is_string($from) || !is_string($to) || $from === $to) {
					fbMoveFail(sprintf('map "%s": "%s" must map a string to a different string', $path, $key));
				}

				if ($key !== 'files' && (!fbMoveIsFqcn($from) || !fbMoveIsFqcn($to))) {
					fbMoveFail(sprintf('map "%s": "%s" is not a qualified name pair', $path, $from));
				}

				if (isset($merged[$key][$from])) {
					fbMoveFail(sprintf('"%s" is mapped twice', $from));
				}

				$merged[$key][$from] = $to;
			}
		}

		if (!is_array($map['normalize']) || !array_is_list($map['normalize'])) {
			fbMoveFail(sprintf('map "%s": "normalize" must be a list', $path));
		}

		foreach ($map['normalize'] as $namespace) {
			if (!is_string($namespace) || !fbMoveIsFqcn($namespace)) {
				fbMoveFail(sprintf('map "%s": "normalize" holds a non-namespace', $path));
			}

			$merged['normalize'][] = $namespace;
		}
	}

	foreach (['classes', 'namespaces'] as $key) {
		$lowerSources = array_map(strtolower(...), array_keys($merged[$key]));
		$lowerTargets = array_map(strtolower(...), array_values($merged[$key]));

		if (count(array_unique($lowerTargets)) !== count($lowerTargets)) {
			fbMoveFail(sprintf('two "%s" entries map to the same target', $key));
		}

		if (array_intersect($lowerSources, $lowerTargets) !== []) {
			fbMoveFail(sprintf('a "%s" target is also a source; chains are not supported', $key));
		}
	}

	return $merged;
}

/**
 * Core's PSR-4 roots, longest prefix first: namespace prefix (with trailing `\`) => directory
 * relative to the repository root (with trailing `/`).
 *
 * @return array<string, string>
 */
function fbMovePsr4(string $root): array
{
	$composer = json_decode(fbMoveRead($root . '/' . FB_MOVE_CORE_PACKAGE . 'composer.json'), true);

	if (!is_array($composer)) {
		fbMoveFail('could not parse Core\'s composer.json');
	}

	$roots = [];

	foreach (['autoload', 'autoload-dev'] as $section) {
		$psr4 = is_array($composer[$section] ?? null) ? ($composer[$section]['psr-4'] ?? []) : [];

		if (!is_array($psr4)) {
			fbMoveFail(sprintf('Core\'s composer.json "%s.psr-4" is not an object', $section));
		}

		foreach ($psr4 as $prefix => $directory) {
			if (!is_string($prefix) || !is_string($directory)) {
				fbMoveFail('Core\'s composer.json holds a non-string PSR-4 entry');
			}

			$roots[$prefix] = FB_MOVE_CORE_PACKAGE . rtrim($directory, '/') . '/';
		}
	}

	uksort($roots, fbMoveLongestFirst(...));

	return $roots;
}

/**
 * @param array<string, string> $psr4
 */
function fbMoveClassPath(string $fqcn, array $psr4): string
{
	foreach ($psr4 as $prefix => $directory) {
		if (str_starts_with($fqcn, $prefix)) {
			return $directory . str_replace('\\', '/', substr($fqcn, strlen($prefix))) . '.php';
		}
	}

	fbMoveFail(sprintf('"%s" is under no PSR-4 root of Core', $fqcn));
}

/**
 * @return list<PhpToken>
 */
function fbMoveTokens(string $code): array
{
	return array_values(PhpToken::tokenize($code));
}

/**
 * @param list<PhpToken> $tokens
 */
function fbMoveSignificant(array $tokens, int $index, int $step): int
{
	for ($i = $index + $step; $i >= 0 && $i < count($tokens); $i += $step) {
		if (!$tokens[$i]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
			return $i;
		}
	}

	return -1;
}

/**
 * Whether an unqualified T_STRING is in a position where it names a class. Errs towards
 * "yes": a false positive only makes a name reserved (so a colliding import is aliased) or
 * resolves to nothing and is ignored.
 *
 * @param list<PhpToken> $tokens
 */
function fbMoveIsClassPosition(array $tokens, int $index, bool $attributeTop): bool
{
	if (in_array(strtolower($tokens[$index]->text), FB_MOVE_BUILTINS, true)) {
		return false;
	}

	$prev = fbMoveSignificant($tokens, $index, -1);
	$next = fbMoveSignificant($tokens, $index, 1);
	$prevToken = $prev >= 0 ? $tokens[$prev] : null;
	$nextToken = $next >= 0 ? $tokens[$next] : null;

	if ($prevToken !== null && $prevToken->is([
		T_OBJECT_OPERATOR,
		T_NULLSAFE_OBJECT_OPERATOR,
		T_DOUBLE_COLON,
		T_FUNCTION,
		T_CONST,
		T_CLASS,
		T_INTERFACE,
		T_TRAIT,
		T_ENUM,
		T_NAMESPACE,
		T_GOTO,
		T_AS,
	])) {
		return false;
	}

	if ($prevToken?->is(T_CASE) === true && $nextToken?->is(T_DOUBLE_COLON) !== true) {
		return false;
	}

	// A typed class constant's own name (`const TYPE NAME = ...`, PHP 8.3+): the type, if any,
	// sits between T_CONST and this token and is itself still a class position (resolved on its
	// own pass through this same function); only the token directly followed by `=` is the
	// constant's name, and it is never a class reference, however many letters it happens to
	// share -- case-insensitively -- with a class in scope (e.g. a `PHONE` constant beside a
	// `Phone` class in the same namespace).
	if ($nextToken?->text === '=') {
		for ($i = $index - 1; $i >= 0; $i--) {
			if ($tokens[$i]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
				continue;
			}

			if (in_array($tokens[$i]->text, [';', '{', '}'], true)) {
				break;
			}

			if ($tokens[$i]->is(T_CONST)) {
				return false;
			}
		}
	}

	if ($nextToken?->text === '(') {
		return $prevToken?->is(T_NEW) === true || $attributeTop;
	}

	// not a named argument, nor a goto label
	return $nextToken?->text !== ':' || !in_array($prevToken?->text, ['(', ',', ';', '{', '}'], true);
}

/**
 * Byte ranges of a docblock that hold a TYPE of an allowed tag, never a description and never
 * a `@Secured` annotation line. The third element marks an @method range, whose method name
 * must not be read as a type.
 *
 * @return list<array{0: int, 1: int, 2: bool}>
 */
function fbMoveDocTypeRanges(string $doc): array
{
	$ranges = [];

	preg_match_all(
		'/^([ \t]*(?:\/\*\*|\*)?[ \t]*)@([A-Za-z][A-Za-z0-9_\-]*)(?=[\s(]|$)/m',
		$doc,
		$matches,
		PREG_OFFSET_CAPTURE | PREG_SET_ORDER,
	);

	foreach ($matches as $match) {
		$tag = $match[2][0];
		$contentStart = $match[2][1] + strlen($tag);
		$lineEnd = strpos($doc, "\n", $contentStart);
		$line = substr($doc, $match[0][1], ($lineEnd === false ? strlen($doc) : $lineEnd) - $match[0][1]);

		if (str_contains($line, fbMoveSecuredMarker()) || !fbMoveIsTypeTag($tag)) {
			continue;
		}

		$base = preg_replace('/^(?:phpstan|psalm)-/', '', $tag) ?? $tag;
		$position = $contentStart;

		if ($base === 'method') {
			$ranges[] = [$contentStart, fbMoveDocMethodEnd($doc, $contentStart), true];

			continue;
		}

		if ($base === 'import-type') {
			$ranges[] = [$contentStart, $lineEnd === false ? strlen($doc) : $lineEnd, false];

			continue;
		}

		if (
			str_starts_with($base, 'template')
			&& !in_array($base, ['template-extends', 'template-implements', 'template-use'], true)
		) {
			// `@template T of Foo = Bar`: skip the parameter name, then the bound
			$position = fbMoveDocSkipWord($doc, $position);
			$position = fbMoveDocSkipKeyword($doc, $position, ['of', 'as', 'super']);
		} elseif ($base === 'type') {
			$position = fbMoveDocSkipWord($doc, $position);
			$position = fbMoveDocSkipKeyword($doc, $position, ['=']);
		} elseif (in_array($base, ['var', 'param', 'param-out', 'property', 'property-read', 'property-write'], true)) {
			// `@var $x Foo` is legal too
			$afterSpace = fbMoveDocSkipSpace($doc, $position);

			if (($doc[$afterSpace] ?? '') === '$') {
				$position = fbMoveDocSkipWord($doc, $position);
			}
		}

		$end = fbMoveDocTypeEnd($doc, $position);
		$ranges[] = [$position, $end, false];

		if (str_starts_with($base, 'template')) {
			$afterDefault = fbMoveDocSkipKeyword($doc, $end, ['=']);

			if ($afterDefault !== $end) {
				$ranges[] = [$afterDefault, fbMoveDocTypeEnd($doc, $afterDefault), false];
			}
		}
	}

	return $ranges;
}

function fbMoveIsTypeTag(string $tag): bool
{
	if (str_starts_with($tag, 'phpstan-ignore') || $tag === 'psalm-suppress') {
		// an error identifier, not a type
		return false;
	}

	if (str_starts_with($tag, 'phpstan-') || str_starts_with($tag, 'psalm-')) {
		return true;
	}

	return in_array($tag, ['var', 'param', 'return', 'throws', 'method', 'extends', 'implements', 'mixin', 'see'], true)
		|| str_starts_with($tag, 'property')
		|| str_starts_with($tag, 'template');
}

function fbMoveDocSkipSpace(string $doc, int $position): int
{
	while ($position < strlen($doc) && ($doc[$position] === ' ' || $doc[$position] === "\t")) {
		$position++;
	}

	return $position;
}

function fbMoveDocSkipWord(string $doc, int $position): int
{
	$position = fbMoveDocSkipSpace($doc, $position);

	while ($position < strlen($doc) && preg_match('/[\s]/', $doc[$position]) !== 1) {
		$position++;
	}

	return $position;
}

/**
 * @param list<string> $keywords
 */
function fbMoveDocSkipKeyword(string $doc, int $position, array $keywords): int
{
	$start = fbMoveDocSkipSpace($doc, $position);

	foreach ($keywords as $keyword) {
		if (substr($doc, $start, strlen($keyword)) === $keyword) {
			$after = $start + strlen($keyword);

			if ($keyword === '=' || preg_match('/\s/', $doc[$after] ?? ' ') === 1) {
				return $after;
			}
		}
	}

	return $position;
}

/**
 * End of the type expression starting at $position: the first whitespace outside brackets
 * that is not part of a `|`, `&` or callable-return `:` continuation. A type may span lines
 * inside brackets, but never into the next tag or past the end of the comment.
 */
function fbMoveDocTypeEnd(string $doc, int $position): int
{
	$position = fbMoveDocSkipSpace($doc, $position);
	$depth = 0;
	$length = strlen($doc);
	$last = '';

	for ($i = $position; $i < $length; $i++) {
		$char = $doc[$i];

		if ($char === '*' && ($doc[$i + 1] ?? '') === '/') {
			return $i;
		}

		if ($char === "\n") {
			if ($depth === 0) {
				return $i;
			}

			if (preg_match('/\G\n[ \t]*\*?[ \t]*(?:@|\/)/', $doc, $unused, 0, $i) === 1) {
				return $i;
			}

			continue;
		}

		if (in_array($char, ['<', '(', '{', '['], true)) {
			$depth++;
		} elseif (in_array($char, ['>', ')', '}', ']'], true)) {
			if ($depth === 0) {
				return $i;
			}

			$depth--;
		} elseif (($char === ' ' || $char === "\t") && $depth === 0) {
			$next = fbMoveDocSkipSpace($doc, $i);
			$nextChar = $doc[$next] ?? '';

			if (!in_array($last, ['|', '&', ':'], true) && !in_array($nextChar, ['|', '&'], true)) {
				return $i;
			}

			$i = $next - 1;

			continue;
		}

		$last = $char;
	}

	return $length;
}

/**
 * `@method [static] ReturnType name(Params)`: the range runs to the end of the parameter list.
 */
function fbMoveDocMethodEnd(string $doc, int $position): int
{
	$open = strpos($doc, '(', $position);
	$lineEnd = strpos($doc, "\n", $position);

	if ($open === false || ($lineEnd !== false && $open > $lineEnd)) {
		return $lineEnd === false ? strlen($doc) : $lineEnd;
	}

	$depth = 0;

	for ($i = $open; $i < strlen($doc); $i++) {
		if ($doc[$i] === '(') {
			$depth++;
		} elseif ($doc[$i] === ')') {
			$depth--;

			if ($depth === 0) {
				return $i + 1;
			}
		} elseif ($doc[$i] === "\n") {
			return $i;
		}
	}

	return strlen($doc);
}

/**
 * Names in the type ranges of a docblock: [offset in the docblock, name].
 *
 * @param list<array{0: int, 1: int, 2: bool}> $ranges
 *
 * @return list<array{0: int, 1: string}>
 */
function fbMoveDocNames(string $doc, array $ranges): array
{
	$names = [];

	foreach ($ranges as [$start, $end, $isMethod]) {
		$text = substr($doc, $start, $end - $start);

		preg_match_all(
			'/(?<![A-Za-z0-9_\\\\$\-])(?<!::)\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*(?![A-Za-z0-9_\\\\\-])/',
			$text,
			$matches,
			PREG_OFFSET_CAPTURE,
		);

		$methodName = -1;

		if ($isMethod) {
			// the method name is the last name before the parameter list's `(`
			$paren = strpos($text, '(');

			foreach ($matches[0] as $index => $match) {
				if ($paren !== false && $match[1] < $paren) {
					$methodName = $index;
				}
			}
		}

		foreach ($matches[0] as $index => $match) {
			if ($index === $methodName) {
				continue;
			}

			if (!str_contains($match[0], '\\') && in_array($match[0], FB_MOVE_BUILTINS, true)) {
				continue;
			}

			$names[] = [$start + $match[1], $match[0]];
		}
	}

	return $names;
}

/**
 * Everything the rewriter needs to know about one PHP file.
 *
 * @return array{
 *     namespace: string,
 *     namespaceCount: int,
 *     braced: bool,
 *     namespaceStart: int,
 *     namespaceText: string,
 *     namespaceEnd: int,
 *     imports: list<array{kind: string, name: string, alias: string, start: int, end: int, lineStart: int, lineEnd: int, clean: bool, explicit: bool}>,
 *     types: list<array{name: string, start: int}>,
 *     refs: list<array{start: int, text: string, doc: bool, unqualified: bool}>,
 *     strings: list<array{start: int, text: string}>,
 *     docs: list<array{start: int, text: string, ranges: list<array{0: int, 1: int, 2: bool}>}>,
 * }
 */
function fbMoveAnalyse(string $code): array
{
	$tokens = fbMoveTokens($code);
	$result = [
		'namespace' => '',
		'namespaceCount' => 0,
		'braced' => false,
		'namespaceStart' => -1,
		'namespaceText' => '',
		'namespaceEnd' => -1,
		'imports' => [],
		'types' => [],
		'refs' => [],
		'strings' => [],
		'docs' => [],
	];

	$depth = 0;
	$attributeDepth = -1;
	$attributeParens = 0;
	$count = count($tokens);

	for ($i = 0; $i < $count; $i++) {
		$token = $tokens[$i];

		if ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
			$depth++;

			continue;
		}

		if ($token->text === '}') {
			$depth--;

			continue;
		}

		if ($token->is(T_ATTRIBUTE)) {
			$attributeDepth = 1;
			$attributeParens = 0;

			continue;
		}

		if ($attributeDepth > 0) {
			if ($token->text === '[') {
				$attributeDepth++;
			} elseif ($token->text === ']') {
				$attributeDepth--;
			} elseif ($token->text === '(') {
				$attributeParens++;
			} elseif ($token->text === ')') {
				$attributeParens--;
			}
		}

		if ($token->is(T_NAMESPACE)) {
			$next = fbMoveSignificant($tokens, $i, 1);

			if ($next >= 0 && $tokens[$next]->is([T_STRING, T_NAME_QUALIFIED])) {
				$result['namespaceCount']++;
				$result['namespace'] = $tokens[$next]->text;
				$result['namespaceStart'] = $tokens[$next]->pos;
				$result['namespaceText'] = $tokens[$next]->text;
				$after = fbMoveSignificant($tokens, $next, 1);
				$result['namespaceEnd'] = $after >= 0 ? $tokens[$after]->pos + strlen($tokens[$after]->text) : -1;

				if ($after >= 0 && $tokens[$after]->text === '{') {
					// braced namespaces hold their imports inside the brace; unsupported
					$result['braced'] = true;
				}

				$i = $next;
			}

			continue;
		}

		if ($token->is(T_USE) && $depth === 0) {
			$next = fbMoveSignificant($tokens, $i, 1);

			if ($next >= 0 && $tokens[$next]->text === '(') {
				continue; // a closure's `use (...)`
			}

			$i = fbMoveParseUse($code, $tokens, $i, $result['imports']);

			continue;
		}

		if ($token->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
			$prev = fbMoveSignificant($tokens, $i, -1);
			$next = fbMoveSignificant($tokens, $i, 1);

			if (
				$next >= 0
				&& $tokens[$next]->is(T_STRING)
				&& ($prev < 0 || !$tokens[$prev]->is([T_DOUBLE_COLON, T_NEW]))
			) {
				$result['types'][] = ['name' => $tokens[$next]->text, 'start' => $tokens[$next]->pos];
				$i = $next;
			}

			continue;
		}

		if ($token->is([T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE])) {
			$result['refs'][] = ['start' => $token->pos, 'text' => $token->text, 'doc' => false, 'unqualified' => false];

			continue;
		}

		if ($token->is(T_STRING)) {
			$attributeTop = $attributeDepth === 1 && $attributeParens === 0;

			if (fbMoveIsClassPosition($tokens, $i, $attributeTop)) {
				$result['refs'][] = ['start' => $token->pos, 'text' => $token->text, 'doc' => false, 'unqualified' => true];
			}

			continue;
		}

		if ($token->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE])) {
			$result['strings'][] = ['start' => $token->pos, 'text' => $token->text];

			continue;
		}

		if ($token->is(T_DOC_COMMENT)) {
			$ranges = fbMoveDocTypeRanges($token->text);
			$result['docs'][] = ['start' => $token->pos, 'text' => $token->text, 'ranges' => $ranges];

			foreach (fbMoveDocNames($token->text, $ranges) as [$offset, $name]) {
				$result['refs'][] = [
					'start' => $token->pos + $offset,
					'text' => $name,
					'doc' => true,
					'unqualified' => !str_contains($name, '\\'),
				];
			}
		}
	}

	return $result;
}

/**
 * Parses one top-level `use` statement starting at token $index and appends its imports.
 * Returns the index of its terminating `;`.
 *
 * @param list<PhpToken> $tokens
 * @param list<array{kind: string, name: string, alias: string, start: int, end: int, lineStart: int, lineEnd: int, clean: bool, explicit: bool}> $imports
 */
function fbMoveParseUse(string $code, array $tokens, int $index, array &$imports): int
{
	$kind = 'class';
	$items = [];
	$current = null;
	$expectAlias = false;
	$grouped = false;
	$prefix = '';
	$end = $index;

	for ($i = $index + 1; $i < count($tokens); $i++) {
		$token = $tokens[$i];

		if ($token->text === ';') {
			$end = $i;

			break;
		}

		if ($token->is(T_FUNCTION)) {
			$kind = 'function';
		} elseif ($token->is(T_CONST)) {
			$kind = 'const';
		} elseif ($token->text === '{') {
			// `use Prefix\{A, B as C}`: what came before the brace is the prefix
			$grouped = true;
			$prefix = $current !== null ? $current['name'] : '';
			$current = null;
		} elseif ($token->is(T_AS)) {
			$expectAlias = true;
		} elseif ($token->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
			if ($expectAlias && $current !== null) {
				$current['alias'] = $token->text;
				$current['explicit'] = true;
				$expectAlias = false;
			} else {
				if ($current !== null) {
					$items[] = $current;
				}

				$name = ($prefix !== '' ? $prefix . '\\' : '') . ltrim($token->text, '\\');
				$current = ['name' => $name, 'alias' => fbMoveShortOf($name), 'explicit' => false];
			}
		}
	}

	if ($current !== null) {
		$items[] = $current;
	}

	$start = $tokens[$index]->pos;
	$stop = $tokens[$end]->pos + 1;
	$lineStart = strrpos(substr($code, 0, $start), "\n");
	$lineStart = $lineStart === false ? 0 : $lineStart + 1;
	$lineEnd = strpos($code, "\n", $stop);
	$lineEnd = $lineEnd === false ? strlen($code) : $lineEnd + 1;
	$clean = !$grouped
		&& count($items) === 1
		&& trim(substr($code, $lineStart, $start - $lineStart)) === ''
		&& trim(substr($code, $stop, $lineEnd - $stop)) === '';

	foreach ($items as $item) {
		$imports[] = [
			// a grouped class import is resolved through, but never rewritten
			'kind' => $grouped && $kind === 'class' ? 'group' : $kind,
			'name' => $item['name'],
			'alias' => $item['alias'],
			'start' => $start,
			'end' => $stop,
			'lineStart' => $lineStart,
			'lineEnd' => $lineEnd,
			'clean' => $clean,
			'explicit' => $item['explicit'],
		];
	}

	return $end;
}

/**
 * @param list<array{kind: string, name: string, alias: string, start: int, end: int, lineStart: int, lineEnd: int, clean: bool, explicit: bool}> $imports
 *
 * @return array<string, int>
 */
function fbMoveAliasIndex(array $imports): array
{
	$index = [];

	foreach ($imports as $key => $import) {
		if ($import['kind'] === 'class' || $import['kind'] === 'group') {
			$index[strtolower($import['alias'])] ??= $key;
		}
	}

	return $index;
}

/**
 * Resolves a name as PHP would for a class: fully qualified, through an import, or relative
 * to the namespace.
 *
 * @param list<array{kind: string, name: string, alias: string, start: int, end: int, lineStart: int, lineEnd: int, clean: bool, explicit: bool}> $imports
 * @param array<string, int> $aliasIndex
 *
 * @return array{fqcn: string, import: int, rest: string, fq: bool}
 */
function fbMoveResolve(string $text, string $namespace, array $imports, array $aliasIndex): array
{
	if (str_starts_with($text, '\\')) {
		return ['fqcn' => substr($text, 1), 'import' => -1, 'rest' => '', 'fq' => true];
	}

	if (str_starts_with(strtolower($text), 'namespace\\')) {
		$relative = substr($text, strlen('namespace\\'));

		return ['fqcn' => ($namespace !== '' ? $namespace . '\\' : '') . $relative, 'import' => -1, 'rest' => $text, 'fq' => false];
	}

	$position = strpos($text, '\\');
	$first = $position === false ? $text : substr($text, 0, $position);
	$rest = $position === false ? '' : substr($text, $position + 1);
	$key = strtolower($first);

	if (isset($aliasIndex[$key])) {
		$import = $imports[$aliasIndex[$key]];

		return [
			'fqcn' => $import['name'] . ($rest !== '' ? '\\' . $rest : ''),
			'import' => $aliasIndex[$key],
			'rest' => $rest,
			'fq' => false,
		];
	}

	return ['fqcn' => ($namespace !== '' ? $namespace . '\\' : '') . $text, 'import' => -1, 'rest' => $text, 'fq' => false];
}

/**
 * Rewrites one PHP file. $move is [oldFqcn, newFqcn] when the file is the one a moved class
 * is declared in.
 *
 * @param array{0: string, 1: string}|null $move
 * @param array<string, string> $classes lowercase old FQCN => new FQCN
 * @param array<string, true> $known lowercase FQCNs of every Core type before the move
 * @param array<string, true> $normalize lowercase namespaces to normalize
 * @param array<string, true> $vacated lowercase namespaces the map empties
 * @param list<array{0: string, 1: string, 2: bool}> $stringMap
 */
function fbMoveRewritePhp(
	string $path,
	string $code,
	array|null $move,
	array $classes,
	array $known,
	array $normalize,
	array $vacated,
	array $stringMap,
): string
{
	$analysis = fbMoveAnalyse($code);
	$imports = $analysis['imports'];
	$aliasIndex = fbMoveAliasIndex($imports);
	$oldNamespace = $analysis['namespace'];
	$newNamespace = $move !== null ? fbMoveNamespaceOf($move[1]) : $oldNamespace;
	$moved = strcasecmp($oldNamespace, $newNamespace) !== 0;

	if (
		$analysis['braced']
		|| $analysis['namespaceCount'] > 1
		|| (
			$move !== null
			&& $analysis['namespaceCount'] !== 1
		)
	) {
		// None exists in the repository; resolving imports per namespace block is not built.
		fbMoveFail(sprintf('%s: several or braced namespaces are not supported', $path));
	}

	// Lowercase names nothing new may be imported as: declared types, the first segment of every
	// name that stays relative, and the aliases of grouped imports (which are never rewritten).
	$reserved = [];

	foreach ($analysis['types'] as $type) {
		$reserved[strtolower($type['name'])] = true;
	}

	// Per existing import: uses found before the rewrite, uses bound to it after, whether it
	// names the new namespace's own members (a moved file drops those), the import it merges
	// into (normalization), and whether its alias must become legal.
	$before = [];
	$after = [];
	$sameNamespace = [];
	$mergedInto = [];
	$wantLegal = [];

	foreach ($imports as $key => $import) {
		if ($import['kind'] === 'group') {
			$reserved[strtolower($import['alias'])] = true;
		}

		$before[$key] = 0;
		$after[$key] = 0;
		$sameNamespace[$key] = $moved
			&& $import['kind'] === 'class'
			&& strcasecmp(fbMoveNamespaceOf($import['name']), $newNamespace) === 0;
		$mergedInto[$key] = -1;
		$wantLegal[$key] = false;
	}

	// Normalization: an illegal alias of a listed namespace becomes legal; duplicates merge
	// into the first import of that namespace.
	$canonical = [];

	foreach ($imports as $key => $import) {
		$lowerName = strtolower($import['name']);

		if (!isset($normalize[$lowerName])) {
			continue;
		}

		if (
			$import['kind'] === 'group'
			&& !fbMoveAliasIsLegal($import['name'], $import['alias'], $import['explicit'])
		) {
			fbMoveFail(sprintf(
				'%s:%d: a grouped `use` holds an illegal alias of a normalized namespace',
				$path,
				fbMoveLineOf($code, $import['start']),
			));
		}

		if ($import['kind'] !== 'class') {
			continue;
		}

		if (isset($canonical[$lowerName])) {
			$mergedInto[$key] = $canonical[$lowerName];
			$wantLegal[$canonical[$lowerName]] = true;

			continue;
		}

		$canonical[$lowerName] = $key;

		if (!fbMoveAliasIsLegal($import['name'], $import['alias'], $import['explicit'])) {
			$wantLegal[$key] = true;
		}
	}

	$newImports = []; // lowercase namespace => namespace

	// How a reference is written after the rewrite:
	//   ['fq', fqcn, ''] | ['rel', text, ''] | ['imp', import key, rest] | ['new', lowercase ns, rest]
	$express = static function (string $target) use ($sameNamespace, $mergedInto, &$newImports, &$reserved, $imports, $newNamespace): array {
		$targetNamespace = fbMoveNamespaceOf($target);
		$short = fbMoveShortOf($target);

		foreach ([$target, $targetNamespace] as $wanted) {
			// an existing import of the class itself, then of its namespace
			foreach ($imports as $key => $import) {
				if (
					$import['kind'] === 'class'
					&& !$sameNamespace[$key]
					&& strcasecmp($import['name'], $wanted) === 0
				) {
					return ['imp', (string) ($mergedInto[$key] >= 0 ? $mergedInto[$key] : $key), $wanted === $target ? '' : $short];
				}
			}

			if ($wanted === $target && strcasecmp($targetNamespace, $newNamespace) === 0) {
				$reserved[strtolower($short)] = true;

				return ['rel', $short, ''];
			}
		}

		if ($newNamespace !== '' && strcasecmp(fbMoveNamespaceOf($targetNamespace), $newNamespace) === 0) {
			// importing it would be UseFromSameNamespace; the relative name needs no import
			$reserved[strtolower(fbMoveShortOf($targetNamespace))] = true;

			return ['rel', fbMoveShortOf($targetNamespace) . '\\' . $short, ''];
		}

		$newImports[strtolower($targetNamespace)] ??= $targetNamespace;

		return ['new', strtolower($targetNamespace), $short];
	};

	$bindings = []; // ref key => binding
	$origin = []; // ref key => the import it resolved through
	$originalRest = []; // ref key => the text after the import's alias, as it was written

	foreach ($analysis['refs'] as $refKey => $ref) {
		$resolved = fbMoveResolve($ref['text'], $oldNamespace, $imports, $aliasIndex);
		$lowerFqcn = strtolower($resolved['fqcn']);
		$changed = isset($classes[$lowerFqcn]);
		$target = $classes[$lowerFqcn] ?? $resolved['fqcn'];

		if ($resolved['fq']) {
			if ($changed) {
				$bindings[$refKey] = ['fq', $target, ''];
			}

			continue;
		}

		if ($resolved['import'] >= 0) {
			$key = $resolved['import'];

			if ($imports[$key]['kind'] === 'group') {
				if ($changed) {
					fbMoveFail(sprintf('%s: "%s" is imported by a grouped `use`', $path, $ref['text']));
				}

				continue;
			}

			$before[$key]++;
			$origin[$refKey] = $key;
			$originalRest[$refKey] = $resolved['rest'];
			$bindings[$refKey] = $changed || $sameNamespace[$key]
				? $express($target)
				: ['imp', (string) ($mergedInto[$key] >= 0 ? $mergedInto[$key] : $key), $resolved['rest']];

			continue;
		}

		if (str_starts_with(strtolower($ref['text']), 'namespace\\')) {
			if ($changed || $moved) {
				fbMoveFail(sprintf('%s: a `namespace\\` relative name would need rewriting', $path));
			}

			continue;
		}

		if ($changed || ($moved && isset($known[$lowerFqcn]))) {
			$bindings[$refKey] = $express($target);

			continue;
		}

		if ($moved && !$ref['unqualified'] && !$ref['doc']) {
			fbMoveFail(
				sprintf('%s: relative name "%s" is not a known Core type and the file moves', $path, $ref['text']),
			);
		}

		$reserved[strtolower(explode('\\', $ref['text'])[0])] = true;
	}

	foreach ($bindings as $binding) {
		if ($binding[0] === 'imp') {
			$after[(int) $binding[1]]++;
		}
	}

	// Which existing class imports survive, and under which name. An import is dropped when it
	// names its moved file's own namespace, when it merges into a duplicate, or when every use
	// of it was rewritten away. A docblock can still name it outside a rewritable type (prose,
	// an unlisted tag): an import kept only by such a mention of a moved name is never left
	// behind silently. When it imports a moved class that keeps its short name and its alias
	// stays legal, it is retargeted -- every mention then resolves to the moved class, exactly
	// as before. Otherwise the tool stops before touching anything, naming each mention's line.
	$isStale = static function (string $name) use ($classes, $vacated): bool {
		$lower = strtolower($name);

		if (isset($classes[$lower]) || isset($vacated[$lower])) {
			return true;
		}

		foreach (array_keys($vacated) as $namespace) {
			if (str_starts_with($lower, $namespace . '\\')) {
				return true;
			}
		}

		return false;
	};

	$final = []; // 'e<key>' | 'n<lowercase ns>' => [name, alias, touched]

	foreach ($imports as $key => $import) {
		if ($import['kind'] !== 'class') {
			continue;
		}

		$forced = $sameNamespace[$key] || $mergedInto[$key] >= 0;
		$unused = $before[$key] > 0 && $after[$key] === 0;
		$stale = $isStale($import['name']);
		$mentions = fbMoveMentionsOutsideTypes($analysis['docs'], $import['alias']);
		$staleMentions = array_values(array_filter(
			$mentions,
			static fn (array $mention): bool => $isStale($import['name'] . substr(
				$mention[1],
				strlen($import['alias']),
			)),
		));

		if ($forced && $mentions !== []) {
			fbMoveFailMentions($path, $code, $import['name'], 'must be dropped', $mentions);
		}

		$name = $import['name'];

		// Only an import the mentions alone would keep is retargeted or stops the run. One that
		// code still uses and that is not itself stale is right as it stands; stale prose through
		// it is listed by the stale-reference report instead.
		if (($unused || $stale) && $staleMentions !== []) {
			$target = $classes[strtolower($import['name'])] ?? null;

			if (
				$target === null
				|| $after[$key] !== 0
				|| fbMoveShortOf($target) !== fbMoveShortOf($import['name'])
				|| !fbMoveAliasIsLegal($target, $import['alias'], $import['explicit'])
			) {
				fbMoveFailMentions($path, $code, $import['name'], 'cannot be retargeted exactly', $staleMentions);
			}

			$name = $target;
		} elseif ($forced || ($unused && $mentions === []) || ($stale && $after[$key] === 0 && $mentions === [])) {
			continue;
		} elseif ($stale) {
			fbMoveFail(sprintf(
				'%s:%d: import "%s" names a moved type and is still used by a name that did not move',
				$path,
				fbMoveLineOf($code, $import['start']),
				$import['name'],
			));
		}

		$final['e' . $key] = [
			'name' => $name,
			'alias' => $wantLegal[$key] ? fbMoveShortOf($name) : $import['alias'],
			'touched' => $wantLegal[$key],
		];
	}

	foreach ($newImports as $lowerName => $name) {
		$final['n' . $lowerName] = ['name' => $name, 'alias' => fbMoveShortOf($name), 'touched' => true];
	}

	fbMoveAssignAliases($path, $final, $reserved);

	foreach ($final as $id => $entry) {
		if (str_starts_with($id, 'e')) {
			$import = $imports[(int) substr($id, 1)];

			$mentions = fbMoveMentionsOutsideTypes($analysis['docs'], $import['alias']);

			if ($entry['alias'] !== $import['alias'] && $mentions !== []) {
				fbMoveFailMentions($path, $code, $import['name'], 'must be re-aliased', $mentions);
			}
		}
	}

	// Edits: [start, end, replacement].
	$edits = [];

	foreach ($bindings as $refKey => $binding) {
		$ref = $analysis['refs'][$refKey];

		if (
			$binding[0] === 'imp'
			&& ($origin[$refKey] ?? -1) === (int) $binding[1]
			&& ($originalRest[$refKey] ?? null) === $binding[2]
			&& $final['e' . $binding[1]]['alias'] === $imports[(int) $binding[1]]['alias']
		) {
			continue; // unchanged, through an unchanged import, to the same short name: keep it as
			// written (PHP names are case-insensitive). When the short name itself changed -- a
			// rename that keeps the class in the same, already-imported namespace -- this must not
			// take the shortcut: $binding[2] is then the class's new short name, not what was typed.
		}

		$text = match ($binding[0]) {
			'fq' => '\\' . $binding[1],
			'rel' => $binding[1],
			'imp' => $final['e' . $binding[1]]['alias'] . ($binding[2] !== '' ? '\\' . $binding[2] : ''),
			default => $final['n' . $binding[1]]['alias'] . ($binding[2] !== '' ? '\\' . $binding[2] : ''),
		};

		if ($text !== $ref['text']) {
			$edits[] = [$ref['start'], $ref['start'] + strlen($ref['text']), $text];
		}
	}

	if ($moved) {
		$namespaceEnd = $analysis['namespaceStart'] + strlen($analysis['namespaceText']);
		$edits[] = [$analysis['namespaceStart'], $namespaceEnd, $newNamespace];
	}

	if ($move !== null && fbMoveShortOf($move[0]) !== fbMoveShortOf($move[1])) {
		foreach ($analysis['types'] as $type) {
			if ($type['name'] === fbMoveShortOf($move[0])) {
				$edits[] = [$type['start'], $type['start'] + strlen($type['name']), fbMoveShortOf($move[1])];
			}
		}
	}

	$classImports = [];

	foreach ($imports as $key => $import) {
		if ($import['kind'] !== 'class') {
			continue;
		}

		$classImports[] = $import;
		$entry = $final['e' . $key] ?? null;

		if (
			$entry !== null
			&& $entry['name'] === $import['name']
			&& $entry['alias'] === $import['alias']
			// a normalized `use X as <its own short name>` still loses the useless alias
			&& !($entry['touched'] && $import['explicit'] && $entry['alias'] === fbMoveShortOf($entry['name']))
		) {
			continue;
		}

		if (!$import['clean']) {
			fbMoveFail(sprintf('%s: import "%s" is not alone on its line', $path, $import['name']));
		}

		$edits[] = $entry === null
			? [$import['lineStart'], $import['lineEnd'], '']
			: [$import['start'], $import['end'], fbMoveUseStatement($entry['name'], $entry['alias'])];
	}

	// The last import going away takes the blank line that separated the import block.
	$lastImport = $imports === [] ? null : $imports[count($imports) - 1];

	if (
		$lastImport !== null
		&& $newImports === []
		&& array_filter($imports, static fn (array $import): bool => $import['kind'] !== 'class') === []
		&& array_filter(array_keys($final), static fn (string $id): bool => str_starts_with($id, 'e')) === []
		&& ($code[$lastImport['lineEnd']] ?? '') === "\n"
	) {
		foreach ($edits as $index => $edit) {
			if ($edit[0] === $lastImport['lineStart'] && $edit[1] === $lastImport['lineEnd'] && $edit[2] === '') {
				$edits[$index][1]++;
			}
		}
	}

	// New imports, grouped by insertion point, each group in Slevomat's order.
	$insertions = [];

	foreach ($newImports as $lowerName => $name) {
		[$offset, $afterNamespace] = fbMoveInsertionPoint($path, $code, $analysis, $classImports, $name);
		$insertions[$offset]['names'][$name] = fbMoveUseStatement($name, $final['n' . $lowerName]['alias']) . "\n";
		$insertions[$offset]['blank'] = $afterNamespace;
	}

	ksort($insertions);

	foreach ($insertions as $offset => $insertion) {
		uksort($insertion['names'], fbMoveCompareNames(...));
		$edits[] = [$offset, $offset, implode('', $insertion['names']) . ($insertion['blank'] ? "\n" : '')];
	}

	foreach ($analysis['strings'] as $string) {
		$rewritten = fbMoveRewriteNames($string['text'], $stringMap);

		if ($rewritten !== $string['text']) {
			$edits[] = [$string['start'], $string['start'] + strlen($string['text']), $rewritten];
		}
	}

	$result = fbMoveApplyEdits($path, $code, $edits);

	if ($result !== $code && fbMoveSecuredLines($result) !== fbMoveSecuredLines($code)) {
		fbMoveFail(sprintf('%s: the rewrite would change a @Secured annotation line; refusing', $path));
	}

	return $result;
}

/**
 * Final aliases. A touched import (new, or normalized) is bare unless its short name collides;
 * then it takes the last two segments joined. Two imports of DIFFERENT namespaces sharing a
 * short name, when either is bare (its alias is exactly that short name) and either is touched,
 * BOTH take the two-segment alias -- never one bare and one aliased. This never fires for two
 * imports of the SAME namespace under different explicit aliases (e.g. a shared namespace
 * intentionally aliased once per consumer subsystem in one file): those are already
 * unambiguous and are left as they are, even when a sibling import with a different namespace
 * but the same short name is touched. An untouched import is re-aliased only when a name the
 * rewrite made relative would otherwise resolve through it.
 *
 * @param array<string, array{name: string, alias: string, touched: bool}> $final
 * @param array<string, true> $reserved
 */
function fbMoveAssignAliases(string $path, array &$final, array $reserved): void
{
	$byShort = [];

	foreach ($final as $id => $entry) {
		$byShort[strtolower(fbMoveShortOf($entry['name']))][strtolower($entry['name'])] = true;
	}

	foreach ($final as $id => $entry) {
		$short = strtolower(fbMoveShortOf($entry['name']));
		$sharesShortName = count($byShort[$short]) > 1;
		$isBare = strcasecmp($entry['alias'], fbMoveShortOf($entry['name'])) === 0;
		$groupTouched = false;

		foreach ($final as $other) {
			if (strtolower(fbMoveShortOf($other['name'])) === $short && $other['touched']) {
				$groupTouched = true;
			}
		}

		if (
			($sharesShortName && $groupTouched && $isBare)
			|| ($entry['touched'] && isset($reserved[strtolower($entry['alias'])]))
			|| (!$entry['touched'] && isset($reserved[strtolower($entry['alias'])]))
		) {
			$final[$id]['alias'] = fbMoveTwoSegmentAlias($entry['name']);
			$final[$id]['touched'] = true;
		}
	}

	// A touched alias equal to an untouched import's alias is a collision too.
	foreach ($final as $id => $entry) {
		if (!$entry['touched']) {
			continue;
		}

		foreach ($final as $otherId => $other) {
			if (
				$otherId !== $id
				&& strcasecmp($other['alias'], $entry['alias']) === 0
				&& strcasecmp($other['name'], $entry['name']) !== 0
			) {
				$final[$id]['alias'] = fbMoveTwoSegmentAlias($entry['name']);
			}
		}
	}

	$seen = [];

	foreach ($final as $id => $entry) {
		$lowerAlias = strtolower($entry['alias']);

		if ($entry['touched'] && (isset($reserved[$lowerAlias]) || isset($seen[$lowerAlias]))) {
			fbMoveFail(sprintf('%s: no free alias for "%s" ("%s" is taken)', $path, $entry['name'], $entry['alias']));
		}

		$seen[$lowerAlias] = $id;
	}
}

function fbMoveUseStatement(string $name, string $alias): string
{
	return $alias === fbMoveShortOf($name) ? sprintf('use %s;', $name) : sprintf('use %s as %s;', $name, $alias);
}

/**
 * Where a new class import goes: before the first existing class import that sorts after it
 * (Slevomat's order), else after the last one, else before the function/const imports, else
 * below the namespace statement -- and then it needs a blank line of its own after it.
 *
 * @param array{namespaceEnd: int, imports: list<array{kind: string, name: string, alias: string, start: int, end: int, lineStart: int, lineEnd: int, clean: bool, explicit: bool}>} $analysis
 * @param list<array{kind: string, name: string, alias: string, start: int, end: int, lineStart: int, lineEnd: int, clean: bool, explicit: bool}> $classImports
 *
 * @return array{0: int, 1: bool}
 */
function fbMoveInsertionPoint(string $path, string $code, array $analysis, array $classImports, string $name): array
{
	foreach ($classImports as $import) {
		if (fbMoveCompareNames($name, $import['name']) < 0) {
			return [$import['lineStart'], false];
		}
	}

	if ($classImports !== []) {
		return [$classImports[count($classImports) - 1]['lineEnd'], false];
	}

	if ($analysis['imports'] !== []) {
		return [$analysis['imports'][0]['lineStart'], false];
	}

	$lineEnd = $analysis['namespaceEnd'] >= 0 ? strpos($code, "\n", $analysis['namespaceEnd']) : false;

	if ($lineEnd === false || ($code[$lineEnd + 1] ?? '') !== "\n") {
		fbMoveFail(sprintf('%s: needs a first import but has no `namespace ...;` followed by a blank line', $path));
	}

	return [$lineEnd + 2, true];
}

/**
 * @param list<array{0: int, 1: int, 2: string}> $edits
 */
function fbMoveApplyEdits(string $path, string $code, array $edits): string
{
	// by position; an insertion before a replacement starting at the same byte
	usort(
		$edits,
		static fn (array $a, array $b): int => [$a[0], $a[1] === $a[0] ? 0 : 1] <=> [$b[0], $b[1] === $b[0] ? 0 : 1],
	);

	$result = '';
	$cursor = 0;

	foreach ($edits as [$start, $end, $replacement]) {
		if ($start < $cursor) {
			fbMoveFail(sprintf('%s: overlapping edits at byte %d', $path, $start));
		}

		$result .= substr($code, $cursor, $start - $cursor) . $replacement;
		$cursor = $end;
	}

	return $result . substr($code, $cursor);
}

/**
 * Where an alias is named in a docblock outside the type ranges the tool rewrites -- as an
 * annotation (`@Alias\...`, `@Alias(`) or a qualified name in prose (`Alias\Foo`, `Alias::`).
 * Each mention is [absolute byte offset, the name as written, starting with the alias]. The
 * tool can never rewrite these, so such an import is never dropped or re-aliased under them.
 *
 * @param list<array{start: int, text: string, ranges: list<array{0: int, 1: int, 2: bool}>}> $docs
 *
 * @return list<array{0: int, 1: string}>
 */
function fbMoveMentionsOutsideTypes(array $docs, string $alias): array
{
	$pattern = '/(?<![A-Za-z0-9_\\\\$])' . preg_quote($alias, '/')
		. '(?:(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+|(?=::|\())/i';
	$mentions = [];

	foreach ($docs as $doc) {
		$text = $doc['text'];

		foreach ($doc['ranges'] as [$start, $end]) {
			$text = substr_replace($text, str_repeat(' ', $end - $start), $start, $end - $start);
		}

		preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);

		foreach ($matches[0] as [$name, $offset]) {
			$mentions[] = [$doc['start'] + $offset, $name];
		}
	}

	return $mentions;
}

function fbMoveLineOf(string $code, int $offset): int
{
	return substr_count($code, "\n", 0, $offset) + 1;
}

/**
 * Stops the run -- before anything on disk has changed -- naming every line a docblock names an
 * import on in a way the tool cannot rewrite. Each is a hand fix.
 *
 * @param list<array{0: int, 1: string}> $mentions
 */
function fbMoveFailMentions(string $path, string $code, string $import, string $why, array $mentions): never
{
	$lines = array_map(
		static fn (array $mention): string => sprintf(
			'%s:%d: %s',
			$path,
			fbMoveLineOf($code, $mention[0]),
			$mention[1],
		),
		$mentions,
	);

	fbMoveFail(sprintf(
		"import \"%s\" %s, but a docblock names it outside a rewritable type:\n  %s",
		$import,
		$why,
		implode("\n  ", $lines),
	));
}

/**
 * @return list<string>
 */
function fbMoveSecuredLines(string $code): array
{
	$lines = array_values(array_filter(
		explode("\n", $code),
		static fn (string $line): bool => str_contains($line, fbMoveSecuredMarker()),
	));
	sort($lines, SORT_STRING);

	return $lines;
}

/**
 * Old => new name pairs as text, in every escaping a file can hold them: `\` (PHP code, NEON,
 * XML), `\\` (PHP strings, JSON, PHPStan's regex-quoted messages) and `\\\\`.
 *
 * @param list<array{0: string, 1: string, 2: bool}> $pairs
 */
function fbMoveRewriteNames(string $text, array $pairs): string
{
	if ($pairs === [] || !str_contains($text, 'FastyBird')) {
		return $text;
	}

	foreach (['\\', '\\\\', '\\\\\\\\'] as $separator) {
		$byOld = [];
		$isNamespace = [];

		foreach ($pairs as [$old, $new, $namespace]) {
			$byOld[str_replace('\\', $separator, $old)] = str_replace('\\', $separator, $new);
			$isNamespace[str_replace('\\', $separator, $old)] = $namespace;
		}

		uksort($byOld, fbMoveLongestFirst(...));

		$quotedSeparator = preg_quote($separator, '/');
		$alternatives = [];

		foreach (array_keys($byOld) as $old) {
			// A class name is never a namespace prefix; a namespace name may be one.
			$notPrefix = $isNamespace[$old] ? '' : '(?!' . $quotedSeparator . '[A-Za-z_])';
			$alternatives[] = preg_quote($old, '/') . $notPrefix;
		}

		$pattern = '/(?<![A-Za-z0-9_])(?<![A-Za-z0-9_]' . $quotedSeparator . ')(?:' . implode('|', $alternatives) . ')'
			. '(?![A-Za-z0-9_])/';

		$text = preg_replace_callback(
			$pattern,
			static fn (array $match): string => $byOld[$match[0]] ?? $match[0],
			$text,
		) ?? fbMoveFail('a rewrite pattern failed');
	}

	return $text;
}

/**
 * NEON, XML and JSON: names as text, and the old paths of moved files. Never a `@Secured`
 * annotation line.
 *
 * @param list<array{0: string, 1: string, 2: bool}> $pairs
 * @param array<string, string> $paths
 */
function fbMoveRewriteConfig(string $text, array $pairs, array $paths): string
{
	$lines = preg_split('/(?<=\n)/', $text);

	if ($lines === false) {
		fbMoveFail('could not split a config file into lines');
	}

	foreach ($lines as $index => $line) {
		if (str_contains($line, fbMoveSecuredMarker())) {
			continue;
		}

		$line = fbMoveRewriteNames($line, $pairs);

		foreach ($paths as $old => $new) {
			$line = preg_replace(
				'/(?<![A-Za-z0-9_\-])' . preg_quote($old, '/') . '(?![A-Za-z0-9_\-.\/])/',
				$new,
				$line,
			) ?? $line;
		}

		$lines[$index] = $line;
	}

	return implode('', $lines);
}

/**
 * Every Core type, lowercase FQCN => [FQCN, file].
 *
 * @param list<string> $files
 *
 * @return array<string, array{0: string, 1: string}>
 */
function fbMoveIndexCore(string $root, array $files): array
{
	$index = [];

	foreach ($files as $file) {
		if (!str_starts_with($file, FB_MOVE_CORE_PACKAGE) || !str_ends_with($file, '.php')) {
			continue;
		}

		$analysis = fbMoveAnalyse(fbMoveRead($root . '/' . $file));

		foreach ($analysis['types'] as $type) {
			$fqcn = ($analysis['namespace'] !== '' ? $analysis['namespace'] . '\\' : '') . $type['name'];
			$index[strtolower($fqcn)] = [$fqcn, $file];
		}
	}

	return $index;
}

function fbMoveInPhpScope(string $file): bool
{
	return str_ends_with($file, '.php')
		&& fbMoveHasAnyPrefix($file, FB_MOVE_PHP_ROOTS)
		&& !fbMoveHasAnyPrefix($file, FB_MOVE_OWN_FILES)
		&& !str_contains($file, '/node_modules/')
		&& !str_contains($file, '/vendor/');
}

function fbMoveInConfigScope(string $file): bool
{
	return in_array(pathinfo($file, PATHINFO_EXTENSION), FB_MOVE_CONFIG_EXTENSIONS, true)
		&& !fbMoveHasAnyPrefix($file, FB_MOVE_REPORT_EXCLUDED)
		&& !str_contains($file, 'node_modules/')
		&& !str_starts_with($file, 'vendor/');
}

function fbMoveInReportScope(string $file): bool
{
	return !fbMoveHasAnyPrefix($file, FB_MOVE_REPORT_EXCLUDED)
		&& basename($file) !== 'CHANGELOG.md'
		&& !str_contains($file, 'node_modules/')
		&& !str_starts_with($file, 'vendor/');
}

/**
 * Namespaces the map empties: every type declared in them, or below them, moves.
 *
 * @param array<string, string> $classes
 * @param array<string, array{0: string, 1: string}> $index
 *
 * @return list<string>
 */
function fbMoveVacatedNamespaces(array $classes, array $index): array
{
	$moving = array_change_key_case(array_flip(array_keys($classes)));
	$vacated = [];

	foreach (array_keys($classes) as $old) {
		$namespace = fbMoveNamespaceOf($old);
		$prefix = strtolower($namespace) . '\\';
		$empty = true;

		foreach (array_keys($index) as $lower) {
			if (str_starts_with($lower, $prefix) && !isset($moving[$lower])) {
				$empty = false;

				break;
			}
		}

		if ($empty) {
			$vacated[$namespace] = true;
		}
	}

	$vacated = array_keys($vacated);
	sort($vacated, SORT_STRING);

	return $vacated;
}

/**
 * @param array{classes: array<string, string>, normalize: list<string>, namespaces: array<string, string>, files: array<string, string>} $map
 * @param array<string, array{0: string, 1: string}> $index
 * @param array<string, string> $oldPaths old path => new path
 * @param list<string> $files
 */
function fbMoveReport(string $root, array $map, array $index, array $oldPaths, array $files): int
{
	$vacated = fbMoveVacatedNamespaces($map['classes'], $index);
	$names = array_merge(array_keys($map['classes']), $vacated, array_keys($map['namespaces']));
	$alternatives = [];

	foreach ($names as $name) {
		foreach (['\\', '\\\\', '\\\\\\\\'] as $separator) {
			$alternatives[] = preg_quote(str_replace('\\', $separator, $name), '/');
		}
	}

	foreach (array_keys($oldPaths) as $path) {
		$alternatives[] = preg_quote($path, '/');

		if (str_starts_with($path, FB_MOVE_CORE_PACKAGE)) {
			// the same file named from its package root, as READMEs and docs do
			$alternatives[] = preg_quote(substr($path, strlen(FB_MOVE_CORE_PACKAGE)), '/');
		}
	}

	$alternatives = array_values(array_unique($alternatives));
	usort($alternatives, fbMoveLongestFirst(...));
	$findings = [];

	foreach ($files as $file) {
		// A map that moves nothing (normalization only) has no old name to look for; an empty
		// alternation would match every line.
		if ($alternatives === [] || !fbMoveInReportScope($file)) {
			continue;
		}

		$pattern = '/(?<![A-Za-z0-9_])(?:' . implode('|', $alternatives) . ')(?![A-Za-z0-9_])/';

		$content = fbMoveRead($root . '/' . $file);

		if (str_contains($content, "\0")) {
			continue;
		}

		foreach (explode("\n", $content) as $number => $line) {
			if (preg_match($pattern, $line, $match) === 1) {
				$findings[] = sprintf('  %s:%d: %s  |  %s', $file, $number + 1, $match[0], substr(trim($line), 0, 140));
			}
		}
	}

	$resolved = [];
	$illegal = [];
	$normalize = array_fill_keys(array_map(strtolower(...), $map['normalize']), true);
	$lowerOld = array_change_key_case(array_flip(array_keys($map['classes'])));
	$lowerVacated = array_map(static fn (string $namespace): string => strtolower($namespace), $vacated);
	$isStale = static function (string $name) use ($lowerOld, $lowerVacated): bool {
		$lower = strtolower($name);

		if (isset($lowerOld[$lower])) {
			return true;
		}

		foreach ($lowerVacated as $namespace) {
			if ($lower === $namespace || str_starts_with($lower, $namespace . '\\')) {
				return true;
			}
		}

		return false;
	};

	foreach ($files as $file) {
		if (!fbMoveInPhpScope($file) || !fbMoveInReportScope($file)) {
			continue;
		}

		$code = fbMoveRead($root . '/' . $file);
		$analysis = fbMoveAnalyse($code);
		$aliasIndex = fbMoveAliasIndex($analysis['imports']);

		foreach ($analysis['imports'] as $import) {
			if ($import['kind'] === 'function' || $import['kind'] === 'const') {
				continue;
			}

			if ($isStale($import['name'])) {
				$resolved[] = sprintf('  %s:%d: use %s', $file, fbMoveLineOf($code, $import['start']), $import['name']);
			}

			if (
				isset($normalize[strtolower($import['name'])])
				&& !fbMoveAliasIsLegal($import['name'], $import['alias'], $import['explicit'])
			) {
				$illegal[] = sprintf(
					'  %s:%d: use %s as %s (legal: no alias, or %s)',
					$file,
					fbMoveLineOf($code, $import['start']),
					$import['name'],
					$import['alias'],
					fbMoveTwoSegmentAlias($import['name']),
				);
			}

			// prose and unlisted tags naming a moved type through this import
			foreach (fbMoveMentionsOutsideTypes($analysis['docs'], $import['alias']) as [$offset, $mention]) {
				$fqcn = $import['name'] . substr($mention, strlen($import['alias']));

				if ($isStale($fqcn)) {
					$resolved[] = sprintf(
						'  %s:%d: %s (docblock, not a rewritable type) resolves to %s',
						$file,
						fbMoveLineOf($code, $offset),
						$mention,
						$fqcn,
					);
				}
			}
		}

		foreach ($analysis['refs'] as $ref) {
			$fqcn = fbMoveResolve($ref['text'], $analysis['namespace'], $analysis['imports'], $aliasIndex)['fqcn'];

			if ($isStale($fqcn)) {
				$resolved[] = sprintf(
					'  %s:%d: %s resolves to %s',
					$file,
					fbMoveLineOf($code, $ref['start']),
					$ref['text'],
					$fqcn,
				);
			}
		}
	}

	printf(
		'Stale-reference report: %d old FQCN(s), %d vacated namespace(s), %d old path(s), %d normalized'
		. " namespace(s), %d tracked files.\n",
		count($map['classes']),
		count($vacated),
		count($oldPaths),
		count($normalize),
		count($files),
	);

	foreach ($vacated as $namespace) {
		printf("  vacated: %s\n", $namespace);
	}

	if ($findings === [] && $resolved === [] && $illegal === []) {
		echo "No stale references.\n";

		return 0;
	}

	if ($illegal !== []) {
		printf("\n%d illegal alias(es) of a normalized namespace:\n%s\n", count($illegal), implode("\n", $illegal));
	}

	if ($findings !== []) {
		printf("\n%d line(s) naming an old FQCN, namespace or path:\n%s\n", count($findings), implode("\n", $findings));
	}

	if ($resolved !== []) {
		printf(
			"\n%d PHP name(s) still resolving to an old FQCN or namespace:\n%s\n",
			count($resolved),
			implode("\n", $resolved),
		);
	}

	return 1;
}

/**
 * The map's names as text-rewrite pairs [from, to, is a namespace], forwards or inverted.
 *
 * @param array{classes: array<string, string>, normalize: list<string>, namespaces: array<string, string>, files: array<string, string>} $map
 *
 * @return list<array{0: string, 1: string, 2: bool}>
 */
function fbMoveNamePairs(array $map, bool $inverse): array
{
	$pairs = [];

	foreach ([[false, $map['classes']], [true, $map['namespaces']]] as [$isNamespace, $names]) {
		foreach ($names as $old => $new) {
			$pairs[] = $inverse ? [$new, $old, $isNamespace] : [$old, $new, $isNamespace];
		}
	}

	return $pairs;
}

/**
 * Entries of a PHPStan baseline, each as its lines joined.
 *
 * @return list<string>
 */
function fbMoveBaselineEntries(string $neon): array
{
	$entries = [];
	$current = null;

	foreach (explode("\n", $neon) as $line) {
		if (preg_match('/^\t\t-\s*$/', $line) === 1) {
			if ($current !== null) {
				$entries[] = $current;
			}

			$current = '';

			continue;
		}

		if ($current !== null && str_starts_with($line, "\t\t\t")) {
			$current .= trim($line) . "\n";
		}
	}

	if ($current !== null) {
		$entries[] = $current;
	}

	return $entries;
}

/**
 * @param array{classes: array<string, string>, normalize: list<string>, namespaces: array<string, string>, files: array<string, string>} $map
 * @param array<string, string> $oldPaths old path => new path
 */
function fbMoveVerifyBaselines(string $root, string $ref, array $map, array $oldPaths): int
{
	$inverse = fbMoveNamePairs($map, true);

	$status = 0;

	foreach (['tools/phpstan-baseline.neon', 'tools/phpstan-baseline.tests.neon'] as $file) {
		$before = fbMoveBaselineEntries(fbMoveRun(['git', 'show', $ref . ':' . $file], $root));
		$now = fbMoveBaselineEntries(fbMoveRead($root . '/' . $file));
		$image = array_map(
			static fn (string $entry): string => fbMoveRewriteConfig($entry, $inverse, array_flip($oldPaths)),
			$now,
		);

		$added = fbMoveMultisetDiff($image, $before);
		$lost = fbMoveMultisetDiff($before, $image);

		printf(
			"%s: %d entries at %s, %d now; after the inverse map: %d added, %d lost.\n",
			$file,
			count($before),
			$ref,
			count($now),
			count($added),
			count($lost),
		);

		foreach ($added as $entry) {
			printf("  + %s\n", str_replace("\n", ' | ', trim($entry)));
		}

		foreach ($lost as $entry) {
			printf("  - %s\n", str_replace("\n", ' | ', trim($entry)));
		}

		if ($added !== [] || $lost !== []) {
			$status = 1;
		}
	}

	return $status;
}

/**
 * @param list<string> $a
 * @param list<string> $b
 *
 * @return list<string>
 */
function fbMoveMultisetDiff(array $a, array $b): array
{
	$counts = array_count_values($b);
	$diff = [];

	foreach ($a as $entry) {
		if (($counts[$entry] ?? 0) > 0) {
			$counts[$entry]--;

			continue;
		}

		$diff[] = $entry;
	}

	return $diff;
}

// ----------------------------------------------------------------------------------------

$root = dirname(__DIR__);
$arguments = array_slice($argv, 1);
$reportOnly = in_array('--report-only', $arguments, true);
$verifyRef = null;
$mapPaths = [];

foreach ($arguments as $argument) {
	if ($argument === '--report-only') {
		continue;
	}

	if (str_starts_with($argument, '--verify-baselines=')) {
		$verifyRef = substr($argument, strlen('--verify-baselines='));

		continue;
	}

	if (str_starts_with($argument, '--')) {
		fbMoveFail(sprintf('unknown option "%s"', $argument));
	}

	$mapPaths[] = $argument;
}

if ($mapPaths === []) {
	fbMoveFail('usage: php tools/move-core-symbols.php [--report-only | --verify-baselines=<ref>] <map.php>...');
}

$map = fbMoveLoadMaps($mapPaths);
$psr4 = fbMovePsr4($root);
$files = fbMoveTrackedFiles($root);
$index = fbMoveIndexCore($root, $files);

// Resolve every class move to a file move, in either state: not yet moved, or already moved.
$moves = []; // old path => [new path, old FQCN, new FQCN]
$pending = [];

foreach ($map['classes'] as $old => $new) {
	$oldPath = fbMoveClassPath($old, $psr4);
	$newPath = fbMoveClassPath($new, $psr4);
	$oldKnown = $index[strtolower($old)] ?? null;
	$newKnown = $index[strtolower($new)] ?? null;

	if ($oldKnown !== null && $newKnown === null) {
		if ($oldKnown[1] !== $oldPath) {
			fbMoveFail(sprintf('"%s" is declared in %s, not at its PSR-4 path %s', $old, $oldKnown[1], $oldPath));
		}

		if (is_file($root . '/' . $newPath)) {
			fbMoveFail(sprintf('%s already exists', $newPath));
		}

		$pending[$oldPath] = $newPath;
	} elseif ($oldKnown !== null || $newKnown === null || $newKnown[1] !== $newPath) {
		fbMoveFail(sprintf('"%s" => "%s" matches neither the unmoved nor the moved state', $old, $new));
	}

	$moves[$oldPath] = [$newPath, $old, $new];
}

foreach ($map['files'] as $oldPath => $newPath) {
	if (is_file($root . '/' . $oldPath) && !is_file($root . '/' . $newPath)) {
		$pending[$oldPath] = $newPath;
	} elseif (is_file($root . '/' . $oldPath) || !is_file($root . '/' . $newPath)) {
		fbMoveFail(sprintf('file move "%s" => "%s" matches neither state', $oldPath, $newPath));
	}
}

$oldPaths = array_map(static fn (array $move): string => $move[0], $moves) + $map['files'];
ksort($oldPaths, SORT_STRING);

if ($verifyRef !== null) {
	exit(fbMoveVerifyBaselines($root, $verifyRef, $map, $oldPaths));
}

if ($reportOnly) {
	exit(fbMoveReport($root, $map, $index, $oldPaths, $files));
}

// 1. Pre-flight: compute every rewrite in memory, from the tree as it is. Every condition the
// tool refuses (fbMoveFail) fires here, before a single file is moved or written, so a failed
// run leaves the tree exactly as it found it.
$classes = [];
$known = [];
$normalize = [];
$moveByFile = []; // the file a moved class is declared in, where it is now => [old FQCN, new FQCN]

foreach ($map['classes'] as $old => $new) {
	$classes[strtolower($old)] = $new;
}

foreach ($index as $lower => $unused) {
	$known[$lower] = true;
}

foreach ($map['normalize'] as $namespace) {
	$normalize[strtolower($namespace)] = true;
}

foreach ($moves as $oldPath => [$newPath, $old, $new]) {
	$moveByFile[isset($pending[$oldPath]) ? $oldPath : $newPath] = [$old, $new];
}

$vacated = array_fill_keys(array_map(strtolower(...), fbMoveVacatedNamespaces($map['classes'], $index)), true);
$stringPairs = fbMoveNamePairs($map, false);
$writes = []; // path after the moves => content

foreach ($files as $file) {
	if (fbMoveInPhpScope($file)) {
		$code = fbMoveRead($root . '/' . $file);
		$rewritten = fbMoveRewritePhp(
			$file,
			$code,
			$moveByFile[$file] ?? null,
			$classes,
			$known,
			$normalize,
			$vacated,
			$stringPairs,
		);
	} elseif (fbMoveInConfigScope($file)) {
		$code = fbMoveRead($root . '/' . $file);
		$rewritten = fbMoveRewriteConfig($code, $stringPairs, $oldPaths);
	} else {
		continue;
	}

	if ($rewritten !== $code) {
		$writes[$pending[$file] ?? $file] = $rewritten;
	}
}

ksort($pending, SORT_STRING);
ksort($writes, SORT_STRING);

// 2. Only now touch the tree: move the files, then write every rewritten one.
foreach ($pending as $oldPath => $newPath) {
	$directory = dirname($root . '/' . $newPath);

	if (!is_dir($directory) && !mkdir($directory, 0o777, true)) {
		fbMoveFail(sprintf('could not create %s', $directory));
	}

	fbMoveRun(['git', 'mv', '--', $oldPath, $newPath], $root);
	printf("moved   %s -> %s\n", $oldPath, $newPath);

	// the move leaves the emptied directories behind
	for ($emptied = dirname($root . '/' . $oldPath); $emptied !== $root; $emptied = dirname($emptied)) {
		if (!is_dir($emptied) || scandir($emptied) !== ['.', '..'] || !rmdir($emptied)) {
			break;
		}
	}
}

foreach ($writes as $file => $content) {
	fbMoveWrite($root . '/' . $file, $content);
	printf("rewrote %s\n", $file);
}

printf("\n%d file(s) moved, %d file(s) rewritten.\n\n", count($pending), count($writes));

// 3. The report. Applying the map is not a finding, so its verdict does not set the exit code.
fbMoveReport($root, $map, $index, $oldPaths, fbMoveTrackedFiles($root));

exit(0);
