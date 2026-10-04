<?php declare(strict_types = 1);

/**
 * E5.1 census (#633), table T10: the fbCore configuration schema, walked from the real
 * Nette\Schema objects (CoreExtension::getConfigSchema(), which composes each child's).
 *
 *   php tools/census/e5/schema.php
 *
 * Prints one line per node: <key path> <kind> [<type>] [default=<json>]; kind is "structure"
 * for a nested Expect::structure() and "leaf" otherwise. Structures are what a typed
 * configuration class (Expect::from) needs one class for.
 */

require __DIR__ . '/../../../vendor/autoload.php';

use Nette\Schema;

$ext = new FastyBird\Core\DI\CoreExtension();
$schema = $ext->getConfigSchema();

$prop = static function (object $o, string $name): mixed {
	$r = new ReflectionObject($o);

	while (!$r->hasProperty($name) && $r->getParentClass() !== false) {
		$r = $r->getParentClass();
	}

	return $r->hasProperty($name) ? $r->getProperty($name)->getValue($o) : null;
};

$walk = static function (Schema\Schema $s, string $path) use (&$walk, $prop): void {
	if ($s instanceof Schema\Elements\Structure) {
		if ($path !== '') {
			echo $path, "\tstructure\n";
		}

		foreach ($prop($s, 'items') as $k => $item) {
			$walk($item, ltrim($path . '.' . $k, '.'));
		}

		return;
	}

	$type = $s instanceof Schema\Elements\Type ? (string) $prop($s, 'type') : get_class($s);

	if ($s instanceof Schema\Elements\AnyOf) {
		$type = 'anyOf(' . implode('|', array_map(
			static fn ($x) => $x instanceof Schema\Elements\Type ? (string) $prop($x, 'type') : (is_object($x) ? get_class($x) : var_export($x, true)),
			$prop($s, 'set'),
		)) . ')';
	}

	echo $path, "\tleaf\t", $type, "\tdefault=", json_encode($prop($s, 'default'), JSON_UNESCAPED_SLASHES),
		$prop($s, 'required') ? "\trequired" : '', "\n";
};

$walk($schema, '');
