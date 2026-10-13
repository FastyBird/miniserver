<?php declare(strict_types = 1);

/**
 * Removes the legacy file header from first-party PHP files. Epic E7 (#462 §3 D9), E7.0 (#694).
 *
 * The header is the 14-line docblock the 28 non-Core packages still carry between
 * `declare(strict_types = 1);` and `namespace` -- a filename line and the nine tags
 * docs/conventions.md forbids. E2 found that `make csf` cannot remove it (ForbiddenAnnotations
 * leaves a `/** * Foo.php *\/` stub behind), so every E7 package PR runs this tool first and
 * `make csf` after it (#462 §3 D4, item 2).
 *
 * USAGE (in the application image, from the repository root)
 *
 *   php tools/strip-file-headers.php [--package <Type>[/<Name>]]... [--check] [--list]
 *
 *   (no option)      remove every header in scope, print a summary and every file left alone
 *   --check          change nothing; exit 1 if any header remains in scope (a removable one, or a
 *                    docblock in another shape that still carries a forbidden tag), 0 otherwise
 *   --package X      limit the scope to src/FastyBird/<X>; repeatable. `Module` is the four
 *                    modules, `Module/Devices` is one package
 *   --list           also print every file a header was (or, with --check, would be) removed from
 *
 * Without --package the scope is every first-party directory: src, tests, bin, public, migrations
 * and config, with their PHP files and every `*.neon` file below them. node_modules directories
 * are skipped.
 *
 * WHAT IS A HEADER. The file's first docblock, if and only if it sits DIRECTLY between the
 * `declare(strict_types = 1);` statement and the `namespace` keyword -- nothing but whitespace on
 * either side -- and its content, line by line with the leading `*` stripped, is only:
 *
 *   - the filename: one token ending in `.php`, optionally after a `class`/`interface`/`trait`/
 *     `enum` word. It may be the file's own name or a stale one (120 headers name a file that has
 *     since been renamed), and it may be split at the extension: the first line holds one token
 *     and every further line before the tags is a whitespace-free fragment starting with `.`
 *     (one such header was ` * trait TEntity` and ` * .php`). Any other
 *     second line -- ` * Bootstrap` + ` * Foo.php` -- is prose, and the docblock is left alone.
 *     The header is recognised by its shape, never by comparing the name with the file;
 *   - the nine forbidden tags, @package @subpackage @author @copyright @license @since @created
 *     @version @date, with any value;
 *   - blank `*` lines.
 *
 * Removing it deletes the docblock and the whitespace after it, so the file reads
 * `<?php declare(strict_types = 1);` + the original blank line + `namespace ...` -- Core's shape.
 *
 * ANY OTHER SHAPE IS LEFT ALONE AND REPORTED: a docblock in that position holding anything else
 * (a prose line, another tag, a filename after a tag), or a docblock before the namespace that is
 * not directly between declare and namespace but still carries a forbidden tag. A file without a
 * namespace has no header to judge: its docblocks are left alone and never reported. Fixing those is a
 * hand decision (#462 §15.1: by hand if the content is still only the filename and tags,
 * otherwise an escalation, §15.2). A forbidden tag in a class or member docblock is not a header
 * and is not this tool's business: `make csf` removes it once the package's ForbiddenAnnotations
 * carve-out in tools/phpcs.xml is gone.
 *
 * NEON FILES. The same legacy header sits at the top of the `*.neon` files: the translations
 * (`src/FastyBird/<Type>/<Name>/src/**\/Translations/*.neon`; the Core one is nested under `Api`,
 * #729), the shipped and test configuration (`config/common.neon`, the Core `config/*.neon` and
 * `tests/common.neon`) and the test fixtures (#734). Every `*.neon` file below the scope is in
 * scope, because the ForbiddenAnnotations sniff only sees PHP. A NEON header is the file's leading
 * block of `#` comment lines, followed by blank lines, if and only if the block holds at least one
 * of the nine forbidden tags and every line of it is one of:
 *
 *   - an empty comment line (`#`);
 *   - one of the nine forbidden tags or `@language`, with any value;
 *   - ONE title line before the first tag, without an `@` (`# Connector translations`).
 *
 * Removing it deletes the block and the blank lines after it, so the file starts with its first
 * key; everything after is untouched byte for byte. The other-shape rule applies here too: a
 * leading comment block with a forbidden tag in any other shape, or a forbidden tag in a comment
 * line anywhere else in the file, is left alone, reported and counted by --check. Only that block
 * is a header: every other comment in a `*.neon` file (section banners, explanations) is kept, and
 * so is a title or description of more than one line -- a file whose header also carries prose has
 * its tags removed by hand (tests/config/dbal-test-connection.neon, #734).
 *
 * The tool is idempotent: once a header is gone the position is empty, so a second run removes
 * nothing. It only ever deletes bytes from the start of a file up to `namespace`; it never touches
 * anything after it.
 *
 * Exit codes follow tools/check-naming.php: 0 done / clean, 1 --check found a header, 2 usage or
 * I/O error. tools/ is outside the coding standard's paths; this file follows it anyway.
 */

