<?php declare(strict_types = 1);

/**
 * T7 (included by census.php): every class constant that a class or interface in one package
 * redeclares from a parent class or interface in ANOTHER package (#462 §1.3, D8).
 *
 * A typed parent constant forces every override to declare a compatible type (a fatal error
 * otherwise), while an untyped parent accepts a typed child, so a family is typed children first. Each family is the
 * parent declaration and all its cross-package overrides, with the type each one has today and
 * the type its value implies. A family whose values disagree on a type is an escalation (D8).
 *
 * Reflection, through the real autoloader, over every class and interface declared in any
 * package's src/ (Core's too, as a parent).
 */

$families = [];
$sameP = 0;

foreach (e7Packages() as $package => $_) {
	foreach (e7SrcDecls($package) as $fqcn => $decl) {
		if ($decl['kind'] !== 'class' && $decl['kind'] !== 'interface') {
			continue;
		}

		$reflection = e7Reflect($fqcn);

		if ($reflection === null) {
			continue;
		}

		$parents = [];

		if ($reflection->getParentClass() !== false) {
			$parents[] = $reflection->getParentClass();
		}

		foreach ($reflection->getInterfaces() as $interface) {
			$parents[] = $interface;
		}

		foreach ($reflection->getReflectionConstants() as $constant) {
			if ($constant->getDeclaringClass()->getName() !== $fqcn) {
				continue;
			}

			foreach ($parents as $parent) {
				if (!$parent->hasConstant($constant->getName())) {
					continue;
				}

				$parentConstant = $parent->getReflectionConstant($constant->getName());
				$origin = $parentConstant->getDeclaringClass();
				$originFile = substr((string) $origin->getFileName(), strlen(e7Root()) + 1);

				// a vendor mirror path maps back to its package
				foreach (e7AllPackages() as $key => $p) {
					if (str_starts_with($originFile, 'vendor/' . $p['composer'] . '/')) {
						$originFile = $p['dir'] . substr($originFile, strlen('vendor/' . $p['composer']));
					}
				}

				$originPackage = e7PackageOf($originFile);

				if ($originPackage === $package) {
					$sameP++;

					break;
				}

				$key = $origin->getName() . '::' . $constant->getName();
				$families[$key]['parent'] ??= [
					'class' => $origin->getName(),
					'package' => $originPackage,
					'type' => $parentConstant->hasType() ? (string) $parentConstant->getType() : null,
					'value' => get_debug_type($parentConstant->getValue()),
					'line' => $originFile . ':' . ($decls[$origin->getName()]['constants'][$constant->getName()]['line'] ?? '?'),
				];
				$families[$key]['children'][] = [
					'class' => $fqcn,
					'package' => $package,
					'type' => $constant->hasType() ? (string) $constant->getType() : null,
					'value' => get_debug_type($constant->getValue()),
					'line' => $decl['file'] . ':' . ($decl['constants'][$constant->getName()]['line'] ?? '?'),
				];

				break;
			}
		}
	}
}

ksort($families);
$rows = [];

foreach ($families as $key => $family) {
	$types = array_unique(array_merge([$family['parent']['value']], array_column($family['children'], 'value')));
	$rows[] = [
		'`' . e7Short($key) . '` `' . $family['parent']['line'] . '` (' . $family['parent']['package'] . ')',
		$family['parent']['type'] === null ? 'untyped' : '`' . $family['parent']['type'] . '`',
		implode('; ', array_map(
			static fn (array $c): string => '`' . e7Short($c['class']) . '` `' . $c['line'] . '` (' . ($c['type'] === null ? 'untyped' : '`' . $c['type'] . '`') . ', ' . $c['value'] . ')',
			$family['children'],
		)),
		count($types) === 1 ? '`' . $types[0] . '`' : '**ESCALATE: values disagree: ' . implode(', ', $types) . '**',
		implode(', ', array_unique(array_column($family['children'], 'package'))) . ' before ' . $family['parent']['package'],
	];
}

echo '### T7 — constants overridden in another package (' . count($families) . " families)\n\n";
e7Table(['Parent constant', 'Parent type today', 'Overrides (type today, value type)', 'Type the family takes', 'D8 order: type these first'], $rows);
printf("Overrides inside one package (not a D8 ordering constraint): %d.\n", $sameP);
