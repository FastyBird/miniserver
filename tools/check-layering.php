<?php declare(strict_types = 1);

/**
 * check-layering.php
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
 * DEPENDENCY-DIRECTION GATE FOR THE PACKAGES UNDER src/FastyBird.
 *
 * Run it:   php tools/check-layering.php
 * Or:       make layers
 *
 * Exit 0 clean, 1 the code is wrong, 2 the tool could not do its job. See --help.
 *
 * ---------------------------------------------------------------------------------------
 * WHY THIS IS PLAIN PHP AND NOT A PHPSTAN RULE OR DEPTRAC
 * ---------------------------------------------------------------------------------------
 *
 * Because a PHP-only checker reports this repository 100% clean, and that answer is wrong.
 * Every layer inversion anyone has found here lives in a .neon file: a test container
 * borrowing another package's Doctrine ConnectionWrapper, a connector mapping an addon's
 * fixture namespace, an automator registering the devices module's DI extension. None of
 * those is a `use` statement, so deptrac, the PHPStan Rule API and every AST-based tool in
 * existence see nothing. This scanner reads raw bytes and therefore sees all of them.
 *
 * Being plain PHP with no composer dependency is also a tested property, not a preference:
 * the CI step runs BEFORE `composer install`, so a regression that reintroduced a vendor/
 * requirement fails the job rather than passing review.
 *
 * ---------------------------------------------------------------------------------------
 * WHAT "RAW BYTES" COSTS, STATED HONESTLY
 * ---------------------------------------------------------------------------------------
 *
 * A raw scanner cannot tell a class-string from a sentence that happens to contain a class
 * name. Three separate adversarial reviews planted that exact shape, so the boundary is
 * drawn explicitly rather than left to be rediscovered:
 *
 *   COMMENTS ARE NOT CODE. PHP `//`, `#` and block comments are masked before matching, and
 *   inside a doc comment only the text of a TYPE-BEARING annotation survives -- the list is
 *   FB_TYPE_ANNOTATIONS below: param, var, return, throws, property, method, template,
 *   extends, implements, use, mixin and the phpstan/psalm equivalents. A "deprecated" tag
 *   whose prose names \FastyBird\Module\Ui\... is dropped; a "param" tag naming the same
 *   class is a real dependency and is kept. NEON comments are masked from an unquoted `#` to
 *   end of line, not only when the `#` starts the line. The masking is byte-length
 *   preserving (comment bytes become spaces, newlines survive), so every reported line
 *   number is still the real one.
 *
 *   STRING LITERALS ARE CODE, AND THAT IS DELIBERATE. `'FastyBird\Module\Ui\Entities\...'`
 *   in a PHP string is reported even when it is a human-readable message rather than a
 *   class-string, because half of what this tool exists to catch -- DI wiring, Doctrine
 *   mapping, `class_exists`, PHPStan-style patterns -- is exactly a class name in a string
 *   and there is no way to tell the two apart without running the program. The claim this
 *   tool makes is "zero false positives on the tree as it stands", not "zero by
 *   construction". If a message string ever legitimately needs to name a foreign class,
 *   that is what tools/layering.php["exceptions"] is for. The one file type where this bit
 *   in practice -- a PHPStan baseline, which is 241 KB of FastyBird\... message patterns --
 *   is excluded by name in tools/layering.php["scan"]["excludeFiles"].
 *
 * ---------------------------------------------------------------------------------------
 * KNOWN GAPS -- what this gate does NOT stop, written down so nobody assumes otherwise
 * ---------------------------------------------------------------------------------------
 *
 * A gap that is documented is a decision; a gap that is not is a hole. All five below were
 * found by deliberately attacking the checker, all five have zero live instances in the
 * tree, and each is listed with why closing it was judged to cost more than it is worth.
 *
 *   1. NETTE DI CONSUMED BY EXTENSION NAME. `fbDevicesModule:` as a configuration block, or
 *      `@fbDevicesModule.middlewares.access` as a service reference, couples to the devices
 *      module without naming a single class. Nothing textual identifies the owner. Closing
 *      it would mean building a registry of extension names -- data that rots, that has no
 *      authority over a name two packages both want, and that would need its own gate. All
 *      five `fbDevicesModule:` blocks outside Module/Devices today sit in bridge test
 *      containers that also register DevicesExtension by class, so they are caught by that.
 *   2. AN ALIAS OR GROUP `use` IS FAILED, NOT RESOLVED. `use FastyBird\Module as Modules;`
 *      exits 1 as an unreadable coordinate rather than being followed to Module/Ui. The
 *      author is told to write the coordinate literally, which is also what
 *      SlevomatCodingStandard.Namespaces.DisallowGroupUse demands under `make cs`. Resolving
 *      alias bodies would mean tracking symbol scope, i.e. writing the parser this tool
 *      exists to avoid, for a shape that does not occur.
 *   3. A CLASS NAME THAT IS NEVER SPELLED. `$class = self::MAP[$key];` where MAP lives in
 *      another file has no FastyBird prefix anywhere near the use site. No textual scanner
 *      sees it, and neither does deptrac.
 *   4. A RELATIVE PATH ANCHORED ON A RUNTIME VALUE. `$rootDir . '/../../Devices'` is not
 *      resolved; only PHP paths anchored on `__DIR__` and NEON paths (whose base is the
 *      file's own directory) are. Guessing what a variable holds would produce confident
 *      wrong answers, and the 140 files in this tree that build paths that way all stay
 *      inside their own package.
 *   5. THE FRONTEND. *.ts and *.vue couple by `@fastybird/<name>` npm specifier, never by
 *      PHP namespace, so this scanner would see nothing there. That boundary is clean today
 *      and is a separate tool if one is ever wanted. *.md is skipped for the opposite
 *      reason: three docs/Home.md files name sibling packages in prose, which is a
 *      documentation typo and not a dependency.
 *
 * NOTE ON CODING STANDARDS: tools/ is out of scope for `make cs` twice over -- the target
 * invokes phpcs on `src` only, and tools/phpcs.xml additionally carries
 * `<exclude-pattern>^tools/*</exclude-pattern>`. `make lint` (parallel-lint) is likewise
 * `src`-only. This file nevertheless follows the repository conventions -- tabs,
 * strict_types with spaces around the `=`, the src file docblock header -- because it is
 * read far more often than it is run.
 */

const FB_EXIT_CLEAN = 0;
const FB_EXIT_VIOLATIONS = 1;
const FB_EXIT_BROKEN = 2;

/**
 * Doc-comment annotations that carry a TYPE in the position after the tag. Text following
 * any other tag, and the free prose above the first tag, is dropped before matching. This is
 * the line between "this docblock declares a dependency" and "this docblock talks about one".
 */
const FB_TYPE_ANNOTATIONS = 'param|param-out|var|return|throws|property|property-read|property-write'
	. '|method|template|template-covariant|template-contravariant|extends|implements|use|mixin'
	// The `-ignore` and `-suppress` families are deliberately excluded from the vendor-prefixed
	// wildcards: `@phpstan-ignore-next-line Call to an undefined method FastyBird\...` is a
	// suppression message, not a type position, and 53 files in this tree carry one.
	. '|type|import-type|self-out|this-out|phpstan-(?!ignore)[a-z-]+|psalm-(?!suppress)[a-z-]+';

/**
 * The usage text is a constant rather than a heredoc inside a function so that it can be
 * quoted verbatim in a review without running the tool.
 */
const FB_USAGE = <<<'TXT'
Usage: php tools/check-layering.php [options]

