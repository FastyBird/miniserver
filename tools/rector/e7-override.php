<?php declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
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
 * Epic E7 (#462 §3 D4, item 3): `#[\Override]` on every genuine override in the 28 packages
 * outside Core. Copied from tools/rector/skeleton.php, which documents the skips and why names
 * are not imported; one rule, nothing else.
 *
 *   - AddOverrideAttributeToOverriddenMethodsRector, with add_to_interface_methods ON: a method
 *     that implements an interface method gets the attribute as well as one that overrides a
 *     parent class method. That is how E2 applied the same rule to Core (#492: 507 attributes in
 *     124 files), so Core and the packages follow one rule, and it is what the D10 guard
 *     (PHPStan's checkMissingOverrideMethodAttribute) will demand. The rule's other option,
 *     allow_override_empty_method, stays at its default (off), as it did for Core: an override of
 *     an empty parent method is left alone.
 *
 * The paths are the 28 packages' `src` directories only -- never Core, never tests: E7 rolls
 * Core's conventions out to the packages' production code. A package PR limits the run to its
 * own packages with E7_PACKAGE, a comma-separated list of `<Type>` or `<Type>/<Name>`:
 *
 *   E7_PACKAGE=Plugin make rector-e5 RECTOR_CONFIG=tools/rector/e7-override.php ARGS=--dry-run   # preview
 *   E7_PACKAGE=Plugin make rector-e5 RECTOR_CONFIG=tools/rector/e7-override.php                  # apply
 *   make csf                                                                                     # then
 *
 * (`make rector-e5` is E5's target; it runs any committed config. Without E7_PACKAGE the run
 * covers all 28 packages, which is the close-out's "0 changes for every package" check,
 * #462 §13 P7.) Rector writes `#[\Override]` fully qualified; `make csf` imports it, which is
 * why D4 runs it straight after. The Rector output and the csf re-sort are the PR's tool-output
 * commit; hand fixes go in a separate commit after it.
 */
return RectorConfig::configure()
	->withPaths($paths)
	->withSkip([
		'*/node_modules/*',
		'*/assets/*',
	])
	->withCache(__DIR__ . '/../../var/tools/Rector')
	->withPhpVersion(PhpVersion::PHP_84)
	->withConfiguredRule(AddOverrideAttributeToOverriddenMethodsRector::class, [
		AddOverrideAttributeToOverriddenMethodsRector::ADD_TO_INTERFACE_METHODS => true,
	]);
