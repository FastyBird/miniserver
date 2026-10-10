<?php declare(strict_types = 1);

/**
 * T2 (included by census.php): every interface declared in a package's src/, with its K1-K4
 * reason or "collapse"/"delete" (#460 §3.1, #462 D6).
 *
 * Implementers are transitive and production-only (src/ of any package, Core included; tests/
 * excluded). R1 (docs/conventions.md): an implementer that extends another implementer does not
 * count towards K1 or K2, so K1 needs two ROOTS -- two independent implementing chains.
 *
 *   K1  two or more R1 roots
 *   K2  a root in another package
 *   K3  a substitution point: findByType()/getByType()/getDefinitionByType()/setImplement() names
 *       it in src/, or a NEON file does (a Nette generated factory has no implementer and is
 *       registered with setImplement())
 *   D6  a package's Exceptions\Exception marker: kept while it has an implementer; with none and
 *       no catch site, deleted
 *
 * Group counts reproduce #462 §1.4's table, which counted implementers before R1.
 */

$implementers = e7Implementers();
$k3 = e7K3Evidence();
$catches = [];

foreach ($index['catches'] as [$class, $file, $line]) {
	if (e7AreaOf($file) === 'src') {
		$catches[$class][] = e7Loc($file, $line);
	}
}

$groups = ['factory' => 0, 'two or more implementers' => 0, 'one implementer, another package' => 0, 'one implementer, same package' => 0, 'no implementer, not a factory' => 0];
$decisions = [];
$out = [];

foreach (e7ByType($packages) as $type => $keys) {
	$rows = [];

	foreach ($keys as $package) {
		foreach (e7SrcDecls($package) as $fqcn => $decl) {
			if ($decl['kind'] !== 'interface') {
				continue;
			}

			$concrete = $implementers[$fqcn]['concrete'] ?? [];
			$roots = $implementers[$fqcn]['roots'] ?? [];
			$foreignRoots = array_values(array_filter($roots, static fn (string $r): bool => e7PackageOf($decls[$r]['file']) !== $package));
			$evidence = $k3[$fqcn] ?? [];
			$isFactory = $concrete === [] && array_filter($evidence, static fn (string $e): bool => str_starts_with($e, 'setImplement()')) !== [];
			$isMarker = str_ends_with($fqcn, '\\Exceptions\\Exception');

			// §1.4's grouping (implementers counted before R1)
			$group = match (true) {
				$concrete === [] && ($isFactory || str_ends_with($fqcn, 'Factory')) => 'factory',
				count($concrete) >= 2 => 'two or more implementers',
				$concrete === [] => 'no implementer, not a factory',
				e7PackageOf($decls[$concrete[0]]['file']) !== $package => 'one implementer, another package',
				default => 'one implementer, same package',
			};
			$groups[$group]++;

			$ks = [];
			$notes = [];

			if (count($roots) >= 2) {
				$ks[] = 'K1';
			} elseif (count($concrete) >= 2) {
				$notes[] = sprintf('K1 only through subclasses of one root (R1): %d concrete, root `%s`', count($concrete), e7Short($roots[0] ?? '?'));
			}

			if ($foreignRoots !== []) {
				$ks[] = 'K2';
			}

			if ($evidence !== []) {
				$ks[] = 'K3';
			}

			$implText = $concrete === []
				? ($roots === [] ? 'none' : 'abstract only: ' . implode(', ', array_map(static fn (string $r): string => '`' . e7Short($r) . '`', $roots)))
				: sprintf(
					'%d concrete, %d root(s)%s: %s',
					count($concrete),
					count($roots),
					$foreignRoots !== [] ? sprintf(' (%d in another package)', count($foreignRoots)) : '',
					implode(', ', array_map(
						static fn (string $r): string => '`' . e7Short($r) . '` ' . e7Loc($decls[$r]['file'], $decls[$r]['line']),
						array_slice($roots, 0, 3),
					)) . (count($roots) > 3 ? ', …' : ''),
				);

			$subInterfaces = array_keys(array_filter(
				$decls,
				static fn (array $d): bool => $d['kind'] === 'interface' && in_array($fqcn, $d['extends'], true),
			));

			if ($concrete === [] && $subInterfaces !== []) {
				$notes[] = 'extended by ' . implode(', ', array_map(
					static fn (string $i): string => '`' . e7Short($i) . '`' . (($k3[$i] ?? []) !== [] ? ' (' . explode(' ', $k3[$i][0])[0] . ')' : ''),
					$subInterfaces,
				));
			}

			$decision = match (true) {
				$isMarker && $concrete !== [] => 'keep (D6 marker)',
				$isMarker && $concrete === [] && ($catches[$fqcn] ?? []) === [] => 'delete (D6: no implementer, no catch site)',
				$isMarker => 'ESCALATE: marker with no implementer but a catch site',
				$ks !== [] => 'keep (' . implode(', ', $ks) . ')',
				$concrete === [] && $roots === [] && $subInterfaces !== [] => 'ESCALATE: no implementer and no K3 of its own; the parent of generated factories',
				$concrete === [] && $roots === [] && str_ends_with($fqcn, 'Factory') => 'ESCALATE: a generated-factory shape that nothing registers (no setImplement())',
				$concrete === [] && $roots === [] => 'ESCALATE: no implementer, not a factory',
				count($roots) === 1 && count($concrete) >= 2 => 'OPEN: collapse under R1, or keep (see notes)',
				default => 'collapse into `' . e7Short($roots[0], $package) . '`',
			};

			if ($isMarker && ($catches[$fqcn] ?? []) !== []) {
				$notes[] = 'caught at ' . implode(', ', array_slice($catches[$fqcn], 0, 3)) . (count($catches[$fqcn]) > 3 ? sprintf(' (+%d)', count($catches[$fqcn]) - 3) : '');
			}

			$decisions[preg_replace('~ \(.*$|: .*$| into .*$~', '', $decision)][] = $fqcn;
			$rows[] = [
				'`' . e7Short($fqcn) . '` ' . e7Loc($decl['file'], $decl['line']),
				$implText,
				$ks === [] ? '—' : implode(', ', $ks),
				implode('; ', array_merge(array_slice($evidence, 0, 3), count($evidence) > 3 ? [sprintf('+%d more', count($evidence) - 3)] : [], $notes)) ?: '—',
				$decision,
			];
		}
	}

	echo "### T2 — {$type}: interfaces (" . count($rows) . ")\n\n";
	e7Table(['Interface', 'Production implementers (transitive; R1 roots)', 'K', 'K3 evidence / notes', 'Decision'], $rows);
}

echo "### T2 — totals\n\n";
e7Table(['#462 §1.4 group (implementers before R1)', 'Count'], array_map(null, array_keys($groups), array_values($groups)));
e7Table(['Decision', 'Count', 'Interfaces'], array_map(
	static fn (string $d, array $list): array => [$d, count($list), count($list) <= 12 ? implode(', ', array_map(static fn (string $f): string => '`' . e7Short($f) . '`', $list)) : '—'],
	array_keys($decisions),
	array_values($decisions),
));
