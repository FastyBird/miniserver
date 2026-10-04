<?php declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

/**
 * The skeleton every Epic E5 (#460) Rector config starts from. It applies no rule.
 *
 * E5 uses Rector for the repository-wide mechanical rewrites the move tool
 * (tools/move-core-symbols.php) cannot express -- a method rename, a class-constant fetch
 * rename, a return-type swap at every call site. Each such PR copies this file to
 * tools/rector/e5-<topic>.php, adds exactly the rules its issue names, and commits the config
 * alongside the output. The repository-root rector.php is unrelated: it is the PHPUnit
 * annotation-to-attribute conversion behind `make rector` / `make rectorf`, scoped to the test
 * suites, and stays as it is.
 *
 * Run it in the application image, never on the host:
 *
 *   make rector-e5 RECTOR_CONFIG=tools/rector/e5-<topic>.php ARGS=--dry-run   # preview
 *   make rector-e5 RECTOR_CONFIG=tools/rector/e5-<topic>.php                  # apply
 *
 * (`make rector-e5 RECTOR_CONFIG=tools/rector/skeleton.php` itself exits 0 and prints Rector's
 * "Register rules or sets" warning: there is nothing to apply.)
 *
 * After a real config, `make csf`: Rector writes fully qualified names, which the coding standard rejects. The
 * Rector output and the `make csf` re-sort belong in the PR's tool-output commit; hand fixes go
 * in a separate commit after it (#460 §3.2, §12).
 *
 * The paths are every first-party PHP directory the gates scan -- production code as well as
 * tests, because E5 changes Core's public API and the call sites live in every package. Names
 * are not imported (no withImportNames()): this codebase imports namespaces, not classes, and
 * Rector's importer would fight tools/check-naming.php's alias rule.
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
