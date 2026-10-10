<?php declare(strict_types = 1);

/**
 * T8 (included by census.php): runtime service locators outside Core (#462 §1.7, D4 item 9).
 *
 * A locator is a class outside a `\DI\` namespace that takes Nette\DI\Container (constructor
 * parameter or property, Reflection) and calls getByType()/findByType()/getByName()/getService()/
 * createService()/findByTag() on it. A DI extension's compile-time use of the builder is not one.
 *
 * With --snapshot <dir> (a tools/di-snapshot.php recording), each lookup is checked against the
 * compiled containers for a dependency cycle (a service "holds the locator" when its type is the
 * locator class or a subclass of it -- Ui's Widget hydrator is abstract): injecting the looked-up service T into the locator's
 * service S closes a cycle when T already reaches S through constructor arguments, setup calls or
 * factories. The table gives, per lookup, how many containers have such a path, the first path
 * found, and whether it already passes through a lazy definition (which breaks the construction
 * cycle). `lazy` is needed where a path exists that no lazy definition breaks.
 */

$snapshot = $options['snapshot'] ?? null;
$containers = [];

if ($snapshot !== null) {
	$snapshotIndex = json_decode((string) file_get_contents($snapshot . '/index.json'), true);

	foreach ($snapshotIndex['containers'] ?? [] as $id => $entry) {
		$data = json_decode((string) file_get_contents($snapshot . '/' . (is_array($entry) ? $entry['file'] : $entry)), true);

		if (($data['compiled'] ?? true) === false) {
			continue;
		}

		$containers[$id] = $data;
	}
}

/**
 * @return list<string>
 */
function e7SnapshotRefs(mixed $value): array
{
	$refs = [];

	if (is_array($value)) {
		if (isset($value['@']) && is_string($value['@'])) {
			$refs[] = $value['@'];
		}

		foreach ($value as $item) {
			array_push($refs, ...e7SnapshotRefs($item));
		}
	}

	return $refs;
}

/**
 * Shortest reference path from any service of type $from to any service of type $to.
 *
 * @return array{path: list<string>, lazy: bool}|null
 */
function e7SnapshotReach(array $container, string $from, string $to): array|null
{
	$services = $container['services'];
	$aliases = $container['aliases'] ?? [];
	$graph = [];

	foreach ($services as $name => $service) {
		$targets = [];

		foreach (e7SnapshotRefs([$service['arguments'] ?? [], $service['setup'] ?? [], $service['factory'] ?? null]) as $ref) {
			$ref = $aliases[$ref] ?? $ref;

			if (isset($services[$ref]) && $ref !== $name) {
				$targets[$ref] = true;
			}
		}

		$graph[$name] = array_keys($targets);
	}

	$isType = static function (string $name, string $type) use ($services): bool {
		$serviceType = $services[$name]['type'] ?? null;

		return is_string($serviceType) && ($serviceType === $type || (e7Reflect($serviceType) !== null && is_a($serviceType, $type, true)));
	};

	foreach (array_keys($services) as $start) {
		if (!$isType($start, $from)) {
			continue;
		}

		$previous = [$start => null];
		$queue = [$start];

		while ($queue !== []) {
			$current = array_shift($queue);

			foreach ($graph[$current] ?? [] as $next) {
				if (array_key_exists($next, $previous)) {
					continue;
				}

				$previous[$next] = $current;

				if ($isType($next, $to)) {
					$path = [$next];

					for ($p = $current; $p !== null; $p = $previous[$p]) {
						$path[] = $p;
					}

					$path = array_reverse($path);
					$lazy = false;

					foreach (array_slice($path, 0, -1) as $node) {
						$lazy = $lazy || ($services[$node]['lazy'] ?? false) === true;
					}

					return ['path' => $path, 'lazy' => $lazy];
				}

				$queue[] = $next;
			}
		}
	}

	return null;
}

$rows = [];
$byClass = [];

foreach ($index['lookups'] as [$method, $class, $receiver, $file, $line, $current]) {
	if (
		$current === null
		|| e7AreaOf($file) !== 'src'
		|| !isset($packages[e7PackageOf($file)])
		|| str_contains($current, '\\DI\\')
		|| !in_array($method, ['getByType', 'findByType', 'getByName', 'getService', 'createService', 'findByTag'], true)
	) {
		continue;
	}

	$reflection = e7Reflect($current);
	$takesContainer = false;

	foreach ($reflection?->getProperties() ?? [] as $property) {
		$takesContainer = $takesContainer || (string) $property->getType() === 'Nette\\DI\\Container';
	}

	if (!$takesContainer) {
		continue;
	}

	$byClass[$current][] = [$method, $class, $file, $line];
}

foreach ($byClass as $locator => $lookups) {
	$decl = $decls[$locator];
	$package = e7PackageOf($decl['file']);
	$typed = array_values(array_unique(array_filter(array_column($lookups, 1))));
	$cycle = [];

	foreach ($typed as $target) {
		if ($containers === []) {
			$cycle[] = 'run with --snapshot';

			continue;
		}

		$found = 0;
		$unbroken = 0;
		$example = null;
		$present = 0;

		foreach ($containers as $id => $container) {
			$hasLocator = false;

			foreach ($container['services'] as $service) {
				$serviceType = $service['type'] ?? null;
				$hasLocator = $hasLocator || (is_string($serviceType) && ($serviceType === $locator || (e7Reflect($serviceType) !== null && is_a($serviceType, $locator, true))));
			}

			if (!$hasLocator) {
				continue;
			}

			$present++;
			$reach = e7SnapshotReach($container, $target, $locator);

			if ($reach !== null) {
				$found++;
				$unbroken += $reach['lazy'] ? 0 : 1;
				$example ??= $id . ': ' . implode(' → ', $reach['path']) . ($reach['lazy'] ? ' (through a lazy definition)' : '');
			}
		}

		$cycle[] = sprintf(
			'`%s` → `%s`: path in %d of the %d containers holding the locator%s%s',
			e7Short($target),
			e7Short($locator),
			$found,
			$present,
			$found > 0 ? sprintf(', %d not broken by a lazy definition', $unbroken) : '',
			$example !== null ? '; e.g. ' . $example : '',
		);
	}

	$rows[] = [
		'`' . e7Short($locator) . '` ' . e7Loc($decl['file'], $decl['line']),
		implode('; ', array_map(
			static fn (array $l): string => '`' . $l[0] . '(' . ($l[1] !== null ? e7Short($l[1]) . '::class' : '…') . ')` ' . e7Loc($l[2], $l[3]),
			$lookups,
		)),
		implode(', ', array_map(static fn (string $t): string => '`' . e7Short($t) . '`', $typed)),
		implode('<br>', $cycle),
	];
}

echo '### T8 — runtime service locators (' . count($rows) . ")\n\n";
e7Table(['Locator class', 'Lookups', 'Type to inject', 'DI cycle check against the compiled containers'], $rows);