const FB_HEADER_FORBIDDEN_TAGS = [
	'package',
	'subpackage',
	'author',
	'copyright',
	'license',
	'since',
	'created',
	'version',
	'date',
];

const FB_HEADER_DEFAULT_SCOPE = ['src', 'tests', 'bin', 'public', 'migrations', 'config'];

// The first filename line: one token, optionally after a class/interface/trait/enum word.
const FB_HEADER_FILENAME = '/^(?:(?:class|interface|trait|enum)\s+)?([A-Za-z0-9_.\/-]+)$/';

// A continuation line of a split filename: a fragment of the extension, which starts with the dot
// and holds no whitespace (` * trait TEntity` + `.php`). Anything else on a second line is prose.
const FB_HEADER_FILENAME_CONTINUATION = '/^\.[A-Za-z0-9_.\/-]*$/';

function fbHeaderFail(string $message): never
{
	fwrite(STDERR, 'strip-file-headers: ' . $message . PHP_EOL);

	exit(2);
}

/**
 * @return list<string> repository-relative paths of the PHP files below $directory or, with $neon, of
 *                      its `*.neon` files; sorted
 */
function fbHeaderFiles(string $root, string $directory, bool $neon = false): array
{
	$files = [];

	if (!is_dir($root . '/' . $directory)) {
		return $files;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS),
			static fn (SplFileInfo $file): bool => !$file->isDir() || $file->getFilename() !== 'node_modules',
		),
	);

	foreach ($iterator as $file) {
		if (
			$file instanceof SplFileInfo
			&& $file->isFile()
			&& $file->getExtension() === ($neon ? 'neon' : 'php')
		) {
			$files[] = substr($file->getPathname(), strlen($root) + 1);
		}
	}

	sort($files, SORT_STRING);

	return $files;
}

/**
 * Why a docblock is not a removable header, or null when it is one.
 */
function fbHeaderRejection(string $docblock): string|null
{
	$body = substr($docblock, 3, -2);
	$filename = [];
	$tags = 0;

	foreach (preg_split('/\R/', $body) ?: [] as $line) {
		$text = trim((string) preg_replace('/^\s*\*?/', '', $line));

		if ($text === '') {
			continue;
		}

		if (preg_match('/^@([A-Za-z-]+)/', $text, $match) === 1) {
			if (!in_array(strtolower($match[1]), FB_HEADER_FORBIDDEN_TAGS, true)) {
				return sprintf('holds the tag @%s', $match[1]);
			}

			$tags++;

			continue;
		}

		if ($tags > 0) {
			return sprintf('holds a line after its tags: "%s"', $text);
		}

		$filename[] = $text;
	}

	if ($filename === []) {
		return 'has no filename line';
	}

	if (preg_match(FB_HEADER_FILENAME, $filename[0], $match) !== 1) {
		return sprintf('does not start with a *.php filename: "%s"', implode(' / ', $filename));
	}

	$name = $match[1];

	foreach (array_slice($filename, 1) as $continuation) {
		if (preg_match(FB_HEADER_FILENAME_CONTINUATION, $continuation) !== 1) {
			return sprintf('holds text besides a *.php filename before its tags: "%s"', implode(' / ', $filename));
		}

		$name .= $continuation;
	}

	if (!str_ends_with($name, '.php')) {
		return sprintf('does not start with a *.php filename: "%s"', implode(' / ', $filename));
	}

	return null;
}

/**
 * @return array{status: 'none'|'header'|'other', code: string, reason: string|null, forbidden: bool}
 */
