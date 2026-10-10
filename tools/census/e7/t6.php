<?php declare(strict_types = 1);

/**
 * T6 (included by census.php): Nette\SmartObject.
 *
 * Per type: every class or trait in the packages' src/ that uses the trait directly, with the
 * magic it actually relies on, and every subclass of it in ANOTHER package. Then every
 * `@property`/`@property-read`/`@property-write` line in the packages' src/, classified.
 *
 *   magic call   `$this->onX(...)` where the class (or the class using the trait) has no method
 *                onX but has a property of that name: SmartObject::__call() invokes every
 *                callback in the array. Found from the index's `$this->x()` calls and Reflection.
 *   @property    documentation  the class (or every class using the trait) declares the property
 *                live magic     no class declares it, the class is a SmartObject and has a
 *                               get<X>()/is<X>() getter, and `$this-><x>` or `-><x>` is read in it
 *                magic, unread  as live magic, but no read of that name was found
 *                third-party magic  served by a getter of a vendor parent (Nette's
 *                               `Control::getTemplate()` for `$template`): that parent's own
 *                               SmartObject, which E7 does not touch
 *                stale          nothing declares it and nothing serves it
 *   isset/unset  `isset($o->x)` / `unset($o->x)` on a name that only magic serves
 *
 * A subclass in another package "relies on" the magic when it calls an inherited event array
 * through `$this->onX()` or reads an inherited magic property.
 */

$smart = 'Nette\\SmartObject';
$sources = [];

foreach ($index['files'] as $file) {
	if (e7AreaOf($file) === 'src' || e7PackageOf($file) === '') {
		$sources[$file] = (string) file_get_contents(e7Root() . '/' . $file);
	}
}

// classes that use SmartObject anywhere in their hierarchy (directly, via a parent, or via a trait)
$usesSmart = static function (string $fqcn) use (&$usesSmart, $decls, $smart): bool {
	static $memo = [];

	if (isset($memo[$fqcn])) {
		return $memo[$fqcn];
	}

	$memo[$fqcn] = false;
	$decl = $decls[$fqcn] ?? null;

	if ($decl === null) {
		return false;
	}

	foreach ($decl['traits'] as $trait) {
		if ($trait === $smart || $usesSmart($trait)) {
			return $memo[$fqcn] = true;
		}
	}

	foreach ($decl['extends'] as $parent) {
		if ($decl['kind'] === 'class' && $usesSmart($parent)) {
			return $memo[$fqcn] = true;
		}
	}

	return false;
};

// users of each trait, transitively (a trait used by a trait)
$traitUsers = static function (string $trait) use (&$traitUsers, $decls): array {
	$users = [];

	foreach ($decls as $fqcn => $decl) {
		if (in_array($trait, $decl['traits'], true)) {
			$users = $decl['kind'] === 'trait' ? array_merge($users, $traitUsers($fqcn)) : array_merge($users, [$fqcn]);
		}
	}

	return array_values(array_unique($users));
};

$thisCallsByClass = [];

foreach ($index['thisCalls'] as [$class, $method, $file, $line]) {
	if ($class !== null) {
		$thisCallsByClass[$class][] = [$method, $file, $line];
	}
}

$fetchesByName = [];

foreach ($index['fetches'] as [$name, $receiver, $class, $file, $line, $context]) {
	$fetchesByName[$name][] = ['receiver' => $receiver, 'class' => $class, 'file' => $file, 'line' => $line, 'context' => $context];
}

/**
 * `$this->name()` calls in $class that SmartObject::__call() serves: no method, but a property.
 *
 * @return list<string>
 */
$magicCalls = static function (string $class, ReflectionClass $reflection) use ($thisCallsByClass): array {
	$found = [];

	$owners = [$class, ...$reflection->getTraitNames()];
	$calls = array_merge(...array_map(static fn (string $o): array => $thisCallsByClass[$o] ?? [], $owners));

	foreach ($calls as [$method, $file, $line]) {
		if (!$reflection->hasMethod($method) && $reflection->hasProperty($method)) {
			$found[] = '`$this->' . $method . '()` ' . e7Loc($file, $line);
		}
	}

	return $found;
};

/**
 * `$this->x` reads, writes, isset() and unset() in $class that only SmartObject's __get()/__set()
 * can serve: no property x, but a get<X>()/is<X>() (or, for a write, a set<X>()) method.
 *
 * @return list<string>
 */
$magicFetches = static function (string $class, ReflectionClass $reflection) use ($index): array {
	$found = [];

	$owners = [$class, ...$reflection->getTraitNames()];

	foreach ($index['fetches'] as [$name, $receiver, $fetchClass, $file, $line, $context]) {
		if (!in_array($fetchClass, $owners, true) || $receiver !== '$this' || $reflection->hasProperty($name)) {
			continue;
		}

		$accessor = $context === 'write' ? ['set'] : ['get', 'is'];

		foreach ($accessor as $prefix) {
			if ($reflection->hasMethod($prefix . ucfirst($name))) {
				$found[] = $context . ' `$this->' . $name . '` ' . e7Loc($file, $line);

				break;
			}
		}
	}

	return $found;
};