Checks dependency direction between the packages under src/FastyBird/<Type>/<Name>.
With no options it scans the whole tree and exits 0 only if every cross-package
reference is permitted by tools/layering.php.

This checks DIRECTION, not composer manifests. An undeclared but legal import is
not a violation here.

Options:
  -h, --help             Show this help and exit 0. The only option that exits 0
                         without checking anything.
      --list-edges       Print the observed package->package edge matrix and exit 0.
                         Reports only; checks nothing. Use it to derive the rule for
                         a newly added Bridge or Addon. Refuses to run when CI is set
                         in the environment, so it cannot become a green-by-
                         construction CI step.
      --summary-only     Print the summary block but not the per-violation lines.
                         Does not change the exit code.
      --ignore-exceptions
                         Report every violation as if tools/layering.php declared no
                         exceptions. Diagnostic only. Exception entries are still
                         matched and still checked for rot, so this flag can never
                         turn a failing run into a passing one.
      --root=PATH        Repository root. Defaults to the parent of this script's
                         directory, so the tool is cwd-independent.

Exit codes:
  0  clean: no violations, no stale exception entries, self-check passed
  1  at least one violation, unreadable package coordinate, or stale entry
  2  the tool could not do its job: self-check tripped, tools/layering.php is
     invalid, src/FastyBird is missing, or an unknown option was given

Rules and exceptions live in tools/layering.php. Both files are plain PHP with no
dependencies; neither vendor/ nor an autoloader is required.
TXT;

/**
 * Bail out for reasons that are the TOOL's problem rather than the repository's. Always
 * stderr, always exit 2, so that a CI log distinguishes at a glance between "the boundary
 * broke" and "the gate broke".
 */
function fbFail(string $message): never
{
	fwrite(STDERR, 'tools/check-layering.php: ' . $message . PHP_EOL);

	exit(FB_EXIT_BROKEN);
}

/**
 * 1-indexed line number of a byte offset. Called only for references that turn out to be
 * interesting (a violation, or an unresolvable namespace), never for all ~13700 matches --
 * doing it for every match costs ~14ms, which is not a problem, but keeping the hot loop
 * allocation-free is free.
 */
function fbLineAt(string $contents, int $offset): int
{
	return substr_count($contents, "\n", 0, $offset) + 1;
}

/**
 * Collapse the 1-to-N backslashes a reference may have been written with down to one, for
 * display only. PHP single-quoted strings, JSON and the PHPStan baselines all double them,
 * and a PHP double-quoted regex over namespaces quadruples them.
 */
function fbNormalise(string $symbol): string
{
	return (string) preg_replace('~\\\\{2,}~', '\\', $symbol);
}

/**
 * Replace every byte of a region with a space except the newlines. Masking rather than
 * deleting is what keeps every reported line number equal to the real one; deleting would
 * preserve line numbers too, but masking also preserves byte offsets, which makes the
 * masked and unmasked contents directly comparable when debugging the masker itself.
 */
function fbBlank(string $region): string
{
	return (string) preg_replace('~[^\n]~', ' ', $region);
}

/**
 * Keep only the type-bearing parts of a doc comment. Walks the block line by line with one
 * bit of state: after a type-bearing tag everything is kept until the next tag, so a
 * multi-line `@var array<string, \FastyBird\...>` survives intact, while the free prose
 * above the first tag and the body of `@deprecated`, `@see`, `@link` and friends does not.
 */
function fbFilterDocComment(string $block): string
{
	$lines = explode("\n", $block);
	$keeping = false;
	$result = [];

	foreach ($lines as $line) {
		if (preg_match('~@([A-Za-z][A-Za-z0-9_-]*)~', $line, $match, PREG_OFFSET_CAPTURE) === 1) {
			$keeping = preg_match('~^(?:' . FB_TYPE_ANNOTATIONS . ')$~', $match[1][0]) === 1;

			if ($keeping) {
				// Blank everything up to and including the tag itself, keep the type after it.
				$cut = $match[1][1] + strlen($match[1][0]);
				$result[] = fbBlank(substr($line, 0, $cut)) . substr($line, $cut);

				continue;
			}
		}

		$result[] = $keeping ? $line : fbBlank($line);
	}

	return implode("\n", $result);
}

/**
 * Mask PHP comments. One pass, one alternation, and the STRING alternatives come first on
 * purpose: a `//` inside a string literal must not start a comment, and a quoted class name
 * must survive. Heredocs and nowdocs are matched whole for the same reason. `#[` is an
 * attribute, not a comment -- attribute arguments are real references and several are live
 * in this tree.
 */
function fbMaskPhp(string $contents): string
{
	return (string) preg_replace_callback(
		'~(?P<heredoc><<<[\'"]?([A-Za-z_][A-Za-z0-9_]*)[\'"]?\R.*?\R[ \t]*\2\b)'
		. '|(?P<single>\'(?:\\\\.|[^\'\\\\])*\')'
		. '|(?P<double>"(?:\\\\.|[^"\\\\])*")'
		. '|(?P<block>/\*.*?\*/)'
		. '|(?P<line>(?://|\#(?!\[))[^\n]*)~s',
		static function (array $match): string {
			if (($match['block'] ?? '') !== '') {
				return fbFilterDocComment($match['block']);
			}

			if (($match['line'] ?? '') !== '') {
				return fbBlank($match['line']);
			}

			return $match[0];
		},
		$contents,
	);
}

/**
 * Mask NEON comments. NEON starts a comment at an unquoted `#` ANYWHERE on a line, not only
 * at the start of one, so `foo: Bar   # was FastyBird\Module\Devices\Foo` is a comment from
 * the `#` onwards. Quoted strings come first in the alternation so that a `#` inside one --
 * `'#61A519'` occurs in this tree -- is left alone.
 */
function fbMaskNeon(string $contents): string
{
	return (string) preg_replace_callback(
		'~(?P<single>\'(?:\'\'|[^\'\n])*\')|(?P<double>"(?:\\\\.|[^"\\\\\n])*")|(?P<comment>\#[^\n]*)~',
		static fn (array $match): string => ($match['comment'] ?? '') !== ''
			? fbBlank($match['comment'])
			: $match[0],
		$contents,
	);
}

/**
 * Resolve a relative filesystem path against a base directory, by hand -- realpath() is
 * useless here because the target of a planted `includes:` may not exist, and it would also
 * follow symlinks out of the tree.
 *
 * A leading `%parameter%` is stripped and the path is then treated as relative to the
 * directory holding the file. That is exact for PHP `__DIR__ . '...'` and it is true in
 * practice for the only NEON parameter used this way here: tools/phpunit-bootstrap.php
 * defines FB_APP_DIR as the package's tests/ directory, which is where common.neon lives.
 * A path anchored on a parameter that means something else resolves to a location that is
 * not inside any package, and such paths are ignored rather than guessed at.
 *
 * @return string|null null when the path climbs above the filesystem root
 */
function fbResolvePath(string $baseDirectory, string $path): string|null
{
	$path = (string) preg_replace('~^%[A-Za-z0-9_]+%~', '', $path);
	$path = ltrim(str_replace('\\', '/', $path), '/');
	$stack = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $baseDirectory));

	foreach (explode('/', $path) as $segment) {
		if ($segment === '' || $segment === '.') {
			continue;
		}

		if ($segment === '..') {
			if (count($stack) <= 1) {
				return null;
			}

			array_pop($stack);

			continue;
		}

		$stack[] = $segment;
	}

	return implode('/', $stack);
}

// ------------------------------------------------------------------------------------
// Arguments
// ------------------------------------------------------------------------------------