function fbHeaderAnalyse(string $code): array
{
	$tokens = PhpToken::tokenize($code);
	$count = count($tokens);
	$none = ['status' => 'none', 'code' => $code, 'reason' => null, 'forbidden' => false];
	$other = static fn (string $reason, bool $forbidden): array => [
		'status' => 'other',
		'code' => $code,
		'reason' => $reason,
		'forbidden' => $forbidden,
	];

	$next = static function (int $index) use ($tokens, $count): int {
		for ($i = $index + 1; $i < $count; $i++) {
			if (!$tokens[$i]->is(T_WHITESPACE)) {
				return $i;
			}
		}

		return -1;
	};

	// A forbidden tag in a docblock before the namespace keyword, wherever it sits. A file with no
	// namespace has no file header in this sense: a docblock there documents the code after it (a
	// class docblock is `make cs`'s business, ForbiddenAnnotations), so it is neither removed nor
	// reported.
	$forbiddenBeforeNamespace = null;
	$candidate = null;

	foreach ($tokens as $token) {
		if ($token->is(T_NAMESPACE)) {
			$forbiddenBeforeNamespace = $candidate;

			break;
		}

		if (
			$candidate === null
			&& $token->is(T_DOC_COMMENT)
			&& preg_match('/^\s*\*?\s*@(' . implode('|', FB_HEADER_FORBIDDEN_TAGS) . ')\b/mi', $token->text) === 1
		) {
			$candidate = $token;
		}
	}

	// The declare(strict_types = 1); statement, as the first statement after the open tag.
	$declare = $next(0);

	if ($count === 0 || !$tokens[0]->is(T_OPEN_TAG) || $declare < 0 || !$tokens[$declare]->is(T_DECLARE)) {
		return $forbiddenBeforeNamespace === null
			? $none
			: $other('carries a forbidden tag, but no declare() starts the file', true);
	}

	$semicolon = $declare;

	while ($semicolon < $count && $tokens[$semicolon]->text !== ';') {
		$semicolon++;
	}

	$docblock = $next($semicolon);

	if ($docblock < 0 || !$tokens[$docblock]->is(T_DOC_COMMENT)) {
		return $forbiddenBeforeNamespace === null
			? $none
			: $other('carries a forbidden tag in a docblock that does not follow declare() directly', true);
	}

	$namespace = $next($docblock);

	if ($namespace < 0 || !$tokens[$namespace]->is(T_NAMESPACE)) {
		return $forbiddenBeforeNamespace === null
			? $none
			: $other('carries a forbidden tag in a docblock after declare() that namespace does not follow directly', true);
	}

	$reason = fbHeaderRejection($tokens[$docblock]->text);

	if ($reason !== null) {
		return $other(
			'its docblock between declare() and namespace ' . $reason,
			$forbiddenBeforeNamespace === $tokens[$docblock],
		);
	}

	$kept = '';

	for ($i = 0; $i < $docblock; $i++) {
		$kept .= $tokens[$i]->text;
	}

	for ($i = $namespace; $i < $count; $i++) {
		$kept .= $tokens[$i]->text;
	}

	return ['status' => 'header', 'code' => $kept, 'reason' => null, 'forbidden' => true];
}

/**
 * Analyses the leading comment block of a NEON file.
 *
 * @return array{status: 'none'|'header'|'other', code: string, reason: string|null, forbidden: bool}
 */
