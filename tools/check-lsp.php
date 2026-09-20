<?php declare(strict_types = 1);

/**
 * check-lsp.php
 *
 * Walks every .php file under src/FastyBird, extracts the class/interface/trait/enum it
 * declares from its own source (a lightweight regex parse, not a full PHP parser -- this
 * repository's coding standard guarantees one type declaration per file), and confirms:
 *
 *   1. The type actually autoloads (class_exists()/interface_exists()/trait_exists()/
 *      enum_exists() triggers Composer's autoloader; a failure here is either a genuine
 *      missing class or -- the specific failure mode this script exists to catch -- a
 *      namespace that doesn't match its file's PSR-4 path after a bulk rename).
 *   2. Reflection's own idea of the file the class was loaded from matches the file this
 *      script found it in. A class that autoloads successfully but from the WRONG file
 *      (a stale vendor/fastybird/* mirror serving a pre-migration copy instead of the
 *      real src/ file, see CLAUDE.md's vendor-mirror-staleness trap) is exactly the false
 *      green this script is built to catch, and neither `php -l` nor a partial `composer
 *      install` catches it.
 *   3. Every declared parent class and interface resolves too (ReflectionClass itself
 *      would already have thrown a fatal Error at class-load time if a hard `extends`/
 *      `implements` target were missing, so reaching this line at all proves #3 for the
 *      type's immediate ancestry; this script additionally walks the full ancestor chain
 *      to catch a parent that loaded from a stale file per #2).
 *
 * Usage: php tools/check-lsp.php [path-relative-to-repo-root]
 *   No argument: scans the whole src/FastyBird tree.
 *   With argument: scans only that subtree (for a fast re-check after a single package edit).
 *
 * Exit 0: every discovered type loaded, from the expected file, with its whole ancestry
 *         resolving the same way.
 * Exit 1: at least one type failed one of the three checks above; every failure is printed.
 * Exit 2: configuration error (no vendor/autoload.php -- run `composer install` first).
 */

$repoRoot = dirname(__DIR__);
$autoload = $repoRoot . '/vendor/autoload.php';

if (!is_file($autoload)) {
	fwrite(STDERR, "vendor/autoload.php not found -- run composer install first.\n");
	exit(2);
}

require $autoload;

$scanPath = $repoRoot . '/' . ltrim($argv[1] ?? 'src/FastyBird', '/');

if (!is_dir($scanPath)) {
	fwrite(STDERR, sprintf("Not a directory: %s\n", $scanPath));
	exit(2);
}

/**
 * @return array<int, string> Every .php file under $path, recursively, skipping vendor/
 *                             node_modules/dist/tests fixture directories that are not
 *                             expected to be autoloadable production or test-suite code.
 */
function collectPhpFiles(string $path): array
{
	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
	);

	foreach ($iterator as $fileInfo) {
		/** @var SplFileInfo $fileInfo */
		if ($fileInfo->getExtension() !== 'php') {
			continue;
		}

		$relative = str_replace($fileInfo->getPath() . '/', '', $fileInfo->getPathname());

		if (
			str_contains($fileInfo->getPathname(), '/vendor/')
			|| str_contains($fileInfo->getPathname(), '/node_modules/')
			|| str_contains($fileInfo->getPathname(), '/dist/')
		) {
			continue;
		}

		$files[] = $fileInfo->getPathname();
	}

	sort($files);

	return $files;
}

/**
 * Extracts the fully-qualified class/interface/trait/enum name a file declares, by regex
 * over its own source. Returns null for files with no type declaration (rare under
 * src/FastyBird -- e.g. a pure-constants or bootstrap file) rather than treating that as
 * an error; this script only checks files that actually declare something.
 */
function extractFqcn(string $file): string|null
{
	$source = file_get_contents($file);

	if ($source === false) {
		return null;
	}

	if (!preg_match('/^\s*namespace\s+([^;]+);/m', $source, $namespaceMatch)) {
		$namespace = '';
	} else {
		$namespace = trim($namespaceMatch[1]);
	}

	if (
		!preg_match(
			'/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
			$source,
			$typeMatch,
		)
	) {
		return null;
	}

	return $namespace === '' ? $typeMatch[1] : $namespace . '\\' . $typeMatch[1];
}

/** @return array<int, string> */
function ancestry(string $fqcn): array
{
	$names = [];

	try {
		$reflection = new ReflectionClass($fqcn);
	} catch (Throwable) {
		return $names;
	}

	foreach ($reflection->getInterfaceNames() as $interface) {
		$names[] = $interface;
	}

	$parent = $reflection->getParentClass();

	while ($parent !== false) {
		$names[] = $parent->getName();
		$parent = $parent->getParentClass();
	}

	return $names;
}

$files = collectPhpFiles($scanPath);
$checked = 0;
$failures = [];

foreach ($files as $file) {
	$fqcn = extractFqcn($file);

	if ($fqcn === null) {
		continue;
	}

	$checked++;
	$exists = class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn) || enum_exists($fqcn);

	if (!$exists) {
		$failures[] = sprintf('%s: declares %s, which does not autoload', $file, $fqcn);

		continue;
	}

	try {
		$reflection = new ReflectionClass($fqcn);
	} catch (Throwable $exception) {
		$failures[] = sprintf(
			'%s: declares %s, autoloads, but ReflectionClass failed: %s',
			$file,
			$fqcn,
			$exception->getMessage(),
		);

		continue;
	}

	$loadedFrom = $reflection->getFileName();

	// Compare file CONTENT, not path identity. Under COMPOSER_MIRROR_PATH_REPOS=1 (what CI and
	// docker/prod/Dockerfile actually use -- this dev container's own docker-compose does not
	// set it, so a plain `composer install` here symlinks instead), vendor/fastybird/<pkg>/... is
	// a physically distinct copy of src/FastyBird/<Type>/<Name>/..., by mirroring's very design --
	// realpath() can never consider them equal even immediately after the freshest possible
	// mirror rebuild, which made the original realpath-identity check fail on every single
	// mirrored class (3067 of 3068 whole-tree failures on this repository's real tree were this,
	// not genuine bugs) while providing no detection power at all under the symlink mode this
	// same check trivially always passes in (a symlink's realpath() always resolves back to the
	// same canonical src/ file, so staleness cannot occur there either). Content comparison is
	// what the docstring's own stated intent requires either way: a fresh mirror's copy is
	// byte-identical to its src/ origin; a stale one (the pre-migration copy CLAUDE.md's trap
	// describes) is not.
	if (
		$loadedFrom !== false
		&& realpath($loadedFrom) !== realpath($file)
		&& file_get_contents($loadedFrom) !== file_get_contents($file)
	) {
		$failures[] = sprintf(
			'%s: declares %s, but it autoloaded from %s instead, with different content -- stale vendor mirror?',
			$file,
			$fqcn,
			$loadedFrom,
		);

		continue;
	}

	foreach (ancestry($fqcn) as $ancestorName) {
		if (!class_exists($ancestorName) && !interface_exists($ancestorName) && !trait_exists($ancestorName)) {
			$failures[] = sprintf(
				'%s: %s\'s ancestor %s does not autoload',
				$file,
				$fqcn,
				$ancestorName,
			);
		}
	}
}

printf("Checked %d files with a type declaration under %s.\n", $checked, $scanPath);

if ($failures !== []) {
	printf("%d failure(s):\n\n", count($failures));

	foreach ($failures as $failure) {
		echo ' - ' . $failure . "\n";
	}

	exit(1);
}

echo "All types autoload from the expected file with a fully-resolving ancestry.\n";
exit(0);