$root = dirname(__DIR__);
$listEdges = false;
$summaryOnly = false;
$ignoreExceptions = false;

foreach (array_slice($argv, 1) as $argument) {
	if ($argument === '-h' || $argument === '--help') {
		echo FB_USAGE, PHP_EOL;

		exit(FB_EXIT_CLEAN);
	}

	if ($argument === '--list-edges') {
		$listEdges = true;
	} elseif ($argument === '--summary-only') {
		$summaryOnly = true;
	} elseif ($argument === '--ignore-exceptions') {
		$ignoreExceptions = true;
	} elseif (str_starts_with($argument, '--root=')) {
		$candidate = realpath(substr($argument, 7));

		if ($candidate === false || !is_dir($candidate)) {
			fbFail('--root does not point at a directory: ' . substr($argument, 7));
		}

		$root = $candidate;
	} else {
		// Never exit 0 on an unknown option. A typo in the CI step must not look like a pass.
		fwrite(STDERR, 'tools/check-layering.php: unknown option: ' . $argument . PHP_EOL . PHP_EOL);
		fwrite(STDERR, FB_USAGE . PHP_EOL);

		exit(FB_EXIT_BROKEN);
	}
}

// `--list-edges` checks nothing and exits 0 by design, which makes it the one shape of this
// tool that is green by construction. That is fine for a human deriving a rule and fatal in
// a CI step, so it refuses to run where CI is set rather than relying on nobody ever adding
// it to ARGS.
if ($listEdges && getenv('CI') !== false && getenv('CI') !== '' && getenv('CI') !== '0' && getenv('CI') !== 'false') {
	fbFail(
		'--list-edges checks nothing and always exits 0, so it must not be used as a CI step. '
		. 'Drop the flag to actually check, or unset CI to derive edges locally.',
	);
}

// ------------------------------------------------------------------------------------
// Configuration
// ------------------------------------------------------------------------------------

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'layering.php';

if (!is_file($configPath)) {
	fbFail('tools/layering.php is missing. The rule matrix lives there.');
}

$config = require $configPath;

if (!is_array($config)) {
	fbFail('tools/layering.php must return an array.');
}

foreach (
	[
		'packagesRoot' => 'string',
		'scan' => 'array',
		'externalNamespaces' => 'array',
		'types' => 'array',
		'packageRuleRequiredFor' => 'array',
		'packages' => 'array',
		'compositionRoots' => 'array',
		'exceptions' => 'array',
		'selfCheck' => 'array',
	] as $key => $expected
) {
	if (!array_key_exists($key, $config)) {
		fbFail('tools/layering.php is missing the "' . $key . '" key.');
	}

	if (get_debug_type($config[$key]) !== $expected) {
		fbFail('tools/layering.php["' . $key . '"] must be ' . $expected . ', got ' . get_debug_type($config[$key]) . '.');
	}
}

foreach (['extensions', 'excludeFiles', 'pruneDirsAnywhere', 'pruneDirsAtPackageRoot'] as $key) {
	if (!array_key_exists($key, $config['scan']) || !is_array($config['scan'][$key])) {
		fbFail('tools/layering.php["scan"]["' . $key . '"] is missing or is not an array.');
	}
}

if ($config['scan']['extensions'] === []) {
	fbFail('tools/layering.php["scan"]["extensions"] is empty, so the scan would read nothing.');
}

/*
 * The floors in layering.php["selfCheck"] are the guard against a green result that means
 * nothing, and they are read out of the very file they guard. An absent or misspelled key
 * would read as null and `$value < null` is always false, which silently voids the floor
 * while still printing "self-check PASS". Every key the checker reads is therefore required
 * to exist and to be a non-negative int BEFORE the scan, and a missing one is exit 2.
 */
$selfCheckKeys = [
	'minPackages',
	'minFiles',
	'minNeonFiles',
	'minJsonFiles',
	'minReferences',
	'minCrossReferences',
	'minPackagePairs',
	'minNeonReferences',
	'minNeonCrossReferences',
	'minTestsReferences',
	'minSrcReferences',
	'minFilesPerPackage',
];

foreach ($selfCheckKeys as $key) {
	if (!array_key_exists($key, $config['selfCheck'])) {
		fbFail(
			'tools/layering.php["selfCheck"]["' . $key . '"] is missing. Every floor the checker reads must '
			. 'be declared: an absent key reads as null, and a comparison against null never trips, which '
			. 'would void the floor while still printing "self-check PASS".',
		);
	}

	if (!is_int($config['selfCheck'][$key]) || $config['selfCheck'][$key] < 0) {
		fbFail('tools/layering.php["selfCheck"]["' . $key . '"] must be a non-negative int.');
	}
}

foreach (array_keys($config['selfCheck']) as $key) {
	if (!in_array($key, $selfCheckKeys, true)) {
		fbFail(
			'tools/layering.php["selfCheck"]["' . $key . '"] is not a floor this checker reads. It is either a '
			. 'typo for one that is, or a leftover; either way it enforces nothing. Remove it.',
		);
	}
}

$packagesRoot = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $config['packagesRoot']);

if (!is_dir($packagesRoot)) {
	fbFail('packages root not found: ' . $config['packagesRoot'] . ' (looked under ' . $root . ')');
}

$extensions = array_flip(array_map('strtolower', $config['scan']['extensions']));
$excludeFiles = array_flip(array_map('strtolower', $config['scan']['excludeFiles']));
$pruneDirsAnywhere = array_flip($config['scan']['pruneDirsAnywhere']);
$pruneDirsAtPackageRoot = array_flip($config['scan']['pruneDirsAtPackageRoot']);
$externalNamespaces = array_flip($config['externalNamespaces']);
$selfCheck = $config['selfCheck'];

// ------------------------------------------------------------------------------------
// Package discovery
//
// A package is any directory at depth 2 under the packages root. Deliberately NOT
// composer.json -- those 34 manifests are being deleted, which is the whole reason this tool
// exists. Deliberately NOT the root autoload, which is an empty psr-4 map. Deliberately NOT
// a hardcoded list of names: a new Connector, Module, Plugin, Automator, Core or Library
// package is picked up on the next run with no edit to either file, and inherits its type
// rule.
//
// Discovery used to require a src/ subdirectory, which quietly doubled as an opt-out: a
// directory holding only config/ and tests/ -- a bridge whose DI wiring was committed before
// its first class, which is an ordinary way to start one -- was not scanned at all AND did
// not trip the mandatory-peer rule for its type. A missing src/ is now a hard configuration
// error instead of an invisibility cloak.
// ------------------------------------------------------------------------------------

$packages = [];
$types = [];

foreach (scandir($packagesRoot) ?: [] as $typeName) {
	if (
		str_starts_with($typeName, '.')
		|| array_key_exists($typeName, $pruneDirsAnywhere)
		|| !is_dir($packagesRoot . DIRECTORY_SEPARATOR . $typeName)
	) {
		continue;
	}

	$hasPackage = false;

	foreach (scandir($packagesRoot . DIRECTORY_SEPARATOR . $typeName) ?: [] as $packageName) {
		$directory = $packagesRoot . DIRECTORY_SEPARATOR . $typeName . DIRECTORY_SEPARATOR . $packageName;

		if (str_starts_with($packageName, '.') || array_key_exists($packageName, $pruneDirsAnywhere) || !is_dir($directory)) {
			continue;
		}

		if (!is_dir($directory . DIRECTORY_SEPARATOR . 'src')) {
			fbFail(
				$config['packagesRoot'] . '/' . $typeName . '/' . $packageName . ' has no src/ directory. Every '
				. 'package must have one. A package directory without src/ used to be skipped entirely, which '
				. 'hid its config/ and tests/ wiring from this checker and exempted it from the mandatory '
				. 'per-package rule for its type. Create src/, or delete the directory.',
			);
		}

		$packages[$typeName . '/' . $packageName] = [
			'type' => $typeName,
			'name' => $packageName,
			'directory' => $directory,
			'files' => 0,
			// Set during the scan: did any file under <pkg>/src declare the expected
			// namespace root? A package whose directory and namespace disagree makes all of
			// its references unattributable and it drops silently out of the graph.
			'namespaceSeen' => false,
		];

		$hasPackage = true;
	}

	if ($hasPackage) {
		$types[] = $typeName;
	}
}

