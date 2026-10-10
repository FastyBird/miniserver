<?php declare(strict_types = 1);

/**
 * T4 and T5 (included by census.php).
 *
 * T4: every named class in the 28 packages' src/. A non-final, non-abstract class stays OPEN when
 *   extended   some class names it as its parent -- the `extends` census covers src/, tests/,
 *              bin/, public/ and migrations/, anonymous classes included, by resolved FQCN
 *   mapped     it is Doctrine-mapped (#[ORM\Entity|MappedSuperclass|Embeddable]); Doctrine
 *              proxies subclass it, so it is never final (#462 §6)
 * and is a `final` CANDIDATE otherwise. A candidate that a test mocks by its own name
 * (createMock(X::class) and friends) is flagged: E2 left three such classes open because PHPStan's
 * tests configuration could not type the mock (#492).
 *
 * T5: `readonly class` candidates: classes that are final today or are T4 candidates, are not
 *   Doctrine-mapped, not already readonly, have no #[AllowDynamicProperties], no parent or a
 *   readonly parent, use no trait that declares a property, and declare at least one property,
 *   every one of them (promoted ones included) readonly, typed and not static -- the conditions of
 *   Rector's ReadOnlyClassRector (ReadonlyClassManipulator), which tools/rector/e7-readonly.php
 *   applies. "final today" is what that rule sees before the `final` commit.
 */

$mocked = [];

foreach ($index['mocks'] as [$class, $file, $line]) {
	if ($class !== null) {
		$mocked[$class][] = e7Loc($file, $line);
	}
}

$totals = [];
$t5Totals = ['final today' => 0, 'after T4' => 0];

foreach (e7ByType($packages) as $type => $keys) {
	$candidateLines = [];
	$openRows = [];
	$readonlyRows = [];
	$count = ['classes' => 0, 'final' => 0, 'abstract' => 0, 'mapped' => 0, 'extended' => 0, 'candidates' => 0, 'mocked candidates' => 0];

	foreach ($keys as $package) {
		$candidates = [];

		foreach (e7SrcDecls($package) as $fqcn => $decl) {
			if ($decl['kind'] !== 'class') {
				continue;
			}

			$count['classes']++;
			$isCandidate = false;

			if ($decl['final']) {
				$count['final']++;
			} elseif ($decl['abstract']) {
				$count['abstract']++;
			} else {
				$extendedAt = e7ExtendedAt($fqcn);
				$mapped = e7IsMapped($decl);

				if ($mapped) {
					$count['mapped']++;
				}

				if ($extendedAt !== []) {
					$count['extended']++;
				}

				if (!$mapped && $extendedAt === []) {
					$isCandidate = true;
					$count['candidates']++;
					$flag = isset($mocked[$fqcn]) ? ' (mocked: ' . implode(', ', array_slice($mocked[$fqcn], 0, 2)) . (count($mocked[$fqcn]) > 2 ? ', …' : '') . ')' : '';
					$count['mocked candidates'] += $flag !== '' ? 1 : 0;
					$candidates[] = '`' . e7Short($fqcn, $package) . '`' . $flag;
				} else {
					$reasons = [];

					if ($mapped) {
						$reasons[] = 'Doctrine-mapped (' . implode(', ', array_map(
							static fn (string $a): string => '#[ORM\\' . substr($a, strrpos($a, '\\') + 1) . ']',
							array_values(array_intersect($decl['attributes'], E7_ORM_MAPPED)),
						)) . ')';
					}

					if ($extendedAt !== []) {
						$reasons[] = sprintf(
							'extended by %d: %s',
							count($extendedAt),
							implode(', ', array_map(
								static fn (array $e): string => (str_starts_with($e['child'], 'class@anonymous') ? 'an anonymous class' : '`' . e7Short($e['child']) . '`') . ' ' . e7Loc($e['file'], $e['line']),
								array_slice($extendedAt, 0, 2),
							)) . (count($extendedAt) > 2 ? ', …' : ''),
						);
					}

					$openRows[] = ['`' . e7Short($fqcn) . '` ' . e7Loc($decl['file'], $decl['line']), implode('; ', $reasons)];
				}
			}

			// T5
			if ($table !== 't5' || (!$decl['final'] && !$isCandidate) || $decl['readonly'] || e7IsMapped($decl)) {
				continue;
			}

			$reflection = e7Reflect($fqcn);

			if ($reflection === null || $reflection->getAttributes('AllowDynamicProperties') !== []) {
				continue;
			}

			$parent = $reflection->getParentClass();

			if ($parent !== false && !$parent->isReadOnly()) {
				continue;
			}

			$traitProperties = false;

			foreach ($reflection->getTraits() as $trait) {
				$traitProperties = $traitProperties || $trait->getProperties() !== [];
			}

			$own = array_filter($reflection->getProperties(), static fn (ReflectionProperty $p): bool => $p->getDeclaringClass()->getName() === $fqcn);

			if ($traitProperties || $own === []) {
				continue;
			}

			$eligible = true;

			foreach ($own as $property) {
				$eligible = $eligible && $property->isReadOnly() && !$property->isStatic() && $property->hasType();
			}

			if ($eligible) {
				$t5Totals[$decl['final'] ? 'final today' : 'after T4']++;
				$readonlyRows[$package][] = '`' . e7Short($fqcn, $package) . '`' . ($decl['final'] ? '' : ' (after T4)');
			}
		}

		if ($candidates !== []) {
			$candidateLines[] = sprintf('- **%s** (%d): %s', $package, count($candidates), implode(', ', $candidates));
		}
	}

	$totals[$type] = $count;

	if ($table === 't4') {
		echo "### T4 — {$type}: `final`\n\n";
		e7Table(array_keys($count), [array_values($count)]);
		echo "Final candidates, by package:\n\n", implode("\n", $candidateLines), "\n\n";
		echo 'Left open (' . count($openRows) . "):\n\n";
		e7Table(['Class', 'Reason'], $openRows);
	} else {
		echo "### T5 — {$type}: `readonly class` candidates (" . array_sum(array_map('count', $readonlyRows)) . ")\n\n";

		foreach ($readonlyRows as $package => $rows) {
			printf("- **%s** (%d): %s\n", $package, count($rows), implode(', ', $rows));
		}

		echo "\n";
	}
}

if ($table === 't4') {
	echo "### T4 — totals\n\n";
	$sum = array_fill_keys(array_keys(reset($totals) ?: []), 0);

	foreach ($totals as $count) {
		foreach ($count as $k => $v) {
			$sum[$k] += $v;
		}
	}

	e7Table(['Type', ...array_keys($sum)], [...array_map(static fn (string $t, array $c): array => [$t, ...array_values($c)], array_keys($totals), array_values($totals)), ['**Total**', ...array_values($sum)]]);
	echo "A class both mapped and extended is counted in both columns; `candidates` = classes − final − abstract − (mapped ∪ extended).\n";
} else {
	echo "### T5 — totals\n\n";
	e7Table(['Final today (what ReadOnlyClassRector sees now)', 'Becomes eligible after the T4 `final` commit', 'Total'], [[$t5Totals['final today'], $t5Totals['after T4'], $t5Totals['final today'] + $t5Totals['after T4']]]);
}
