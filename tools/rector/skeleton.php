<?php declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

/**
 * The skeleton a repository-wide Rector config starts from. It applies no rule.
 *
 * Use Rector for a mechanical rewrite that a text search cannot express -- a method rename, a
 * class-constant fetch rename, a return-type swap at every call site. Copy this file to
 * tools/rector/<topic>.php, add exactly the rules the change names, and commit the config
 * alongside its output. The repository-root rector.php is unrelated: it is the PHPUnit
 * annotation-to-attribute conversion behind `make rector` / `make rectorf`, scoped to the test
 * suites, and stays as it is.
 *
 * Run it in the application image, never on the host:
 *
 *   make rector-config RECTOR_CONFIG=tools/rector/<topic>.php ARGS=--dry-run   # preview
 *   make rector-config RECTOR_CONFIG=tools/rector/<topic>.php                  # apply
 *
 * `make rector-config` always passes `--clear-cache`, for a preview and for an apply run alike.
 * The cache under var/tools/Rector can return stale results (in #713 a readonly run found 15
 * candidates instead of 20 until it was cleared), and a clear costs only speed. Never run
 * `vendor/bin/rector` directly with one of these configs without `--clear-cache`.
 *
 * (`make rector-config RECTOR_CONFIG=tools/rector/skeleton.php` itself exits 0 and prints
 * Rector's "Register rules or sets" warning: there is nothing to apply.)
 *
 * After a real config, `make csf`: Rector writes fully qualified names, which the coding
 * standard rejects. Commit the Rector output and the `make csf` re-sort apart from any hand fix.
 *
 * The paths are every first-party PHP directory the gates scan -- production code as well as
 * tests, because a public API change has call sites in every package. Names are not imported (no
 * withImportNames()): this codebase imports namespaces, not classes, and Rector's importer would
 * fight tools/check-naming.php's alias rule.
 */
return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/../../src/FastyBird',
		__DIR__ . '/../../tests',
		__DIR__ . '/../../bin',
		__DIR__ . '/../../public',
		__DIR__ . '/../../migrations',
	])
	->withSkip([
		'*/node_modules/*',
		'*/assets/*',
		__DIR__ . '/../../tests/stubs',
	])
	->withCache(__DIR__ . '/../../var/tools/Rector')
	->withPhpVersion(PhpVersion::PHP_84)
	->withRules([]);
