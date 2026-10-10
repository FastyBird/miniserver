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
 * Without --package the scope is every first-party PHP directory: src, tests, bin, public and
 * migrations. node_modules directories are skipped.
 *
 * WHAT IS A HEADER. The file's first docblock, if and only if it sits DIRECTLY between the
 * `declare(strict_types = 1);` statement and the `namespace` keyword -- nothing but whitespace on
 * either side -- and its content, line by line with the leading `*` stripped, is only:
 *
 *   - the filename: one or more lines before the first tag that, joined, read `<name>.php`. Any
 *     form is accepted: the file's own name, a stale one (120 headers name a file that has since
 *     been renamed), or one split over two lines (`Plugin/ApiKey/src/Entities/TEntity.php` has
 *     ` * trait TEntity` and `.php`). A leading `class`/`interface`/`trait`/`enum` word is
 *     tolerated for that reason. The header is recognised by its shape, never by comparing the
 *     name with the file;
 *   - the nine forbidden tags, @package @subpackage @author @copyright @license @since @created
 *     @version @date, with any value;
 *   - blank `*` lines.
 *
 * Removing it deletes the docblock and the whitespace after it, so the file reads
 * `<?php declare(strict_types = 1);` + the original blank line + `namespace ...` -- Core's shape.
 *
 * ANY OTHER SHAPE IS LEFT ALONE AND REPORTED: a docblock in that position holding anything else
 * (a prose line, another tag, a filename after a tag), or a docblock before the namespace that is
 * not directly between declare and namespace but still carries a forbidden tag. Fixing those is a
 * hand decision (#462 §15.1: by hand if the content is still only the filename and tags,
 * otherwise an escalation, §15.2). A forbidden tag in a class or member docblock is not a header
 * and is not this tool's business: `make csf` removes it once the package's ForbiddenAnnotations
 * carve-out in tools/phpcs.xml is gone.
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

const FB_HEADER_DEFAULT_SCOPE = ['src', 'tests', 'bin', 'public', 'migrations'];

const FB_HEADER_FILENAME = '/^(?:(?:class|interface|trait|enum)\s+)?[A-Za-z0-9_.\/-]+\.php$/';

function fbHeaderFail(string $message): never
{
	fwrite(STDERR, 'strip-file-headers: ' . $message . PHP_EOL);

	exit(2);
}

/**
 * @return list<string> repository-relative paths of the PHP files below $directory, sorted
 */
function fbHeaderFiles(string $root, string $directory): array
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
		if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
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

	$name = implode('', $filename);

	if (preg_match(FB_HEADER_FILENAME, $name) !== 1) {
		return sprintf('its first line is not a *.php filename: "%s"', $name);
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

	// A forbidden tag in a docblock before the namespace keyword, wherever it sits.
	$forbiddenBeforeNamespace = null;

	foreach ($tokens as $token) {
		if ($token->is(T_NAMESPACE)) {
			break;
		}

		if (
			$token->is(T_DOC_COMMENT)
			&& preg_match('/^\s*\*?\s*@(' . implode('|', FB_HEADER_FORBIDDEN_TAGS) . ')\b/mi', $token->text) === 1
		) {
			$forbiddenBeforeNamespace = $token;

			break;
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
	foreach (fbHeaderFiles($root, $directory) as $file) {
		$files[$file] = true;
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

	$result = fbHeaderAnalyse($code);

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