if ($packages === []) {
	fbFail('no packages discovered under ' . $config['packagesRoot'] . '. Expected <Type>/<Name> directories.');
}

sort($types);

/** @var array<string, string> $typesByLowerName */
$typesByLowerName = [];

foreach ($types as $typeName) {
	$typesByLowerName[strtolower($typeName)] = $typeName;
}

/** @var array<string, string> $packagesByLowerName */
$packagesByLowerName = [];

foreach (array_keys($packages) as $identifier) {
	$packagesByLowerName[strtolower($identifier)] = $identifier;
}

// ------------------------------------------------------------------------------------
// Configuration validation
//
// Everything here is exit 2, not exit 1: a broken rule file is not a code problem. Each
// check exists so that a package or a type appearing or disappearing fails loudly instead
// of quietly falling through to something permissive.
// ------------------------------------------------------------------------------------

foreach ($types as $typeName) {
	if (!array_key_exists($typeName, $config['types'])) {
		fbFail(
			'type "' . $typeName . '" exists on disk but has no rule in tools/layering.php["types"]. '
			. 'Add one; a new type must not default to anything.',
		);
	}
}

foreach (array_keys($config['types']) as $typeName) {
	if (!in_array($typeName, $types, true)) {
		fbFail('tools/layering.php["types"] names type "' . $typeName . '", which has no packages on disk. Remove it.');
	}
}

/**
 * Expand a target list -- entries of the form `Type/*` or `Type/Name` -- into the set of
 * package identifiers it permits.
 */
$expandTargets = static function (array $targets, string $where) use ($packages, $types): array {
	$expanded = [];

	foreach ($targets as $target) {
		if (!is_string($target) || preg_match('~^[A-Za-z0-9]+/(\*|[A-Za-z0-9_]+)$~', $target) !== 1) {
			fbFail('invalid target "' . (is_string($target) ? $target : gettype($target)) . '" in ' . $where . '. Use "Type/*" or "Type/Name".');
		}

		[$targetType, $targetName] = explode('/', $target, 2);

		if (!in_array($targetType, $types, true)) {
			fbFail('target "' . $target . '" in ' . $where . ' names type "' . $targetType . '", which does not exist on disk.');
		}

		if ($targetName === '*') {
			foreach ($packages as $identifier => $package) {
				if ($package['type'] === $targetType) {
					$expanded[$identifier] = true;
				}
			}
		} else {
			if (!array_key_exists($target, $packages)) {
				fbFail('target "' . $target . '" in ' . $where . ' names a package that does not exist on disk.');
			}

			$expanded[$target] = true;
		}
	}

	return $expanded;
};

$typeAllows = [];

foreach ($config['types'] as $typeName => $targets) {
	$typeAllows[$typeName] = $expandTargets($targets, 'types["' . $typeName . '"]');
}

foreach (array_keys($config['packages']) as $identifier) {
	if (!array_key_exists($identifier, $packages)) {
		fbFail('tools/layering.php["packages"] names "' . $identifier . '", which does not exist on disk. Remove it.');
	}
}

foreach ($config['packageRuleRequiredFor'] as $typeName) {
	foreach ($packages as $identifier => $package) {
		if ($package['type'] !== $typeName || array_key_exists($identifier, $config['packages'])) {
			continue;
		}

		fbFail(
			$identifier . ' has no entry in tools/layering.php["packages"], and every ' . $typeName
			. ' must have one -- its identity is the set of packages it reaches, which its type cannot express. '
			. 'Run `php tools/check-layering.php --list-edges` to derive it.',
		);
	}
}

/**
 * The effective allow-set for each package: its type rule UNION its per-package rule.
 * Package rules ADD, they never subtract -- see the long comment in tools/layering.php.
 *
 * Production code is deliberately NOT transitively closed. Closing it would make the
 * declared peer list in layering.php narrower than the set actually permitted, which
 * destroys the one property a per-package matrix has over a coarse type rule.
 *
 * Test containers ARE closed, in $testAllows below, and only for files under <pkg>/tests/.
 * A Nette DI container cannot boot a service without also registering what that service
 * depends on, so a test container is obliged to register the full closure of its peers
 * whether the package's own code touches those classes or not. Judging a test container by
 * the direct-peer rule therefore reports the DI system's requirements as architectural
 * debt. Maintainer's decision, 2026-09-12; the alternative was a hand-written exception per
 * transitive registration, which is noise that grows with every new bridge.
 */
$allows = [];
$declaredTargets = [];

foreach ($packages as $identifier => $package) {
	$targets = $config['types'][$package['type']];
	$allow = $typeAllows[$package['type']];

	if (array_key_exists($identifier, $config['packages'])) {
		$targets = array_merge($targets, $config['packages'][$identifier]);
		$allow += $expandTargets($config['packages'][$identifier], 'packages["' . $identifier . '"]');
	}

	$allows[$identifier] = $allow;
	$declaredTargets[$identifier] = $targets;
}

/*
 * The test-container allow-set: the reflexive-transitive closure of $allows. Iterated to a
 * fixed point rather than recursed, so a cycle in the rules cannot produce infinite
 * descent -- the rules are acyclic today and nothing enforces that they stay so.
 */
$testAllows = $allows;

do {
	$grew = false;

	foreach ($testAllows as $identifier => $allow) {
		foreach (array_keys($allow) as $target) {
			foreach (array_keys($testAllows[$target] ?? []) as $inherited) {
				if (array_key_exists($inherited, $testAllows[$identifier])) {
					continue;
				}

				$testAllows[$identifier][$inherited] = true;
				$grew = true;
			}
		}
	}
} while ($grew);

/*
 * Composition roots. An example application configuration that boots one module standalone
 * legitimately wires modules to plugins -- that is what an application does -- so judging
 * such a file by its owning package's dependency rule is a category error. Listed by exact
 * path, never by glob, and each one carries a reason, exactly like an exception.
 */
$compositionRoots = [];

foreach ($config['compositionRoots'] as $index => $entry) {
	foreach (['path', 'reason'] as $field) {
		if (!isset($entry[$field]) || !is_string($entry[$field])) {
			fbFail('compositionRoots[' . $index . '] is missing the required string field "' . $field . '".');
		}
	}

	if (!is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['path']))) {
		fbFail('compositionRoots[' . $index . '] path does not exist: ' . $entry['path']);
	}

	if (strlen($entry['reason']) < 40) {
		fbFail(
			'compositionRoots[' . $index . '] reason is ' . strlen($entry['reason'])
			. ' characters; at least 40 are required. Say why this file composes an application rather than '
			. 'being part of its package.',
		);
	}

	$compositionRoots[$entry['path']] = ['reason' => $entry['reason'], 'used' => 0];
}

$exceptions = [];

