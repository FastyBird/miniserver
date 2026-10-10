<?php declare(strict_types = 1);

/**
 * T9 (included by census.php): the per-type target counts every E7 package PR checks its result
 * against (#462 §4.1, §13 P6-P8).
 *
 *   --phpcs <json>   a phpcs JSON report of ForbiddenAnnotations and ClassConstantTypeHint, run
 *                    with $E7_DIR/phpcs-e7.xml (census.php phpcs-ruleset) over the six type
 *                    directories -- src/ AND tests/, which is what removing a type's carve-out
 *                    exposes to `make cs`
 *   --rector <txt>   the dry-run output of tools/rector/e7-override.php over all 28 packages
 *
 * Columns: file headers (the shape tools/strip-file-headers.php removes), forbidden-annotation
 * findings, untyped constants (fixable by `make csf`, by hand), `#[\Override]` attributes Rector
 * adds and the files it touches, `final` candidates (T4), `SmartObject` files (T6), and the
 * `missingType.checkedException` entries of tools/phpstan-baseline.neon (the `@throws` target).
 */

$phpcsPath = $options['phpcs'] ?? e7Fail('t9 needs --phpcs <json>');
$rectorPath = $options['rector'] ?? e7Fail('t9 needs --rector <txt>');
$phpcs = json_decode((string) file_get_contents($phpcsPath), true) ?? e7Fail('cannot read ' . $phpcsPath);
$rector = (string) file_get_contents($rectorPath);

$typeOf = static fn (string $path): string|null => preg_match('~src/FastyBird/([^/]+)/[^/]+/~', $path, $m) === 1 ? $m[1] : null;
$count = [];

foreach (E7_TYPES as $type) {
	$count[$type] = array_fill_keys(['headers', 'annotations', 'constants', 'fixable', 'by hand', 'override', 'override files', 'final candidates', 'SmartObject files', 'throws'], 0);
}

// phpcs
foreach ($phpcs['files'] ?? [] as $path => $file) {
	$type = $typeOf($path);

	if ($type === null || !isset($count[$type])) {
		continue;
	}

	foreach ($file['messages'] as $message) {
		if (str_contains($message['source'], 'ForbiddenAnnotations')) {
			$count[$type]['annotations']++;
		} elseif (str_contains($message['source'], 'ClassConstantTypeHint')) {
			$count[$type]['constants']++;
			$count[$type][$message['fixable'] ? 'fixable' : 'by hand']++;
		}
	}
}

// Rector: "N) path:line" opens a file's diff, "+ #[\Override]" lines are the attributes
$current = null;

foreach (explode("\n", $rector) as $line) {
	if (preg_match('~^\d+\) (\S+?):\d+$~', $line, $m) === 1) {
		$current = $typeOf($m[1]);

		if ($current !== null && isset($count[$current])) {
			$count[$current]['override files']++;
		}
	} elseif ($current !== null && isset($count[$current]) && preg_match('~^\+\s*#\[\\\\Override\]~', $line) === 1) {
		$count[$current]['override']++;
	}
}

// headers, final candidates and SmartObject, from the index and the files
foreach ($index['files'] as $file) {
	$type = $typeOf($file);

	if ($type === null || !isset($count[$type]) || e7AreaOf($file) === 'other') {
		continue;
	}

	$code = (string) file_get_contents(e7Root() . '/' . $file);

	if (preg_match('~^<\?php declare\(strict_types = 1\);\s*(/\*\*.*?\*/)\s*namespace\s~s', $code, $m) === 1 && preg_match('~@(' . implode('|', E7_FORBIDDEN_TAGS) . ')\b~', $m[1]) === 1) {
		$count[$type]['headers']++;
	}
}

foreach ($decls as $fqcn => $decl) {
	$type = $typeOf($decl['file']);

	if ($type === null || !isset($count[$type]) || $decl['anonymous'] || e7AreaOf($decl['file']) !== 'src') {
		continue;
	}

	if (in_array('Nette\\SmartObject', $decl['traits'], true)) {
		$count[$type]['SmartObject files']++;
	}

	if ($decl['kind'] === 'class' && !$decl['final'] && !$decl['abstract'] && !e7IsMapped($decl) && e7ExtendedAt($fqcn) === []) {
		$count[$type]['final candidates']++;
	}
}

// @throws: missingType.checkedException entries of the source baseline
$baseline = (string) file_get_contents(e7Root() . '/tools/phpstan-baseline.neon');

foreach (preg_split('~\n\t\t-\n~', $baseline) ?: [] as $entry) {
	if (str_contains($entry, 'missingType.checkedException') && preg_match('~path:\s*(\S+)~', $entry, $m) === 1) {
		$type = $typeOf($m[1]);

		if ($type !== null && isset($count[$type])) {
			$count[$type]['throws'] += preg_match('~count:\s*(\d+)~', $entry, $c) === 1 ? (int) $c[1] : 1;
		}
	}
}

$selectedTypes = array_keys(e7ByType($packages));
$rows = [];
$total = array_fill_keys(array_keys($count['Plugin']), 0);

foreach ($selectedTypes as $type) {
	$rows[] = [$type, ...array_values($count[$type])];

	foreach ($count[$type] as $k => $v) {
		$total[$k] += $v;
	}
}

$rows[] = ['**Total**', ...array_values($total)];

echo "### T9 — per-type target counts\n\n";
e7Table(['Type', 'File headers', 'Forbidden-annotation findings', 'Untyped constants (src+tests)', 'fixable by csf', 'by hand', '`#[\\Override]` to add', 'in files', '`final` candidates', '`SmartObject` files', '`@throws` baseline entries'], $rows);