$totals = ['SmartObject classes and traits' => 0, 'files' => 0, 'public array $onX' => 0, 'magic $this->onX() calls' => 0, 'magic $this->x accesses' => 0, 'Arrays::invoke() calls' => 0, 'subclasses in another package' => 0, 'of them relying on magic' => 0];
$propertyRows = [];
$propertyTotals = [];

foreach (e7ByType($packages) as $type => $keys) {
	$rows = [];
	$files = [];
	$count = ['classes' => 0, 'onX' => 0, 'calls' => 0, 'fetches' => 0, 'invoke' => 0, 'subclasses' => 0];

	foreach ($keys as $package) {
		foreach ($sources as $file => $code) {
			if (e7PackageOf($file) === $package && e7AreaOf($file) === 'src') {
				$count['invoke'] += preg_match_all('/\bArrays::invoke\(/', $code);
			}
		}

		foreach (e7SrcDecls($package) as $fqcn => $decl) {
			if (!in_array($smart, $decl['traits'], true)) {
				continue;
			}

			$count['classes']++;
			$files[$decl['file']] = true;
			$reflection = e7Reflect($fqcn);
			$onX = array_keys(array_filter(
				$decl['properties'],
				static fn (array $p, string $name): bool => $p['public'] && preg_match('/^on[A-Z]/', $name) === 1,
				ARRAY_FILTER_USE_BOTH,
			));
			$count['onX'] += count($onX);
			$classes = $decl['kind'] === 'trait' ? $traitUsers($fqcn) : [$fqcn];
			$calls = [];

			$fetchMagic = [];

			foreach ($classes as $class) {
				$classReflection = e7Reflect($class);

				if ($classReflection !== null) {
					$calls = array_merge($calls, $magicCalls($class, $classReflection));
					$fetchMagic = array_merge($fetchMagic, $magicFetches($class, $classReflection));
				}
			}

			$count['fetches'] += count($fetchMagic);

			$count['calls'] += count($calls);

			// subclasses in another package
			$foreign = [];

			foreach ($decls as $child => $childDecl) {
				if ($childDecl['kind'] !== 'class' || !in_array($fqcn, e7Ancestors($child), true)) {
					continue;
				}

				$childPackage = e7PackageOf($childDecl['file']);

				if ($childPackage === $package) {
					continue;
				}

				$childReflection = e7Reflect($child);
				$relies = $childReflection !== null ? array_merge($magicCalls($child, $childReflection), $magicFetches($child, $childReflection)) : [];

				$foreign[] = '`' . e7Short($child) . '` ' . e7Loc($childDecl['file'], $childDecl['line'])
					. ($relies !== [] ? ' — **relies on magic**: ' . implode(', ', $relies) : ' — no magic use found');
				$count['subclasses']++;
				$totals['of them relying on magic'] += $relies !== [] ? 1 : 0;
			}

			if ($onX === [] && $calls === [] && $fetchMagic === [] && $foreign === []) {
				continue;
			}

			$rows[] = [
				'`' . e7Short($fqcn) . '` ' . e7Loc($decl['file'], $decl['line']) . ($decl['kind'] === 'trait' ? ' (trait)' : ''),
				$onX === [] ? '—' : implode(', ', array_map(static fn (string $p): string => '`$' . $p . '`', $onX)),
				$calls === [] ? '—' : implode('; ', $calls),
				$fetchMagic === [] ? '—' : implode('; ', $fetchMagic),
				$foreign === [] ? '—' : implode('; ', $foreign),
			];
		}
	}

	$totals['SmartObject classes and traits'] += $count['classes'];
	$totals['files'] += count($files);
	$totals['public array $onX'] += $count['onX'];
	$totals['magic $this->onX() calls'] += $count['calls'];
	$totals['magic $this->x accesses'] += $count['fetches'];
	$totals['Arrays::invoke() calls'] += $count['invoke'];
	$totals['subclasses in another package'] += $count['subclasses'];

	echo "### T6 — {$type}: `Nette\\SmartObject`\n\n";
	printf(
		"%d classes and traits in %d files use it. %d declare `public array \$onX`; %d `\$this->onX()` calls go through `__call`; %d `\$this->x` accesses go through `__get`/`__set`; %d `Arrays::invoke()` calls. Classes with no event array, no magic call and no subclass elsewhere are not listed: removing the trait from them changes only what an undeclared member access throws.\n\n",
		$count['classes'],
		count($files),
		count(array_filter($rows, static fn (array $r): bool => $r[1] !== '—')),
		$count['calls'],
		$count['fetches'],
		$count['invoke'],
	);

	if ($rows !== []) {
		e7Table(['Class', 'Event arrays', 'Magic `__call` dispatch', 'Magic property access (`__get`/`__set`/`__isset`/`__unset`)', 'Subclasses in another package'], $rows);
	}

	// @property lines of this type
	foreach ($keys as $package) {
		foreach (e7SrcDecls($package) as $fqcn => $decl) {
			if ($decl['doc'] === null || !str_contains((string) $decl['doc'], '@property')) {
				continue;
			}

			$users = $decl['kind'] === 'trait' ? $traitUsers($fqcn) : [$fqcn];

			foreach (explode("\n", (string) $decl['doc']) as $offset => $line) {
				if (preg_match('/@(property(?:-read|-write)?)\s+(\S+)\s+\$(\w+)/', $line, $m) !== 1) {
					continue;
				}

				$name = $m[3];
				$declaredBy = [];
				$smartUsers = [];
				$getterUsers = [];
				$thirdParty = [];

				foreach ($users as $user) {
					$userReflection = e7Reflect($user);

					if ($userReflection === null) {
						continue;
					}

					if ($userReflection->hasProperty($name)) {
						$declaredBy[] = $user;
					}

					if ($usesSmart($user)) {
						$smartUsers[] = $user;
					}

					foreach (['get', 'is'] as $prefix) {
						if ($userReflection->hasMethod($prefix . ucfirst($name))) {
							$getterUsers[] = $user;
							$declaring = $userReflection->getMethod($prefix . ucfirst($name))->getDeclaringClass();

							if (!str_starts_with($declaring->getName(), 'FastyBird\\')) {
								$thirdParty[] = $declaring->getName() . '::' . $prefix . ucfirst($name) . '()';
							}

							break;
						}
					}
				}

				$reads = [];

				if (count($declaredBy) < count($users)) {
					foreach ($fetchesByName[$name] ?? [] as $fetch) {
						$inUser = (in_array($fetch['class'], $users, true) || $fetch['class'] === $fqcn) && $fetch['receiver'] === '$this';
						$unknown = $fetch['receiver'] !== '$this' && e7AreaOf($fetch['file']) !== 'tests';

						if ($inUser || $unknown) {
							$reads[] = $fetch['context'] . ' ' . e7Loc($fetch['file'], $fetch['line']) . ($inUser ? '' : ' (receiver ' . $fetch['receiver'] . ', type not resolved)');
						}
					}
				}

				$class = match (true) {
					$users === [] => 'stale (no user)',
					count($declaredBy) === count($users) => 'documentation',
					$thirdParty !== [] => 'third-party magic',
					$smartUsers !== [] && $getterUsers !== [] && array_filter($reads, static fn (string $r): bool => !str_contains($r, 'type not resolved')) !== [] => 'live magic',
					$smartUsers !== [] && $getterUsers !== [] => 'magic, unread',
					default => 'stale',
				};
				$propertyTotals[$type][$class] = ($propertyTotals[$type][$class] ?? 0) + 1;
				$propertyRows[$type][] = [
					'`' . e7Short($fqcn) . '` ' . e7Loc($decl['file'], (int) $decl['docLine'] + $offset),
					'`@' . $m[1] . ' ' . $m[2] . ' $' . $name . '`',
					$decl['kind'] === 'trait' ? sprintf('trait; %d user(s), %d declare it', count($users), count($declaredBy)) : (count($declaredBy) === 1 ? 'declared' : 'not declared'),
					$class === 'documentation' ? '—' : ($thirdParty !== [] ? 'served by ' . implode(', ', array_map(static fn (string $t): string => '`' . $t . '`', array_unique($thirdParty))) . '; ' : '') . (implode('; ', array_slice($reads, 0, 3)) . (count($reads) > 3 ? sprintf(' (+%d)', count($reads) - 3) : '') ?: 'no read found'),
					'**' . $class . '**',
				];
			}
		}
	}

	if (isset($propertyRows[$type])) {
		echo "#### T6 — {$type}: `@property` lines (" . count($propertyRows[$type]) . ")\n\n";
		e7Table(['Declared on', 'Line', 'Declared?', 'Reads of an undeclared name (isset/unset shown as such)', 'Classification'], $propertyRows[$type]);
	}
}

echo "### T6 — totals\n\n";
e7Table(array_keys($totals), [array_values($totals)]);
e7Table(['Type', 'documentation', 'live magic', 'magic, unread', 'third-party magic', 'stale', 'stale (no user)'], array_map(
	static fn (string $type, array $c): array => [$type, $c['documentation'] ?? 0, $c['live magic'] ?? 0, $c['magic, unread'] ?? 0, $c['third-party magic'] ?? 0, $c['stale'] ?? 0, $c['stale (no user)'] ?? 0],
	array_keys($propertyTotals),
	array_values($propertyTotals),
));