foreach ($config['exceptions'] as $index => $exception) {
	foreach (['from', 'to', 'path', 'reason'] as $field) {
		if (!isset($exception[$field]) || !is_string($exception[$field])) {
			fbFail('exceptions[' . $index . '] is missing the required string field "' . $field . '".');
		}
	}

	foreach (['from', 'to'] as $field) {
		if (!array_key_exists($exception[$field], $packages)) {
			fbFail('exceptions[' . $index . '] "' . $field . '" names unknown package "' . $exception[$field] . '".');
		}
	}

	if (!is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $exception['path']))) {
		fbFail('exceptions[' . $index . '] path does not exist: ' . $exception['path']);
	}

	if (array_key_exists($exception['path'], $compositionRoots)) {
		fbFail(
			'exceptions[' . $index . '] path ' . $exception['path'] . ' is also listed as a composition root, '
			. 'where nothing is a violation, so the entry can never match. Remove one of the two.',
		);
	}

	// A reason short enough to be "wip" or "legacy" is not a reason. It must name what would
	// have to change for the entry to be deleted.
	if (strlen($exception['reason']) < 40) {
		fbFail(
			'exceptions[' . $index . '] reason is ' . strlen($exception['reason'])
			. ' characters; at least 40 are required. Say what would have to change to delete the entry.',
		);
	}

	/*
	 * `symbols` and `sites` are what stop an entry from being a permanent open door for its
	 * (from, to, path) triple. Without them an entry grandfathering one Doctrine
	 * ConnectionWrapper line also grandfathers every future reference to any class of that
	 * package anywhere in that file -- and the anti-rot detector cannot help, because the
	 * entry still matches more than zero sites. `symbols` narrows the entry to the exact
	 * names it was written for; `sites` pins how many places it may cover, so a second copy
	 * of an already-excused line fails too.
	 */
	if (!isset($exception['symbols']) || !is_array($exception['symbols']) || $exception['symbols'] === []) {
		fbFail(
			'exceptions[' . $index . '] is missing a non-empty "symbols" list. Name the exact namespaces this '
			. 'entry excuses; without it the entry would grandfather every future reference from '
			. $exception['from'] . ' to ' . $exception['to'] . ' in that file.',
		);
	}

	foreach ($exception['symbols'] as $symbol) {
		if (!is_string($symbol) || $symbol === '') {
			fbFail('exceptions[' . $index . '] "symbols" must be a list of non-empty strings.');
		}
	}

	if (!isset($exception['sites']) || !is_int($exception['sites']) || $exception['sites'] < 1) {
		fbFail('exceptions[' . $index . '] is missing a positive int "sites" -- the exact number of places it excuses.');
	}

	$exceptions[] = [
		'from' => $exception['from'],
		'to' => $exception['to'],
		'path' => $exception['path'],
		'reason' => $exception['reason'],
		'symbols' => array_fill_keys($exception['symbols'], 0),
		'sites' => $exception['sites'],
		'used' => 0,
	];
}

// ------------------------------------------------------------------------------------
// The scan
//
// One file_get_contents, one mask and two preg_match_all per file. No tokenizer (it is an
// order of magnitude slower and cannot read .neon at all, so it would force two code paths
// for no gain), no subprocesses (34 greps would cost more than the whole scan), no
// autoloader.
//
// DO NOT add a `str_contains($contents, 'FastyBird')` prefilter before the regex. It was
// measured: 3235 of 3236 files contain the string, because every PHP file has a namespace
// declaration. It filters exactly one file and costs a second pass over 11MB.
// ------------------------------------------------------------------------------------

/**
 * One pattern for everything. It matches a FastyBird namespace prefix wherever it appears:
 * a `use` statement, an inline FQCN, a quoted class-string in PHP, a NEON mapping value, a
 * NEON mapping KEY, a value with a leading backslash, a quoted argument to a NEON setup
 * call. All of those shapes occur in this repository and no two share a syntax, which is
 * why this is raw text matching rather than a parser.
 *
 *   (?<![A-Za-z0-9_])   `MyFastyBirdThing` is not a reference; `\FastyBird` and `\\FastyBird` are.
 *   \\+                 PHP single-quoted strings, JSON and the PHPStan baselines double the
 *                       separator; a PHP double-quoted regex over namespaces quadruples it,
 *                       and an escaped one in a NEON double-quoted string can reach eight. An
 *                       unbounded run costs nothing and removes the question.
 *   /i                  PHP namespaces are case-insensitive, so `fastybird\module\ui\...`
 *                       loads exactly the same class. The lookups below case-fold too, and
 *                       resolve to the canonical package identifier.
 *   group 1 / group 2   namespace segments 2 and 3 -- the package coordinates. BOTH are
 *                       optional, so that `FastyBird\` followed by a `%s`, a brace or a
 *                       closing quote still matches and can be reported. There are zero of
 *                       those in the tree, so nothing benign lands in that branch.
 *   trailing (?: ... )* the rest of the name, for display. The tail is NOT required to look
 *                       like a class: `sprintf('\FastyBird\Connector\Sonoff\API\Messages\
 *                       Uiid\Uiid%s', $type)` and 'FastyBird\Module\Devices\Controllers\*V1'
 *                       both resolve, because their placeholder is in the TAIL. That is the
 *                       whole of the guarantee -- a placeholder, a concatenation break, a
 *                       group `use` brace or an alias cut in segment 2 or 3 does NOT
 *                       resolve, and is reported as an unreadable coordinate rather than
 *                       skipped. It used to be skipped, which made `use FastyBird\Module as
 *                       Modules;` a way to write a forbidden edge that nothing could see.
 */
$pattern = '~(?<![A-Za-z0-9_])FastyBird\\\\+(?:([A-Za-z0-9_]+)(?:\\\\+([A-Za-z0-9_]+))?(?:\\\\+[A-Za-z0-9_]+)*)?~i';

/**
 * Relative paths that climb out of their own directory. Every class-name-shaped reference in
 * the tree is found by the pattern above, but two first-class coupling mechanisms name no
 * class at all: NEON `includes:` of a sibling package's file, and a Doctrine mapping whose
 * value is `%appDir%/../../Other/src/Entities`. Both are hard dependencies and both are
 * invisible to an FQCN scanner. Requiring a literal `../` is what keeps prose out: an
 * ellipsis in a translation string ("Starting discovery...") has no slash.
 */
$neonPathPattern = '~[^\s"\'#,\[\]{}()=]*\.\./[^\s"\'#,\[\]{}()=]*~';
$phpPathPattern = '~__DIR__\s*\.\s*([\'"])([^\'"\n]*\.\./[^\'"\n]*)\1~';

$stats = [
	'files' => 0,
	'byExtension' => [],
	'references' => 0,
	'crossReferences' => 0,
	'neonReferences' => 0,
	'neonCrossReferences' => 0,
	'srcReferences' => 0,
	'testsReferences' => 0,
	'compositionRootReferences' => 0,
	'pathReferences' => 0,
];

/** @var array<string, array<string, int>> $edges */
$edges = [];
/** @var list<array{path: string, line: int, from: string, to: string, symbol: string}> $sites */
$sites = [];
/** @var array<string, array{path: string, line: int, namespace: string, owner: string, kind: string}> $unresolved */
$unresolved = [];
/** @var list<string> $notes */
$notes = [];

$packagesRootPrefix = str_replace(DIRECTORY_SEPARATOR, '/', $packagesRoot) . '/';

$started = microtime(true);

