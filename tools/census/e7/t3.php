<?php declare(strict_types = 1);

/**
 * T3 (included by census.php): every get/set accessor pair in the 28 packages' src/, by E5's T6
 * rule (tools/census/e5/members.php accessors, re-rooted per package): a public get<X>()/is<X>()/
 * has<X>() whose body is `return $this->p;`, and a set<X>($v) whose body is `$this->p = $v;`
 * (optionally followed by `return $this;`), on the same property, both declared by the class or
 * trait itself. Bodies are matched on PhpToken::tokenize() of the method's lines.
 *
 * In scope unless excluded (#462 §1.4, docs/conventions.md "A get/set pair is a property"):
 *   entity          a Doctrine-mapped class, a subclass of one, or a trait one of them uses
 *                   (hydration bypasses hooks)
 *   MappedObject    an orisai/object-mapper MappedObject
 *   fluent          the setter returns $this (a builder)
 */

/** @return list<string> significant tokens of a method body */
function e7BodyTokens(ReflectionMethod $method): array
{
	$lines = file((string) $method->getFileName()) ?: [];
	$source = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
	$significant = [];
	$depth = 0;
	$inside = false;

	foreach (PhpToken::tokenize('<?php ' . $source) as $token) {
		if ($token->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG])) {
			continue;
		}

		if ($token->text === '{') {
			$depth++;

			if ($depth === 1) {
				$inside = true;

				continue;
			}
		}

		if ($token->text === '}') {
			$depth--;

			if ($depth === 0) {
				break;
			}
		}

		if ($inside) {
			$significant[] = $token->text;
		}
	}

	return $significant;
}

// Entity scope: mapped classes, their subclasses, and every trait any of them uses.
$entityScope = [];

foreach ($decls as $fqcn => $decl) {
	if ($decl['kind'] !== 'class') {
		continue;
	}

	$mapped = e7IsMapped($decl);

	foreach (e7Ancestors($fqcn) as $ancestor) {
		$mapped = $mapped || (isset($decls[$ancestor]) && e7IsMapped($decls[$ancestor]));
	}

	if ($mapped) {
		$entityScope[$fqcn] = true;

		foreach ($decl['traits'] as $trait) {
			$entityScope[$trait] = true;
		}
	}
}

$totals = ['pairs' => 0, 'in scope' => 0, 'entity' => 0, 'MappedObject' => 0, 'fluent' => 0];

foreach (e7ByType($packages) as $type => $keys) {
	$rows = [];
	$excluded = [];

	foreach ($keys as $package) {
		foreach (e7SrcDecls($package) as $fqcn => $decl) {
			if ($decl['kind'] !== 'class' && $decl['kind'] !== 'trait') {
				continue;
			}

			$reflection = e7Reflect($fqcn);

			if ($reflection === null) {
				continue;
			}

			$getters = [];
			$setters = [];

			foreach ($reflection->getMethods() as $method) {
				if ($method->getDeclaringClass()->getName() !== $fqcn || $method->isStatic() || $method->isAbstract()) {
					continue;
				}

				$body = e7BodyTokens($method);

				if (
					$method->isPublic()
					&& preg_match('/^(get|is|has)[A-Z]/', $method->getName()) === 1
					&& $method->getNumberOfParameters() === 0
					&& count($body) === 5 && $body[0] === 'return' && $body[1] === '$this' && $body[2] === '->' && $body[4] === ';'
				) {
					$getters[$body[3]][] = $method;
				}

				if (preg_match('/^set[A-Z]/', $method->getName()) === 1 && $method->getNumberOfParameters() === 1) {
					$param = '$' . $method->getParameters()[0]->getName();
					$fluent = $body === ['$this', '->', $body[2] ?? '', '=', $param, ';', 'return', '$this', ';'];

					if ((count($body) === 6 && $body[0] === '$this' && $body[1] === '->' && $body[3] === '=' && $body[4] === $param && $body[5] === ';') || $fluent) {
						$setters[$body[2]][] = [$method, $fluent];
					}
				}
			}

			foreach ($getters as $property => $propertyGetters) {
				foreach ($propertyGetters as $getter) {
					foreach ($setters[$property] ?? [] as [$setter, $fluent]) {
						$totals['pairs']++;
						$reason = match (true) {
							isset($entityScope[$fqcn]) => 'entity',
							$reflection->isSubclassOf('Orisai\\ObjectMapper\\MappedObject') => 'MappedObject',
							$fluent => 'fluent',
							default => 'in scope',
						};
						$totals[$reason]++;
						$prop = $reflection->hasProperty($property) ? $reflection->getProperty($property) : null;
						$row = [
							'`' . e7Short($fqcn) . '::$' . $property . '`',
							'`' . $getter->getName() . '()` / `' . ($setter->isPublic() ? '' : ($setter->isProtected() ? 'protected ' : 'private ')) . $setter->getName() . '()`',
							$prop !== null ? '`' . $prop->getType() . '`' . ($prop->isReadOnly() ? ' readonly' : '') : '?',
							e7Loc($decl['file'], $getter->getStartLine()),
						];

						if ($reason === 'in scope') {
							$rows[] = $row;
						} else {
							$excluded[$reason][] = '`' . e7Short($fqcn) . '::$' . $property . '`';
						}
					}
				}
			}
		}
	}

	if ($rows === [] && $excluded === []) {
		continue;
	}

	echo "### T3 — {$type}: accessor pairs\n\n";

	if ($rows !== []) {
		echo 'In scope (' . count($rows) . "):\n\n";
		e7Table(['Property', 'Getter / setter', 'Property type', 'Getter at'], $rows);
	} else {
		echo "In scope: none.\n\n";
	}

	foreach ($excluded as $reason => $list) {
		printf("Excluded, %s (%d): %s\n\n", $reason, count($list), implode(', ', $list));
	}
}

echo "### T3 — totals\n\n";
e7Table(['Pairs', 'In scope', 'Entity', 'MappedObject', 'Fluent'], [[$totals['pairs'], $totals['in scope'], $totals['entity'], $totals['MappedObject'], $totals['fluent']]]);
