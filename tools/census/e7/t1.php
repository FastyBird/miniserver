<?php declare(strict_types = 1);

/**
 * T1 (included by census.php): every `I`-prefixed interface and `T`-prefixed trait in the 28
 * packages, with the proposed role name (names.php) and the collision a mechanical drop of the
 * prefix would cause.
 *
 * The collision column checks three things, all against the index:
 *   - a type with the bare name in the SAME namespace (a hard collision: it cannot be declared);
 *   - a type with the bare short name elsewhere in the same package (a soft one: every file that
 *     imports both would need an alias, and a reader confuses the two);
 *   - a type with the bare short name in a package that USES this one (the RedisDb bridges
 *     implement the modules' state interfaces next to their own `…Repository`/`…Manager`).
 * The users are the implementers (interfaces) or the classes using the trait, from src/ and
 * tests/; "refs" is the number of files outside the declaring directory that name the type.
 */

$names = require __DIR__ . '/names.php';
$k3 = e7K3Evidence();

// Short names -> FQCNs, per package, for the soft-collision check.
$byShort = [];

foreach ($decls as $fqcn => $decl) {
	if (!$decl['anonymous']) {
		$byShort[substr($fqcn, strrpos($fqcn, '\\') + 1)][] = $fqcn;
	}
}

// Every file that names an I…/T… type, by its resolved FQCN (imports and aliases followed).
$refsByType = [];

foreach ($index['prefixedRefs'] as [$name, $file, $line]) {
	$refsByType[$name][$file][] = $line;
}

$total = ['interface' => 0, 'trait' => 0];
$missing = [];

foreach (e7ByType($packages) as $type => $keys) {
	$rows = [];

	foreach ($keys as $package) {
		foreach (e7SrcDecls($package) as $fqcn => $decl) {
			$short = substr($fqcn, strrpos($fqcn, '\\') + 1);

			if (
				!(($decl['kind'] === 'interface' && preg_match('/^I[A-Z]/', $short) === 1)
				|| ($decl['kind'] === 'trait' && preg_match('/^T[A-Z]/', $short) === 1))
			) {
				continue;
			}

			$total[$decl['kind']]++;
			$namespace = substr($fqcn, 0, strrpos($fqcn, '\\'));
			$bare = substr($short, 1);
			$collisions = [];

			if (isset($decls[$namespace . '\\' . $bare])) {
				$collisions[] = 'same namespace: `' . e7Short($namespace . '\\' . $bare, $package) . '`';
			}

			foreach ($byShort[$bare] ?? [] as $other) {
				if ($other === $namespace . '\\' . $bare) {
					continue;
				}

				$otherPackage = e7PackageOf($decls[$other]['file']);

				if ($otherPackage === $package && e7AreaOf($decls[$other]['file']) === 'src') {
					$collisions[] = 'package: `' . e7Short($other, $package) . '`';
				}
			}

			// users
			$users = [];

			foreach ($decls as $userFqcn => $userDecl) {
				$list = $decl['kind'] === 'trait' ? $userDecl['traits'] : ($userDecl['kind'] === 'interface' ? $userDecl['extends'] : $userDecl['implements']);

				if (in_array($fqcn, $list, true)) {
					$users[] = $userFqcn;
				}
			}

			foreach ($users as $user) {
				$userPackage = e7PackageOf($decls[$user]['file']);

				if ($userPackage !== $package) {
					foreach ($byShort[$bare] ?? [] as $other) {
						if (e7PackageOf($decls[$other]['file']) === $userPackage && e7AreaOf($decls[$other]['file']) === 'src') {
							$collisions[] = 'user package: `' . e7Short($other) . '`';
						}
					}
				}
			}

			$collisions = array_values(array_unique($collisions));
			$soft = array_values(array_filter($collisions, static fn (string $c): bool => str_starts_with($c, 'package:')));
			$collisions = array_values(array_filter($collisions, static fn (string $c): bool => !str_starts_with($c, 'package:')));

			if ($soft !== []) {
				$collisions[] = sprintf('%d same-named type(s) elsewhere in the package, e.g. %s', count($soft), implode(', ', array_map(
					static fn (string $c): string => substr($c, strlen('package: ')),
					array_slice($soft, 0, 2),
				)));
			}

			// references outside the declaring directory, by resolved name
			$directory = dirname($decl['file']);
			$refFiles = array_filter(
				$refsByType[$fqcn] ?? [],
				static fn (string $file): bool => dirname($file) !== $directory,
				ARRAY_FILTER_USE_KEY,
			);
			$refLines = array_sum(array_map('count', $refFiles));

			$refPackages = array_unique(array_map('e7PackageOf', array_keys($refFiles)));
			$proposal = $names[$fqcn] ?? null;

			if ($proposal === null) {
				$missing[] = $fqcn;
			}

			$proposalCheck = '—';

			if ($proposal !== null) {
				$taken = [];

				foreach ($byShort[$proposal[0]] ?? [] as $other) {
					$otherPackage = e7PackageOf($decls[$other]['file']);

					if ($other === $namespace . '\\' . $proposal[0]) {
						$taken[] = 'TAKEN in its namespace';
					} elseif ($otherPackage === $package || in_array($otherPackage, array_map(static fn (string $u): string => e7PackageOf($decls[$u]['file']), $users), true)) {
						$taken[] = '`' . e7Short($other) . '` exists';
					}
				}

				$proposalCheck = $taken === [] ? 'free' : implode('; ', array_unique($taken));
			}

			$k = '';

			if ($decl['kind'] === 'interface') {
				$foreign = array_filter($users, static fn (string $u): bool => e7PackageOf($decls[$u]['file']) !== $package);
				$k = ($foreign !== [] ? 'K2' : '') . (($k3[$fqcn] ?? []) !== [] ? ($foreign !== [] ? ', ' : '') . 'K3: ' . $k3[$fqcn][0] : '');
			}

			$rows[] = [
				'`' . e7Short($fqcn) . '` ' . e7Loc($decl['file'], $decl['line']),
				$decl['kind'],
				(implode(', ', array_map(static fn (string $u): string => '`' . e7Short($u) . '`', array_slice($users, 0, 5)))
					. (count($users) > 5 ? sprintf(', … (%d in all)', count($users)) : '')) ?: 'none',
				sprintf('%d lines in %d files (%s)', $refLines, count($refFiles), implode(', ', array_map(static fn (string $p): string => $p === '' ? 'root' : $p, $refPackages)) ?: '—'),
				$collisions === [] ? 'none' : implode('; ', $collisions),
				$proposal === null ? '**MISSING**' : '**`' . $proposal[0] . '`**' . ($proposal[2] ?? false ? ' (OPEN)' : '') . ' — ' . $proposalCheck,
				$proposal[1] ?? '',
			];

			if ($k !== '') {
				$rows[count($rows) - 1][2] .= ' — ' . $k;
			}
		}
	}

	if ($rows === []) {
		continue;
	}

	echo "### T1 — {$type}: `I`-interfaces and `T`-traits (" . count($rows) . ")\n\n";
	e7Table(['Type', 'Kind', 'Users (implementers / using classes)', 'Referenced outside its directory', 'Collision of a bare drop', 'Proposed name', 'Role, and why this name'], $rows);
}

printf("T1 total: %d `I`-interfaces, %d `T`-traits.\n", $total['interface'], $total['trait']);

if ($missing !== []) {
	printf("\nMISSING a proposal in names.php: %s\n", implode(', ', $missing));
}