function fbHeaderAnalyseNeon(string $code): array
{
	$forbiddenTag = '/^\s*#\s*@(' . implode('|', FB_HEADER_FORBIDDEN_TAGS) . ')\b/i';
	$lines = preg_split('/(?<=\n)/', $code, -1, PREG_SPLIT_NO_EMPTY) ?: [];

	// The leading block: consecutive lines starting with `#`.
	$block = 0;
	$hasTag = false;

	while ($block < count($lines) && str_starts_with($lines[$block], '#')) {
		$hasTag = $hasTag || preg_match($forbiddenTag, $lines[$block]) === 1;
		$block++;
	}

	if (!$hasTag) {
		// No header at the top: a forbidden tag in a comment line anywhere else is still reported.
		foreach ($lines as $number => $line) {
			if (preg_match($forbiddenTag, $line) === 1) {
				return [
					'status' => 'other',
					'code' => $code,
					'reason' => sprintf('line %d carries a forbidden tag outside a leading comment block', $number + 1),
					'forbidden' => true,
				];
			}
		}

		return ['status' => 'none', 'code' => $code, 'reason' => null, 'forbidden' => false];
	}

	$other = static fn (string $reason): array => [
		'status' => 'other',
		'code' => $code,
		'reason' => $reason,
		'forbidden' => true,
	];

	$tags = 0;
	$titles = 0;

	for ($i = 0; $i < $block; $i++) {
		$text = trim(substr(rtrim($lines[$i]), 1));

		if ($text === '') {
			continue;
		}

		if (preg_match('/^@([A-Za-z-]+)/', $text, $match) === 1) {
			if (!in_array(strtolower($match[1]), [...FB_HEADER_FORBIDDEN_TAGS, 'language'], true)) {
				return $other(sprintf('its leading comment block holds the tag @%s', $match[1]));
			}

			$tags++;

			continue;
		}

		if ($tags > 0 || $titles > 0 || str_contains($text, '@')) {
			return $other(sprintf('its leading comment block holds "%s", which is not a single title line before the tags', $text));
		}

		$titles++;
	}

	$rest = $block;

	while ($rest < count($lines) && trim($lines[$rest]) === '') {
		$rest++;
	}

	return ['status' => 'header', 'code' => implode('', array_slice($lines, $rest)), 'reason' => null, 'forbidden' => true];
}

// ---------------------------------------------------------------------------------------------

$root = dirname(__DIR__);
$arguments = array_slice($argv, 1);
$check = false;
$list = false;
$packages = [];

for ($i = 0; $i < count($arguments); $i++) {
	switch ($arguments[$i]) {
		case '--check':
			$check = true;

			break;
		case '--list':
			$list = true;

			break;
		case '--package':
			$package = $arguments[++$i] ?? fbHeaderFail('--package needs <Type> or <Type>/<Name>');

			if (preg_match('/^[A-Z][A-Za-z0-9]*(\/[A-Z][A-Za-z0-9]*)?$/', $package) !== 1) {
				fbHeaderFail(sprintf('--package takes <Type> or <Type>/<Name>, not "%s"', $package));
			}

			if (!is_dir($root . '/src/FastyBird/' . $package)) {
				fbHeaderFail(sprintf('src/FastyBird/%s does not exist', $package));
			}

			$packages[] = 'src/FastyBird/' . $package;

			break;
		default:
			fbHeaderFail(sprintf(
				'unknown argument "%s"; usage: [--package <Type>[/<Name>]]... [--check] [--list]',
				$arguments[$i],
			));
	}
}

$scope = $packages !== [] ? array_values(array_unique($packages)) : FB_HEADER_DEFAULT_SCOPE;
$files = [];

foreach ($scope as $directory) {
	foreach ([false, true] as $neon) {
		foreach (fbHeaderFiles($root, $directory, $neon) as $file) {
			$files[$file] = true;
		}
	}
}

$files = array_keys($files);
sort($files, SORT_STRING);

$headers = [];
$others = [];
$offending = 0;

foreach ($files as $file) {
	$code = @file_get_contents($root . '/' . $file);

	if ($code === false) {
		fbHeaderFail(sprintf('could not read %s', $file));
	}

	$result = str_ends_with($file, '.neon') ? fbHeaderAnalyseNeon($code) : fbHeaderAnalyse($code);

	if ($result['status'] === 'other') {
		$others[$file] = (string) $result['reason'];
		$offending += $result['forbidden'] ? 1 : 0;
	}

	if ($result['status'] !== 'header') {
		continue;
	}

	$headers[] = $file;

	if (!$check && file_put_contents($root . '/' . $file, $result['code']) === false) {
		fbHeaderFail(sprintf('could not write %s', $file));
	}
}

if ($list) {
	foreach ($headers as $file) {
		printf("%s %s\n", $check ? 'header ' : 'removed', $file);
	}
}

foreach ($others as $file => $reason) {
	printf("other shape, left alone: %s -- %s\n", $file, $reason);
}

printf(
	"%s: %d file(s) scanned in %s; %d header(s) %s; %d file(s) in another shape left alone, %d of them with a forbidden tag.\n",
	$check ? 'check' : 'strip',
	count($files),
	implode(', ', $scope),
	count($headers),
	$check ? 'found' : 'removed',
	count($others),
	$offending,
);

exit($check && ($headers !== [] || $offending > 0) ? 1 : 0);