foreach ($packages as $identifier => &$package) {
	$packageDirectory = str_replace(DIRECTORY_SEPARATOR, '/', $package['directory']);
	$directoryIterator = new RecursiveDirectoryIterator($package['directory'], FilesystemIterator::SKIP_DOTS);

	// Prune during traversal, not afterwards: four packages carry a node_modules and six a
	// dist/, together roughly doubling the on-disk file count for zero in-scope files.
	//
	// Two lists, see the long comment in tools/layering.php. The anchored one exists so that
	// a bare directory name cannot become a hiding place -- <pkg>/src/dist/Foo.php is scanned
	// -- and the unanchored one still reports a note whenever it fires anywhere other than
	// directly under a package root, so nothing is skipped silently either way.
	$filter = new RecursiveCallbackFilterIterator(
		$directoryIterator,
		static function (SplFileInfo $current) use (
			$pruneDirsAnywhere,
			$pruneDirsAtPackageRoot,
			$extensions,
			$excludeFiles,
			$packageDirectory,
			$root,
			&$notes,
		): bool {
			if ($current->isDir()) {
				$parent = str_replace(DIRECTORY_SEPARATOR, '/', dirname($current->getPathname()));

				if (array_key_exists($current->getFilename(), $pruneDirsAtPackageRoot)) {
					return $parent !== $packageDirectory;
				}

				if (!array_key_exists($current->getFilename(), $pruneDirsAnywhere)) {
					return true;
				}

				if ($parent !== $packageDirectory) {
					$notes[] = 'pruned below a package root, nothing inside it is checked: '
						. str_replace(DIRECTORY_SEPARATOR, '/', substr($current->getPathname(), strlen($root) + 1)) . '/';
				}

				return false;
			}

			if (array_key_exists(strtolower($current->getFilename()), $excludeFiles)) {
				return false;
			}

			return array_key_exists(strtolower($current->getExtension()), $extensions);
		},
	);

	$namespacePattern = '~^[ \t]*namespace[ \t]+FastyBird\\\\'
		. preg_quote($package['type'], '~') . '\\\\' . preg_quote($package['name'], '~')
		. '(\\\\|[ \t]*;)~m';

	foreach (new RecursiveIteratorIterator($filter) as $file) {
		assert($file instanceof SplFileInfo);

		if (!$file->isFile()) {
			continue;
		}

		$absolute = $file->getPathname();
		$relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($absolute, strlen($root) + 1));
		$extension = strtolower($file->getExtension());
		$withinPackage = substr($relative, strlen($config['packagesRoot'] . '/' . $identifier . '/'));
		$area = str_starts_with($withinPackage, 'tests/')
			? 'tests'
			: (str_starts_with($withinPackage, 'src/') ? 'src' : 'other');
		$isCompositionRoot = array_key_exists($relative, $compositionRoots);

		$contents = file_get_contents($absolute);

		if ($contents === false) {
			fbFail('could not read ' . $relative);
		}

		++$stats['files'];
		++$package['files'];
		$stats['byExtension'][$extension] = ($stats['byExtension'][$extension] ?? 0) + 1;

		// Mask comments before anything else looks at the bytes. A commented-out `use` line
		// is the normal way to remove a coupling and must not be a violation; a prose
		// docblock naming the class you are told NOT to depend on must not be one either.
		$contents = $extension === 'neon' ? fbMaskNeon($contents) : ($extension === 'json' ? $contents : fbMaskPhp($contents));

		if ($area === 'src' && !$package['namespaceSeen'] && preg_match($namespacePattern, $contents) === 1) {
			$package['namespaceSeen'] = true;
		}

		/**
		 * Record one resolved reference. Shared by the namespace scanner and the path
		 * scanner so that both feed the same statistics, the same edge matrix and the same
		 * violation list.
		 */
		$record = static function (string $target, string $symbol, int $offset) use (
			&$stats,
			&$edges,
			&$sites,
			&$compositionRoots,
			$identifier,
			$relative,
			$extension,
			$area,
			$isCompositionRoot,
			$allows,
			$testAllows,
			$contents,
		): void {
			++$stats['references'];

			if ($extension === 'neon') {
				++$stats['neonReferences'];
			}

			if ($area === 'src') {
				++$stats['srcReferences'];
			} elseif ($area === 'tests') {
				++$stats['testsReferences'];
			}

			// A package referencing itself, including its own Tests\ sub-namespace, is not an
			// edge and is never checked.
			if ($target === $identifier) {
				return;
			}

			++$stats['crossReferences'];

			if ($extension === 'neon') {
				++$stats['neonCrossReferences'];
			}

			$edges[$identifier][$target] = ($edges[$identifier][$target] ?? 0) + 1;

			if ($isCompositionRoot) {
				++$stats['compositionRootReferences'];
				++$compositionRoots[$relative]['used'];

				return;
			}

			/*
			 * Test containers are judged against the transitively closed set; production
			 * code against the direct-peer set. See the $testAllows comment above.
			 */
			$effective = $area === 'tests' ? $testAllows[$identifier] : $allows[$identifier];

			if (array_key_exists($target, $effective)) {
				return;
			}

			$sites[] = [
				'path' => $relative,
				'line' => fbLineAt($contents, $offset),
				'from' => $identifier,
				'to' => $target,
				'symbol' => $symbol,
			];
		};

		if (preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) !== 0) {
			foreach ($matches as $match) {
				$segmentTwo = isset($match[1]) && $match[1][1] !== -1 ? $match[1][0] : null;
				$segmentThree = isset($match[2]) && $match[2][1] !== -1 ? $match[2][0] : null;
				$typeName = $segmentTwo !== null ? ($typesByLowerName[strtolower($segmentTwo)] ?? null) : null;

				/**
				 * File a match that did not resolve to a package. `kind` decides whether it is a
				 * build failure or a note; `hint` is what the reader is told to do about it.
				 */
				$unresolvedAt = static function (string $kind, string $hint) use (
					&$unresolved,
					$relative,
					$identifier,
					$contents,
					$match,
				): void {
					$line = fbLineAt($contents, $match[0][1]);
					$namespace = fbNormalise($match[0][0]);
					$unresolved[$relative . '|' . $line . '|' . $namespace] ??= [
						'path' => $relative,
						'line' => $line,
						'namespace' => $namespace,
						'owner' => $identifier,
						'kind' => $kind,
						'hint' => $hint,
					];
				};

				if ($segmentTwo === null) {
					// `FastyBird\` with no readable type segment at all -- a `%s`, a brace or a
					// closing quote where `Module` should be. The same class of problem as an
					// unreadable package segment, one level further up the namespace.
					$unresolvedAt(
						'unreadable',
						'is a FastyBird namespace whose TYPE segment cannot be read, so neither the type '
						. 'nor the package can be determined. Write the coordinate literally.',
					);

					continue;
				}

				if ($typeName === null) {
					// Not a package coordinate. Either an external composer package sharing the
					// vendor namespace, or a stale spelling that resolves to nothing. Advisory,
					// because the second segment is not a type this repository owns and the tool has
					// no standing to call it wrong.
					if (!array_key_exists($segmentTwo, $externalNamespaces)) {
						$unresolvedAt('external-or-stale', '');
					}

					continue;
				}

				/*
				 * Segment two names a real type, so this IS a package coordinate -- but the
				 * package segment could not be read. A group `use` brace, an alias cut short
				 * (`use FastyBird\Module as Modules;`), a concatenation break or a `%s` in the
				 * package position all land here. Every one of them is a live reference whose
				 * target this tool cannot name, and skipping them silently is how a forbidden
				 * edge disappears by being written one segment shorter. There are zero of these
				 * in the tree, so a hard failure costs nothing and closes the hole.
				 */
				if ($segmentThree === null) {
					$unresolvedAt(
						'unreadable',
						'names a real type but the PACKAGE segment cannot be read, so the edge cannot be '
						. 'checked. A group `use`, an alias cut above the package segment, a concatenation '
						. 'or a placeholder in the package position all do this. Write the coordinate '
						. 'literally.',
					);

					continue;
				}

				$target = $packagesByLowerName[strtolower($typeName . '/' . $segmentThree)] ?? null;

				if ($target === null) {
					// `FastyBird\Module\Something` where Something is not a package on disk. Under a
					// real type that is a stale or misspelt package coordinate, never something
					// benign, so it fails rather than being filed as advice. Case is not the issue:
					// the lookup is case-folded, because PHP namespaces are.
					$unresolvedAt(
						'unknown-package',
						'names no package under type ' . $typeName . '. It is a stale or misspelt '
						. 'coordinate; correct it or delete the reference.',
					);

					continue;
				}

				$record($target, fbNormalise($match[0][0]), $match[0][1]);
			}
		}

		// Path-shaped coupling: NEON `includes:` and mapping values, PHP `__DIR__ . '...'`.
		if ($extension !== 'json') {
			$pathPattern = $extension === 'neon' ? $neonPathPattern : $phpPathPattern;
			$pathGroup = $extension === 'neon' ? 0 : 2;

			if (preg_match_all($pathPattern, $contents, $pathMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) !== 0) {
				$baseDirectory = str_replace(DIRECTORY_SEPARATOR, '/', dirname($absolute));

				foreach ($pathMatches as $pathMatch) {
					$resolved = fbResolvePath($baseDirectory, $pathMatch[$pathGroup][0]);

					if ($resolved === null || !str_starts_with($resolved, $packagesRootPrefix)) {
						continue;
					}

					$remainder = explode('/', substr($resolved, strlen($packagesRootPrefix)));

					if (count($remainder) < 2) {
						continue;
					}

					$target = $packagesByLowerName[strtolower($remainder[0] . '/' . $remainder[1])] ?? null;

					if ($target === null || $target === $identifier) {
						continue;
					}

					++$stats['pathReferences'];
					$record($target, $pathMatch[$pathGroup][0], $pathMatch[0][1]);
				}
			}
		}
	}
}

