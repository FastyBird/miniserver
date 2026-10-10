<?php declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\ValueObject\PhpVersion;

// The `src` directory of every package E7_PACKAGE names: a comma-separated list of `<Type>` or
// `<Type>/<Name>`, all 28 packages when it is unset.
$paths = (static function (): array {
	$all = glob(__DIR__ . '/../../src/FastyBird/{Addon,Automator,Bridge,Connector,Module,Plugin}/*/src', GLOB_BRACE | GLOB_ONLYDIR);
	$filter = trim((string) getenv('E7_PACKAGE'));

	if ($all === false || $all === []) {
		throw new RuntimeException('no package src directory found');
	}

	if ($filter === '') {
		return $all;
	}

	$selected = [];

	foreach (explode(',', $filter) as $package) {
		$package = trim($package, " /\t");
		$matched = array_filter(
			$all,
			static fn (string $path): bool => str_contains($path, '/src/FastyBird/' . $package . '/'),
		);

		if ($package === '' || $matched === []) {
			throw new InvalidArgumentException(sprintf('E7_PACKAGE: no package matches "%s"', $package));
		}

		array_push($selected, ...array_values($matched));
	}

	return array_values(array_unique($selected));
})();

/**
 * Epic E7 (#462 §3 D4, item 6): `readonly class` where every property is readonly, in the 28
 * packages outside Core. Copied from tools/rector/skeleton.php, which documents the skips and
 * why names are not imported; one rule, nothing else.
 *
 *   - ReadOnlyClassRector: a class whose properties are all readonly, promoted ones included,
 *     becomes a `readonly class`. The rule itself skips an abstract class, a class with a
 *     parent that is not readonly, and one with a static or an untyped property.
 *
 * Run it AFTER the PR's `final` commit and keep only the hunks on classes the census lists as
 * T5 candidates (docs/superpowers/plans/2026-10-10-e7-census.md): a readonly parent forces every
 * child to be readonly, so the rule is applied to `final` classes only (#462 §1.5). Rector does
 * not know that, and on an open class its output is reverted by hand. A Doctrine entity never
 * becomes readonly (§15.2). PHPStan's class.readOnly check is the backstop, as it was for Core
 * (#492).
 *
 * The paths are the 28 packages' `src` directories only. A package PR limits the run to its own
 * packages with E7_PACKAGE, a comma-separated list of `<Type>` or `<Type>/<Name>`:
 *
 *   E7_PACKAGE=Plugin make rector-e5 RECTOR_CONFIG=tools/rector/e7-readonly.php ARGS=--dry-run   # preview
 *   E7_PACKAGE=Plugin make rector-e5 RECTOR_CONFIG=tools/rector/e7-readonly.php                  # apply
 *   make csf                                                                                     # then
 */
return RectorConfig::configure()
	->withPaths($paths)
	->withSkip([
		'*/node_modules/*',
		'*/assets/*',
	])
	->withCache(__DIR__ . '/../../var/tools/Rector')
	->withPhpVersion(PhpVersion::PHP_84)
	->withRules([ReadOnlyClassRector::class]);