unset($package);

$elapsed = microtime(true) - $started;

// Several matches on one line for the same target are one site.
$deduplicated = [];

foreach ($sites as $site) {
	$deduplicated[$site['path'] . ':' . $site['line'] . ':' . $site['to']] ??= $site;
}

$sites = array_values($deduplicated);

// Directory iteration order is filesystem dependent. Sort, or two CI runs cannot be diffed.
usort($sites, static fn (array $a, array $b): int => [$a['path'], $a['line'], $a['to']] <=> [$b['path'], $b['line'], $b['to']]);

$unresolved = array_values($unresolved);
usort($unresolved, static fn (array $a, array $b): int => [$a['path'], $a['line']] <=> [$b['path'], $b['line']]);
sort($notes);

$unreadable = array_values(array_filter($unresolved, static fn (array $entry): bool => $entry['kind'] !== 'external-or-stale'));
$advisory = array_values(array_filter($unresolved, static fn (array $entry): bool => $entry['kind'] === 'external-or-stale'));

// ------------------------------------------------------------------------------------
// Self-check
//
// Compared AFTER the scan and BEFORE any verdict. A tripped floor is exit 2 and it
// suppresses the "clean" claim entirely -- the tool refuses to say OK, it does not say
// "OK but". Scanning nothing is the single most likely way this tool fails, and without
// these floors it fails by passing. Every key read here was proven to exist during
// configuration validation, so a floor cannot be voided by a typo.
// ------------------------------------------------------------------------------------

$neonFiles = $stats['byExtension']['neon'] ?? 0;
$packagePairs = array_sum(array_map('count', $edges));

$guards = [
	'packages discovered' => [count($packages), $selfCheck['minPackages']],
	'files scanned' => [$stats['files'], $selfCheck['minFiles']],
	'.neon files scanned' => [$neonFiles, $selfCheck['minNeonFiles']],
	'.json files scanned' => [$stats['byExtension']['json'] ?? 0, $selfCheck['minJsonFiles']],
	'references' => [$stats['references'], $selfCheck['minReferences']],
	'cross-package references' => [$stats['crossReferences'], $selfCheck['minCrossReferences']],
	'package pairs' => [$packagePairs, $selfCheck['minPackagePairs']],
	'references in .neon' => [$stats['neonReferences'], $selfCheck['minNeonReferences']],
	'cross-package refs in .neon' => [$stats['neonCrossReferences'], $selfCheck['minNeonCrossReferences']],
	'references from tests/' => [$stats['testsReferences'], $selfCheck['minTestsReferences']],
	'references from src/' => [$stats['srcReferences'], $selfCheck['minSrcReferences']],
];

$tripped = [];

foreach ($guards as $label => [$value, $floor]) {
	if ($value < $floor) {
		$tripped[] = sprintf('  %-30s %7d   (floor %d)   <-- TRIPPED', $label, $value, $floor);
	}
}

foreach ($packages as $identifier => $package) {
	if ($package['files'] < $selfCheck['minFilesPerPackage']) {
		$tripped[] = sprintf(
			'  %-30s %7d   (floor %d)   <-- TRIPPED',
			'files in ' . $identifier,
			$package['files'],
			$selfCheck['minFilesPerPackage'],
		);
	}

	if ($package['namespaceSeen']) {
		continue;
	}

	$tripped[] = '  ' . $identifier . ': no file under src/ declares namespace FastyBird\\'
		. $package['type'] . '\\' . $package['name'] . '   <-- TRIPPED';
}

if ($tripped !== []) {
	fwrite(
		STDERR,
		'tools/check-layering.php: self-check FAILED -- the scan is implausibly small or a package is'
		. PHP_EOL . 'unattributable, so a clean result would be meaningless. Refusing to report success.'
		. PHP_EOL . implode(PHP_EOL, $tripped) . PHP_EOL
		. 'Likely causes: the scan root moved, an extension left layering.php["scan"]["extensions"], a'
		. PHP_EOL . 'prune rule now matches too much, or a package directory no longer matches its namespace.'
		. PHP_EOL . 'Floors live in tools/layering.php["selfCheck"].' . PHP_EOL,
	);

	exit(FB_EXIT_BROKEN);
}

// ------------------------------------------------------------------------------------
// --list-edges: reporting only
// ------------------------------------------------------------------------------------

if ($listEdges) {
	echo '# --list-edges: REPORTING ONLY, no rules were checked.', PHP_EOL;
	echo '# Observed package -> package references. Use this to derive the rule for a new Bridge or Addon.', PHP_EOL, PHP_EOL;

	ksort($edges);

	foreach ($edges as $from => $targets) {
		ksort($targets);

		$rendered = [];

		foreach ($targets as $to => $count) {
			$rendered[] = $to . ' ' . $count;
		}

		printf("%-46s -> %s\n", $from, implode(', ', $rendered));
	}

	exit(FB_EXIT_CLEAN);
}

// ------------------------------------------------------------------------------------
// Apply exceptions, then detect stale ones
//
// Matching runs unconditionally and only SUPPRESSION is conditional, so --ignore-exceptions
// still accounts for every entry and still fails on a stale one. Putting the rot detector
// behind the flag would have made the flag able to turn a failing run into a passing one on
// a tree whose only failure was a stale entry, which is the opposite of what it promises.
// ------------------------------------------------------------------------------------

$reported = [];
$suppressed = 0;

foreach ($sites as $site) {
	$matched = null;

	foreach ($exceptions as $index => $exception) {
		if (
			$exception['from'] !== $site['from']
			|| $exception['to'] !== $site['to']
			|| $exception['path'] !== $site['path']
			|| !array_key_exists($site['symbol'], $exception['symbols'])
		) {
			continue;
		}

		$matched = $index;

		break;
	}

	if ($matched !== null) {
		++$exceptions[$matched]['used'];
		++$exceptions[$matched]['symbols'][$site['symbol']];

		if (!$ignoreExceptions) {
			++$suppressed;

			continue;
		}
	}

	$reported[] = $site;
}

$stale = [];

foreach ($exceptions as $exception) {
	$label = $exception['from'] . ' -> ' . $exception['to'] . ' in ' . $exception['path'];

	if (array_key_exists($exception['to'], $allows[$exception['from']])) {
		$stale[] = [
			'path' => $exception['path'],
			'message' => $exception['from'] . ' -> ' . $exception['to']
				. ' is already allowed by the rules -- delete this entry',
		];

		continue;
	}

	if ($exception['used'] === 0) {
		$stale[] = ['path' => $exception['path'], 'message' => $label . ' matched 0 sites -- delete this entry'];

		continue;
	}

	if ($exception['used'] !== $exception['sites']) {
		$stale[] = [
			'path' => $exception['path'],
			'message' => $label . ' matched ' . $exception['used'] . ' sites but declares sites => '
				. $exception['sites'] . ' -- '
				. ($exception['used'] > $exception['sites']
					? 'the entry has absorbed a NEW reference it was not written for; check the added site before raising the count'
					: 'some of the debt is paid, narrow or delete the entry'),
		];

		continue;
	}

	foreach ($exception['symbols'] as $symbol => $used) {
		if ($used !== 0) {
			continue;
		}

		$stale[] = [
			'path' => $exception['path'],
			'message' => $label . ' lists symbol ' . $symbol . ', which matched 0 sites -- remove it from "symbols"',
		];
	}
}

foreach ($compositionRoots as $path => $entry) {
	if ($entry['used'] !== 0) {
		continue;
	}

	$stale[] = [
		'path' => $path,
		'message' => $path . ' is listed as a composition root but makes no cross-package reference -- '
			. 'it is an ordinary file of its package now, so delete this entry',
	];
}

// Anchor each stale entry to the line of tools/layering.php that declares its path, so the
// report is clickable. Falls back to line 1.
$configSource = (string) file_get_contents($configPath);

foreach ($stale as $index => $entry) {
	$offset = strpos($configSource, "'" . $entry['path'] . "'");
	$stale[$index]['line'] = $offset !== false ? fbLineAt($configSource, $offset) : 1;
}

usort($stale, static fn (array $a, array $b): int => [$a['line'], $a['message']] <=> [$b['line'], $b['message']]);

// ------------------------------------------------------------------------------------
// Report
// ------------------------------------------------------------------------------------

$failed = $reported !== [] || $unreadable !== [] || $stale !== [];

if (!$summaryOnly) {
	foreach ($reported as $site) {
		$targets = $declaredTargets[$site['from']];
		$rule = $targets === []
			? $site['from'] . ' may not depend on any package'
			: $site['from'] . ' may depend on: ' . implode(', ', $targets);

		printf(
			"%s:%d: violation: %s -> %s (%s) -- %s\n",
			$site['path'],
			$site['line'],
			$site['from'],
			$site['to'],
			$site['symbol'],
			$rule,
		);
	}

	foreach ($unreadable as $entry) {
		printf("%s:%d: %s: %s %s\n", $entry['path'], $entry['line'], $entry['kind'], $entry['namespace'], $entry['hint']);
	}

	foreach ($stale as $entry) {
		printf("tools/layering.php:%d: stale-entry: %s\n", $entry['line'], $entry['message']);
	}
}

$typeCounts = [];

foreach ($packages as $package) {
	$typeCounts[$package['type']] = ($typeCounts[$package['type']] ?? 0) + 1;
}

ksort($typeCounts);

$renderedTypes = [];

foreach ($typeCounts as $typeName => $count) {
	$renderedTypes[] = $typeName . ' ' . $count;
}

ksort($stats['byExtension']);

$renderedExtensions = [];

foreach ($stats['byExtension'] as $extension => $count) {
	$renderedExtensions[] = $extension . ' ' . $count;
}

$violatingPairs = [];
$violatingFiles = [];

foreach ($reported as $site) {
	$violatingPairs[$site['from'] . ' -> ' . $site['to']] = true;
	$violatingFiles[$site['path']] = true;
}

$line = str_repeat('-', 78);

echo $line, PHP_EOL;
printf("Layering check: %s\n", $failed ? 'FAILED' : 'OK');
printf("  packages       %6d  (%s)\n", count($packages), implode(', ', $renderedTypes));
printf("  files scanned  %6d  (%s)\n", $stats['files'], implode(', ', $renderedExtensions));
printf(
	"  references     %6d  (self %d, cross-package %d, in .neon %d, by path %d)\n",
	$stats['references'],
	$stats['references'] - $stats['crossReferences'],
	$stats['crossReferences'],
	$stats['neonReferences'],
	$stats['pathReferences'],
);
printf("  edges          %6d  ordered package pairs\n", $packagePairs);
printf(
	"  violations     %6d  in %d pairs, %d files\n",
	count($reported),
	count($violatingPairs),
	count($violatingFiles),
);
printf("  unreadable     %6d  package coordinates that could not be resolved\n", count($unreadable));
printf(
	"  suppressed     %6d  references, by %d of %d exception entries\n",
	$suppressed,
	count(array_filter($exceptions, static fn (array $exception): bool => $exception['used'] > 0)),
	count($exceptions),
);
printf(
	"  composition    %6d  cross-package references in %d composition-root files, not checked\n",
	$stats['compositionRootReferences'],
	count($compositionRoots),
);
printf("  stale entries  %6d\n", count($stale));
printf("  notes          %6d  advisory, does not affect the exit code\n", count($advisory) + count($notes));
// Ten labelled floors plus two per-package checks, all of them listed in full by the failure
// path. Printing all ten values here was ~300 characters on every single run, which wraps
// into an unreadable block in a CI log and which nobody reads on a passing run.
printf(
	"  self-check       PASS  (%d floors + 2 per-package checks over %d packages)\n",
	count($guards),
	count($packages),
);
printf("  elapsed        %6.2fs\n", $elapsed);
echo $line, PHP_EOL;

// Advisories print AFTER the verdict and wear a `note:` prefix. Above it they were
// indistinguishable at a glance from violations, so every green build opened with four
// error-shaped lines.
if (!$summaryOnly) {
	foreach ($advisory as $entry) {
		printf(
			"%s:%d: note: %s resolves to no package and to no known external namespace (owner %s)\n",
			$entry['path'],
			$entry['line'],
			$entry['namespace'],
			$entry['owner'],
		);
	}

	foreach ($notes as $note) {
		printf("note: %s\n", $note);
	}
}

// A failing gate that does not say how to make a legitimate dependency legal gets deleted
// from CI rather than obeyed.
if ($failed) {
	echo PHP_EOL;
	echo 'Rules, per-package peers, composition roots and exceptions all live in tools/layering.php.', PHP_EOL;
	echo 'A new legitimate dependency is declared by adding a peer there, not by adding an exception.', PHP_EOL;
	echo 'Derive a new package\'s peers with `make layers ARGS=--list-edges`.', PHP_EOL;
}

exit($failed ? FB_EXIT_VIOLATIONS : FB_EXIT_CLEAN);
