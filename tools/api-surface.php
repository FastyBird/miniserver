<?php declare(strict_types = 1);

/**
 * API-surface manifest: records the public PHP API of fastybird/miniserver-core -- every
 * `FastyBird\Core\*` type -- as canonical JSON, and proves that a change to it is exactly the
 * change a committed change list declares. Epic E5 (#460 §3.3), E5.2 (#634).
 *
 * E4 proved "nothing else changed" in the DI graph with tools/di-snapshot.php. E5 changes Core's
 * PHP API on purpose, so it needs the same proof one layer up: this file is it. The DI snapshot
 * still runs on every PR as well.
 *
 * USAGE (always in the application image, never on the host)
 *
 *   php tools/api-surface.php --write <file>
 *       Build the manifest of the current tree and write it to <file>.
 *
 *   php tools/api-surface.php --diff <base.json> [--head <head.json>] [--changes <list.php>]...
 *                             [--suggest]
 *       Build the manifest of the current tree (or read <head.json>), apply every change list
 *       to <base.json> in the order given, and compare the result with the head. Exit 0 when
 *       they are identical, 1 when they are not -- every difference the change lists do not
 *       explain is printed -- and 2 on a usage or input error. A change-list item that has
 *       nothing to act on (a removal of something absent, an addition of something present, a
 *       change to the value it already has) is an input error too: the list must equal the
 *       diff, not merely cover it. --suggest additionally prints, as a ready-to-edit change
 *       list, the items that would explain what is left; review turns its removed+added pairs
 *       into renames by hand.
 *
 *   docker run --rm -v "$PWD":/app -w /app -e XDEBUG_MODE=off -e TZ=UTC -e PHP_DATE_TIMEZONE=UTC \
 *       <application-image> php tools/api-surface.php --diff tools/api-maps/base.json
 *
 * The types are found through the real autoloader: the PSR-4 directories vendor/autoload.php
 * registers for `FastyBird\Core\`, minus `FastyBird\Core\Tests\`. With
 * COMPOSER_MIRROR_PATH_REPOS=1 that is the COPY under vendor/fastybird/miniserver-core, not
 * src/FastyBird/Core/Core -- the stale-mirror trap in CLAUDE.md. So the tool refuses to build a
 * manifest when that copy is a symlink or differs from src/FastyBird/Core/Core/src in any file:
 * run `COMPOSER_MIRROR_PATH_REPOS=1 composer reinstall fastybird/miniserver-core` first. Every
 * declared type (found by tokenizing each file, so a file's name is never trusted) is loaded
 * with autoloading on and reflected. A type that fails to load is recorded as
 * {"kind": "error", "error": ...} and compared like any other entry.
 *
 * THE BASE MANIFEST is tools/api-maps/base.json: Core's API at E5's merge base, `main` @
 * 5206bfc86 (E5.2 changes no production code, so its own head records the same). It is frozen:
 * no later PR rewrites it. Two checks run on every E5 PR from E5.3 on:
 *
 *   1. per PR -- the manifest of `main` as the PR's base, written with --write from a checkout
 *      of it, against the PR's head, explained by that PR's change list alone:
 *        --diff var/tools/api-surface/main.json --changes tools/api-maps/NN-<topic>.php
 *   2. cumulative -- the frozen base against the head, explained by every change list so far,
 *      in order (this is the "union manifest" of #460 §16 at close-out):
 *        --diff tools/api-maps/base.json --changes tools/api-maps/03-....php --changes ...
 *
 * Write scratch manifests under var/tools/api-surface/, which var/tools/.gitignore ignores.
 *
 * ANY OTHER PACKAGE: --package <Type>/<Name> (Epic E7, #462 §3 D9). Both modes take it:
 *
 *   php tools/api-surface.php --package Module/Devices --write <file>
 *   php tools/api-surface.php --package Module/Devices --diff <base.json> [--changes <list.php>]...
 *
 * The namespace root is the one PSR-4 prefix of src/FastyBird/<Type>/<Name>/composer.json's
 * `autoload` (`FastyBird\Module\Devices\`), minus its `Tests\` sub-namespace; the mirror whose
 * freshness is checked is that package's own, vendor/<composer name>, against
 * src/FastyBird/<Type>/<Name>/src (refresh it with `COMPOSER_MIRROR_PATH_REPOS=1 composer
 * reinstall <composer name>`). Only the types declared under that root are recorded; a type of
 * another package appears only by name, where a recorded type mentions it. Without --package the
 * tool is exactly the Core tool described above.
 *
 * How an E7 PR uses it (#462 §13 P3): there is no frozen E7 base. E7 is long and `main` moves, so
 * each PR writes, for every package it touches, the base manifest from a checkout of its MERGE
 * BASE (`git merge-base origin/main HEAD`) and diffs its head against it, explained by the change
 * list it commits under tools/api-maps/e7/<pr>-<Type>-<Name>.php (the issue number, then the
 * package):
 *
 *   (merge base) php tools/api-surface.php --package Module/Devices --write var/tools/api-surface/base-Module-Devices.json
 *   (head)       php tools/api-surface.php --package Module/Devices --diff var/tools/api-surface/base-Module-Devices.json \
 *                    --changes tools/api-maps/e7/700-Module-Devices.php
 *
 * A mechanical (a-)PR's list may hold only constant types; `#[\Override]` is never recorded (below),
 * so it needs no item. A package whose surface the PR does not change still runs the diff, with no
 * --changes, and must exit 0.
 *
 * WHAT IS RECORDED, per type, keyed by FQCN and sorted by it:
 *
 *   kind        class | interface | trait | enum | error
 *   final, abstract (classes only, else false), readonly
 *   parent      the direct parent class, or null
 *   interfaces  every interface it implements or extends, inherited ones included, sorted
 *   traits      the traits it uses directly, sorted
 *   attributes  `\Name(arguments)`, in declaration order
 *   backing     an enum's backing type (string, int) or null
 *   cases       an enum's cases in declaration order, name => backing value (or null)
 *   constants   public and protected constants DECLARED here, sorted by name: visibility,
 *               final, type, value
 *   properties  public and protected properties declared here, sorted: visibility, set (the
 *               set visibility -- asymmetric visibility; PHP reports a readonly property as
 *               protected(set)), static, readonly, type, hasDefault, default, promoted, virtual,
 *               final, abstract, hooks (get/set: final, abstract, and the get return or set
 *               parameter type), attributes
 *   methods     public and protected methods declared here (trait methods count as declared by
 *               the class that uses them), sorted: visibility, static, abstract, final, byRef,
 *               return type, parameters (by name, in declaration order: position, type,
 *               optional, default, defaultConstant, variadic, byRef, promoted, attributes),
 *               attributes, throws (the `@throws` types, resolved through the declaring file's
 *               imports, sorted)
 *
 * Private members are not API and are never recorded; neither are file paths or line numbers, nor
 * `#[\Override]`: it asserts something about the declaring class, not about what a caller sees, and
 * a collapse (below) has to drop it from every method only the collapsed interface declared.
 * A value -- a constant, a default, an attribute argument -- is recorded as PHP-like text:
 * `null`, `true`, `42`, `1.5`, `'text'`, `['a', 'k' => 1]`, `\Enum\Name::Case`, `object(\Class)`.
 * Types are PHP's own spelling of them (ReflectionType::__toString()).
 *
 * THE CHANGE LIST (tools/api-maps/NN-<topic>.php, NN being the E5.N subtask number: 03 for #635
 * ... 12 for #644) returns an array with up to four keys, applied to the base in this order:
 *
 *   'renamed' => [old => new, ...]
 *       A TYPE (`FastyBird\Core\Foo` => `FastyBird\Core\Bar\Baz`): a rename or a move. Its entry
 *       moves to the new name, and every mention of the old name anywhere in the manifest --
 *       a parent, an interface list, a parameter, return or property type, a `@throws`, an
 *       attribute, a value -- becomes the new name.
 *       A rename ONTO A TYPE THAT ALREADY EXISTS is a COLLAPSE (#460 §3.1, §3.2): the old type
 *       must be an interface; its entry is dropped, every mention of it becomes the target, it
 *       is removed from every `interfaces` list instead (the target does not implement itself),
 *       and its constants become the target's -- exactly what tools/move-core-symbols.php's
 *       'collapse' key does to the code. `Foo` becoming `final` is a separate 'changed' item.
 *       A MEMBER (`Foo::old()` => `Foo::new()`, `Foo::$a` => `Foo::$b`, `Foo::A` => `Bar::B`):
 *       its entry moves, across types too, and a constant's `Old::A` mentions in values follow.
 *   'removed' => [address, ...]
 *       A type or member that no longer exists. A removed type is also dropped from every
 *       `interfaces` list; any other mention of it must be explained by a 'changed' item.
 *   'added' => [address => entry, ...]
 *       A new type or member, with its full manifest entry exactly as --suggest prints it.
 *   'changed' => [address => [path => value, ...], ...]
 *       A field of an existing type or member: `final`, `interfaces`, `return`,
 *       `parameters.$timeout.default`, `hooks.get`, `set`, `throws`, ... A path names a key in
 *       the entry, nested keys joined with `.`; the value replaces it (a list, such as
 *       `interfaces` or `throws`, is replaced whole). A key that does not exist yet is created
 *       (a new parameter: `parameters.$x` => its entry); a path prefixed with `-` deletes the
 *       key (`'-parameters.$x' => null`).
 *
 *   An address is a type FQCN, or `FQCN::method()`, `FQCN::$property` or `FQCN::NAME` (a
 *   constant or an enum case). Members are addressed by their names as recorded, so a renamed
 *   type's members are addressed by the new type name in the items after 'renamed'.
 *
 *   Examples: collapsing an interface and finalising its implementation --
 *     'renamed' => ['FastyBird\Core\Example\IWidget' => 'FastyBird\Core\Example\Widget'],
 *     'changed' => ['FastyBird\Core\Example\Widget' => ['final' => true]],
 *   a changed default: 'changed' => ['FastyBird\Core\Example\Widget::bar()' => ['parameters.$ttl.default' => '0']].
 *
 * Exit codes follow tools/check-naming.php: 0 identical / done, 1 a difference, 2 the tool or its
 * input failed. tools/ is outside the coding standard's paths; this file follows it anyway.
 */

const FB_API_FORMAT = 1;

const FB_API_PREFIX = 'FastyBird\\Core\\';

const FB_API_EXCLUDED_PREFIX = 'FastyBird\\Core\\Tests\\';

const FB_API_SOURCE = 'src/FastyBird/Core/Core/src';

const FB_API_MIRROR = 'vendor/fastybird/miniserver-core';

const FB_API_PACKAGE = 'fastybird/miniserver-core';

const FB_API_CHANGE_KEYS = ['renamed', 'removed', 'added', 'changed'];

const FB_API_MEMBER_GROUPS = ['constants', 'cases', 'properties', 'methods'];

function fbApiFail(string $message): never
{
	fwrite(STDERR, 'api-surface: ' . $message . PHP_EOL);

	exit(2);
}

function fbApiRead(string $path): string
{
	$content = @file_get_contents($path);

	if ($content === false) {
		fbApiFail(sprintf('could not read "%s"', $path));
	}

	return $content;
}

/**
 * @return list<string> repository-relative paths below $directory, sorted
 */
function fbApiPhpFiles(string $directory): array
{
	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
	);

	foreach ($iterator as $file) {
		if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
			$files[] = substr($file->getPathname(), strlen($directory) + 1);
		}
	}

	sort($files, SORT_STRING);

	return $files;
}

/**
 * The package the manifest is built for: Core by default, or the one --package names.
 *
 * @param array{prefix: string, excluded: string, source: string, mirror: string, package: string}|null $set
 *
 * @return array{prefix: string, excluded: string, source: string, mirror: string, package: string}
 */
function fbApiTarget(array|null $set = null): array
{
	static $target = [
		'prefix' => FB_API_PREFIX,
		'excluded' => FB_API_EXCLUDED_PREFIX,
		'source' => FB_API_SOURCE,
		'mirror' => FB_API_MIRROR,
		'package' => FB_API_PACKAGE,
	];

	if ($set !== null) {
		$target = $set;
	}

	return $target;
}

/**
 * Reads src/FastyBird/<Type>/<Name>/composer.json: its name and its single PSR-4 root.
 *
 * @return array{prefix: string, excluded: string, source: string, mirror: string, package: string}
 */
function fbApiPackageTarget(string $root, string $package): array
{
	if (preg_match('~^[A-Z][A-Za-z0-9]*/[A-Z][A-Za-z0-9]*$~', $package) !== 1) {
		fbApiFail(sprintf('--package takes <Type>/<Name>, for example Module/Devices, not "%s"', $package));
	}

	$directory = 'src/FastyBird/' . $package;
	$manifest = json_decode(fbApiRead($root . '/' . $directory . '/composer.json'), true);

	if (!is_array($manifest) || !is_string($manifest['name'] ?? null)) {
		fbApiFail(sprintf('%s/composer.json has no package name', $directory));
	}

	$psr4 = $manifest['autoload']['psr-4'] ?? null;

	if (!is_array($psr4) || count($psr4) !== 1) {
		fbApiFail(sprintf('%s/composer.json must declare exactly one autoload PSR-4 root', $directory));
	}

	$prefix = (string) array_key_first($psr4);
	$path = trim((string) $psr4[$prefix], '/');

	return [
		'prefix' => $prefix,
		'excluded' => $prefix . 'Tests\\',
		'source' => $directory . '/' . $path,
		'mirror' => 'vendor/' . $manifest['name'],
		'package' => $manifest['name'],
	];
}

/**
 * Refuses a mirror that is a symlink (it can never go stale, so it hides the drift CI sees) or a
 * copy that differs from the source tree in any file.
 */
function fbApiCheckMirror(string $root): void
{
	$target = fbApiTarget();
	$mirror = $root . '/' . $target['mirror'];

	if (is_link($mirror)) {
		fbApiFail(sprintf(
			'%s is a symlink; reinstall it as a copy: COMPOSER_MIRROR_PATH_REPOS=1 composer reinstall %s',
			$target['mirror'],
			$target['package'],
		));
	}

	if (!is_dir($mirror)) {
		fbApiFail(sprintf('%s does not exist; run COMPOSER_MIRROR_PATH_REPOS=1 composer install', $target['mirror']));
	}

	$source = fbApiPhpFiles($root . '/' . $target['source']);
	$copy = fbApiPhpFiles($mirror . '/src');
	$stale = array_merge(array_diff($source, $copy), array_diff($copy, $source));

	foreach (array_intersect($source, $copy) as $file) {
		if (sha1_file($root . '/' . $target['source'] . '/' . $file) !== sha1_file($mirror . '/src/' . $file)) {
			$stale[] = $file;
		}
	}

	if ($stale !== []) {
		sort($stale, SORT_STRING);

		fbApiFail(sprintf(
			'%s/src differs from %s in %d file(s), e.g. %s; refresh it: COMPOSER_MIRROR_PATH_REPOS=1 composer reinstall %s',
			$target['mirror'],
			$target['source'],
			count($stale),
			$stale[0],
			$target['package'],
		));
	}
}

/**
 * The types a file declares and its class imports.
 *
 * @return array{namespace: string, types: list<string>, imports: array<string, string>}
 */
function fbApiAnalyseFile(string $code): array
{
	$tokens = PhpToken::tokenize($code);
	$namespace = '';
	$types = [];
	$imports = [];
	$depth = 0;
	$count = count($tokens);

	$significant = static function (int $index, int $step) use ($tokens, $count): int {
		for ($i = $index + $step; $i >= 0 && $i < $count; $i += $step) {
			if (!$tokens[$i]->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
				return $i;
			}
		}

		return -1;
	};

	for ($i = 0; $i < $count; $i++) {
		$token = $tokens[$i];

		if ($token->text === '{' || $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
			$depth++;

			continue;
		}

		if ($token->text === '}') {
			$depth--;

			continue;
		}

		if ($token->is(T_NAMESPACE)) {
			$next = $significant($i, 1);

			if ($next >= 0 && $tokens[$next]->is([T_STRING, T_NAME_QUALIFIED])) {
				$namespace = $tokens[$next]->text;
				$i = $next;
			}

			continue;
		}

		if ($token->is(T_USE) && $depth === 0) {
			$kind = 'class';
			$prefix = '';
			$current = null;
			$expectAlias = false;
			$items = [];

			for ($i++; $i < $count && $tokens[$i]->text !== ';'; $i++) {
				$part = $tokens[$i];

				if ($part->is(T_FUNCTION)) {
					$kind = 'function';
				} elseif ($part->is(T_CONST)) {
					$kind = 'const';
				} elseif ($part->text === '{') {
					$prefix = $current !== null ? $current[0] : '';
					$current = null;
				} elseif ($part->text === ',' || $part->text === '}') {
					if ($current !== null) {
						$items[] = $current;
					}

					$current = null;
				} elseif ($part->is(T_AS)) {
					$expectAlias = true;
				} elseif ($part->is([T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
					if ($expectAlias && $current !== null) {
						$current[1] = $part->text;
						$expectAlias = false;
					} else {
						$name = ($prefix !== '' ? $prefix . '\\' : '') . ltrim($part->text, '\\');
						$position = strrpos($name, '\\');
						$current = [$name, $position === false ? $name : substr($name, $position + 1)];
					}
				}
			}

			if ($current !== null) {
				$items[] = $current;
			}

			if ($kind === 'class') {
				foreach ($items as [$name, $alias]) {
					$imports[strtolower($alias)] = $name;
				}
			}

			continue;
		}

		if ($token->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
			$previous = $significant($i, -1);
			$next = $significant($i, 1);

			if (
				$next >= 0
				&& $tokens[$next]->is(T_STRING)
				&& ($previous < 0 || !$tokens[$previous]->is([T_DOUBLE_COLON, T_NEW]))
			) {
				$types[] = ($namespace !== '' ? $namespace . '\\' : '') . $tokens[$next]->text;
				$i = $next;
			}
		}
	}

	return ['namespace' => $namespace, 'types' => $types, 'imports' => $imports];
}

/**
 * @return array{namespace: string, types: list<string>, imports: array<string, string>}
 */
function fbApiFileInfo(string $path): array
{
	static $cache = [];

	return $cache[$path] ??= fbApiAnalyseFile(fbApiRead($path));
}

/**
 * Canonical text of a value: what PHP would accept as the same literal, minus objects.
 */
function fbApiExport(mixed $value): string
{
	if ($value === null) {
		return 'null';
	}

	if (is_bool($value)) {
		return $value ? 'true' : 'false';
	}

	if (is_int($value)) {
		return (string) $value;
	}

	if (is_float($value)) {
		return var_export($value, true);
	}

	if (is_string($value)) {
		return var_export($value, true);
	}

	if (is_array($value)) {
		$items = [];
		$isList = array_is_list($value);

		foreach ($value as $key => $item) {
			$items[] = ($isList ? '' : fbApiExport($key) . ' => ') . fbApiExport($item);
		}

		return '[' . implode(', ', $items) . ']';
	}

	if ($value instanceof UnitEnum) {
		return '\\' . $value::class . '::' . $value->name;
	}

	if (is_object($value)) {
		return 'object(\\' . $value::class . ')';
	}

	return get_debug_type($value);
}

function fbApiType(ReflectionType|null $type): string|null
{
	return $type === null ? null : (string) $type;
}

/**
 * @param list<ReflectionAttribute<object>> $attributes
 *
 * @return list<string>
 */
function fbApiAttributes(array $attributes): array
{
	$result = [];

	foreach ($attributes as $attribute) {
		if ($attribute->getName() === Override::class) {
			continue; // a compile-time assertion about the declaring class, not API; see the header
		}

		try {
			$arguments = [];

			foreach ($attribute->getArguments() as $key => $argument) {
				$arguments[] = (is_string($key) ? $key . ': ' : '') . fbApiExport($argument);
			}

			$result[] = '\\' . $attribute->getName() . '(' . implode(', ', $arguments) . ')';
		} catch (Throwable $ex) {
			$result[] = '\\' . $attribute->getName() . '(<' . $ex::class . '>)';
		}
	}

	return $result;
}

/**
 * The `@throws` types of a doc comment, resolved as PHP would resolve a class name in the file
 * that declares it.
 *
 * @return list<string>
 */
function fbApiThrows(string|false $doc, string|false $file): array
{
	if ($doc === false || $file === false || preg_match_all('/@throws\s+([^\s*]+)/', $doc, $matches) === 0) {
		return [];
	}

	$info = fbApiFileInfo($file);
	$throws = [];

	foreach ($matches[1] as $expression) {
		foreach (explode('|', $expression) as $name) {
			$name = trim($name, " \t()");

			if ($name === '') {
				continue;
			}

			if (str_starts_with($name, '\\')) {
				$throws[] = substr($name, 1);

				continue;
			}

			$position = strpos($name, '\\');
			$first = strtolower($position === false ? $name : substr($name, 0, $position));
			$rest = $position === false ? '' : substr($name, $position);

			$throws[] = isset($info['imports'][$first])
				? $info['imports'][$first] . $rest
				: ($info['namespace'] !== '' ? $info['namespace'] . '\\' : '') . $name;
		}
	}

	$throws = array_values(array_unique($throws));
	sort($throws, SORT_STRING);

	return $throws;
}

function fbApiVisibility(ReflectionMethod|ReflectionProperty|ReflectionClassConstant $member): string
{
	return $member->isPublic() ? 'public' : ($member->isProtected() ? 'protected' : 'private');
}

/**
 * @return array<string, mixed>
 */
function fbApiMethod(ReflectionMethod $method): array
{
	$parameters = [];

	foreach ($method->getParameters() as $parameter) {
		$default = null;
		$defaultConstant = null;

		if ($parameter->isDefaultValueAvailable()) {
			try {
				$default = fbApiExport($parameter->getDefaultValue());

				if ($parameter->isDefaultValueConstant()) {
					$defaultConstant = $parameter->getDefaultValueConstantName();
				}
			} catch (Throwable $ex) {
				$default = '<' . $ex::class . '>';
			}
		}

		$parameters['$' . $parameter->getName()] = [
			'position' => $parameter->getPosition(),
			'type' => fbApiType($parameter->getType()),
			'optional' => $parameter->isOptional(),
			'default' => $default,
			'defaultConstant' => $defaultConstant,
			'variadic' => $parameter->isVariadic(),
			'byRef' => $parameter->isPassedByReference(),
			'promoted' => $parameter->isPromoted(),
			'attributes' => fbApiAttributes($parameter->getAttributes()),
		];
	}

	return [
		'visibility' => fbApiVisibility($method),
		'static' => $method->isStatic(),
		'abstract' => $method->isAbstract(),
		'final' => $method->isFinal(),
		'byRef' => $method->returnsReference(),
		'return' => fbApiType($method->getReturnType()),
		'parameters' => $parameters,
		'attributes' => fbApiAttributes($method->getAttributes()),
		'throws' => fbApiThrows($method->getDocComment(), $method->getFileName()),
	];
}

/**
 * @return array<string, mixed>
 */
function fbApiProperty(ReflectionProperty $property): array
{
	$hooks = [];

	foreach ($property->getHooks() as $kind => $hook) {
		$entry = ['final' => $hook->isFinal(), 'abstract' => $hook->isAbstract()];

		if ($kind === 'get') {
			$entry['return'] = fbApiType($hook->getReturnType());
		} else {
			$entry['parameter'] = fbApiType($hook->getParameters()[0]?->getType());
		}

		$hooks[$kind] = $entry;
	}

	ksort($hooks, SORT_STRING);

	$set = $property->isPrivateSet()
		? 'private'
		: ($property->isProtectedSet() ? 'protected' : fbApiVisibility($property));

	return [
		'visibility' => fbApiVisibility($property),
		'set' => $set,
		'static' => $property->isStatic(),
		'readonly' => $property->isReadOnly(),
		'type' => fbApiType($property->getType()),
		'hasDefault' => $property->hasDefaultValue(),
		'default' => $property->hasDefaultValue() ? fbApiExport($property->getDefaultValue()) : null,
		'promoted' => $property->isPromoted(),
		'virtual' => $property->isVirtual(),
		'final' => $property->isFinal(),
		'abstract' => $property->isAbstract(),
		'hooks' => $hooks,
		'attributes' => fbApiAttributes($property->getAttributes()),
	];
}

/**
 * @return array<string, mixed>
 */
function fbApiType_(string $fqcn): array
{
	$class = new ReflectionClass($fqcn);
	$kind = $class->isEnum() ? 'enum' : ($class->isInterface() ? 'interface' : ($class->isTrait() ? 'trait' : 'class'));

	$interfaces = $class->getInterfaceNames();
	sort($interfaces, SORT_STRING);
	$traits = $class->getTraitNames();
	sort($traits, SORT_STRING);

	$backing = null;
	$cases = [];

	if ($class->isEnum()) {
		$enum = new ReflectionEnum($fqcn);
		$backing = fbApiType($enum->getBackingType());

		foreach ($enum->getCases() as $case) {
			$cases[$case->getName()] = $case instanceof ReflectionEnumBackedCase
				? fbApiExport($case->getBackingValue())
				: null;
		}
	}

	$constants = [];

	foreach ($class->getReflectionConstants() as $constant) {
		if (
			$constant->getDeclaringClass()->getName() !== $class->getName()
			|| $constant->isPrivate()
			|| $constant->isEnumCase()
		) {
			continue;
		}

		$constants[$constant->getName()] = [
			'visibility' => fbApiVisibility($constant),
			'final' => $constant->isFinal(),
			'type' => fbApiType($constant->getType()),
			'value' => fbApiExport($constant->getValue()),
		];
	}

	$properties = [];

	foreach ($class->getProperties() as $property) {
		if ($property->getDeclaringClass()->getName() !== $class->getName() || $property->isPrivate()) {
			continue;
		}

		$properties[$property->getName()] = fbApiProperty($property);
	}

	$methods = [];

	foreach ($class->getMethods() as $method) {
		if ($method->getDeclaringClass()->getName() !== $class->getName() || $method->isPrivate()) {
			continue;
		}

		$methods[$method->getName()] = fbApiMethod($method);
	}

	ksort($constants, SORT_STRING);
	ksort($properties, SORT_STRING);
	ksort($methods, SORT_STRING);

	return [
		'kind' => $kind,
		'final' => $class->isFinal(),
		'abstract' => $kind === 'class' && $class->isAbstract(),
		'readonly' => $class->isReadOnly(),
		'parent' => $class->getParentClass() !== false ? $class->getParentClass()->getName() : null,
		'interfaces' => $interfaces,
		'traits' => $traits,
		'attributes' => fbApiAttributes($class->getAttributes()),
		'backing' => $backing,
		'cases' => $cases,
		'constants' => $constants,
		'properties' => $properties,
		'methods' => $methods,
	];
}

/**
 * @return array{format: int, types: array<string, array<string, mixed>>}
 */
function fbApiBuild(string $root): array
{
	$autoload = $root . '/vendor/autoload.php';

	if (!is_file($autoload)) {
		fbApiFail('vendor/autoload.php does not exist; run composer install first');
	}

	fbApiCheckMirror($root);

	$loader = require $autoload;

	if (!$loader instanceof Composer\Autoload\ClassLoader) {
		fbApiFail('vendor/autoload.php did not return Composer\'s ClassLoader');
	}

	$target = fbApiTarget();
	$directories = $loader->getPrefixesPsr4()[$target['prefix']] ?? [];

	if ($directories === []) {
		fbApiFail(sprintf('the autoloader registers no PSR-4 directory for %s', $target['prefix']));
	}

	$fqcns = [];

	foreach ($directories as $directory) {
		$directory = realpath($directory);

		if ($directory === false) {
			fbApiFail('a PSR-4 directory of ' . $target['prefix'] . ' does not exist');
		}

		foreach (fbApiPhpFiles($directory) as $file) {
			foreach (fbApiFileInfo($directory . '/' . $file)['types'] as $fqcn) {
				if (str_starts_with($fqcn, $target['prefix']) && !str_starts_with($fqcn, $target['excluded'])) {
					$fqcns[$fqcn] = true;
				}
			}
		}
	}

	$fqcns = array_keys($fqcns);
	sort($fqcns, SORT_STRING);

	$types = [];

	foreach ($fqcns as $fqcn) {
		try {
			if (
				!class_exists($fqcn)
				&& !interface_exists($fqcn)
				&& !trait_exists($fqcn)
				&& !enum_exists($fqcn)
			) {
				$types[$fqcn] = ['kind' => 'error', 'error' => 'not found by the autoloader'];

				continue;
			}

			$types[$fqcn] = fbApiType_($fqcn);
		} catch (Throwable $ex) {
			$types[$fqcn] = [
				'kind' => 'error',
				'error' => $ex::class . ': ' . str_replace($root, '%root%', $ex->getMessage()),
			];
		}
	}

	return fbApiNormalize(['format' => FB_API_FORMAT, 'types' => $types]);
}

/**
 * JSON round trip, so a built manifest and a read one compare as the same PHP values.
 *
 * @param array<mixed> $manifest
 *
 * @return array{format: int, types: array<string, array<string, mixed>>}
 */
function fbApiNormalize(array $manifest): array
{
	$decoded = json_decode(fbApiEncode($manifest), true, 512, JSON_THROW_ON_ERROR);

	if (!is_array($decoded) || ($decoded['format'] ?? null) !== FB_API_FORMAT || !is_array($decoded['types'] ?? null)) {
		fbApiFail(sprintf('not an API manifest of format %d', FB_API_FORMAT));
	}

	return $decoded;
}

/**
 * @param array<mixed> $manifest
 */
function fbApiEncode(array $manifest): string
{
	return json_encode(
		$manifest,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
	) . "\n";
}

/**
 * @return array{format: int, types: array<string, array<string, mixed>>}
 */
function fbApiLoad(string $path): array
{
	try {
		$decoded = json_decode(fbApiRead($path), true, 512, JSON_THROW_ON_ERROR);
	} catch (JsonException $ex) {
		fbApiFail(sprintf('"%s" is not JSON: %s', $path, $ex->getMessage()));
	}

	if (!is_array($decoded)) {
		fbApiFail(sprintf('"%s" is not an API manifest', $path));
	}

	return fbApiNormalize($decoded);
}

// ---------------------------------------------------------------------------------------------
// Addresses

/**
 * [type, group, member]: group and member are null for a type. A `::NAME` address is looked up
 * among the constants first, then the enum cases.
 *
 * @param array{format: int, types: array<string, array<string, mixed>>} $manifest
 *
 * @return array{0: string, 1: string|null, 2: string|null}
 */
function fbApiParseAddress(string $address, array $manifest, string $context): array
{
	if (!str_contains($address, '::')) {
		if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+$/', $address) !== 1) {
			fbApiFail(sprintf('%s: "%s" is not a type FQCN', $context, $address));
		}

		return [$address, null, null];
	}

	[$type, $member] = explode('::', $address, 2);

	if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*\(\)$/', $member) === 1) {
		return [$type, 'methods', substr($member, 0, -2)];
	}

	if (preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', $member) === 1) {
		return [$type, 'properties', substr($member, 1)];
	}

	if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $member) === 1) {
		$group = isset($manifest['types'][$type]['cases'][$member]) ? 'cases' : 'constants';

		return [$type, $group, $member];
	}

	fbApiFail(sprintf('%s: "%s" is not an address (Type, Type::method(), Type::$property, Type::NAME)', $context, $address));
}

/**
 * @param array{format: int, types: array<string, array<string, mixed>>} $manifest
 */
function fbApiHas(array $manifest, string $type, string|null $group, string|null $member): bool
{
	if ($group === null) {
		return isset($manifest['types'][$type]);
	}

	return isset($manifest['types'][$type][$group]) && array_key_exists($member, $manifest['types'][$type][$group]);
}

/**
 * Every string in $value, keys included, with the type name $old replaced by $new where it
 * stands as a whole name (optionally after a leading `\`).
 */
function fbApiSubstitute(mixed $value, string $pattern, string $replacement): mixed
{
	if (is_string($value)) {
		return preg_replace($pattern, $replacement, $value) ?? fbApiFail('a substitution pattern failed');
	}

	if (!is_array($value)) {
		return $value;
	}

	$result = [];

	foreach ($value as $key => $item) {
		$newKey = is_string($key) ? fbApiSubstitute($key, $pattern, $replacement) : $key;
		$result[$newKey] = fbApiSubstitute($item, $pattern, $replacement);
	}

	return $result;
}

function fbApiNamePattern(string $name): string
{
	return '/(?<![A-Za-z0-9_\\\\])(\\\\?)' . preg_quote($name, '/') . '(?![A-Za-z0-9_])(?!\\\\[A-Za-z_])/';
}

/**
 * @param array{format: int, types: array<string, array<string, mixed>>} $manifest
 * @param list<string> $drop
 */
function fbApiDropInterfaces(array &$manifest, array $drop): void
{
	foreach ($manifest['types'] as $fqcn => $entry) {
		if (!isset($entry['interfaces']) || !is_array($entry['interfaces'])) {
			continue;
		}

		$manifest['types'][$fqcn]['interfaces'] = array_values(array_diff($entry['interfaces'], $drop));
	}
}

// ---------------------------------------------------------------------------------------------
// Change lists

/**
 * @return array{renamed: array<string, string>, removed: list<string>, added: array<string, array<mixed>>, changed: array<string, array<string, mixed>>}
 */
function fbApiLoadChanges(string $path): array
{
	if (!is_file($path)) {
		fbApiFail(sprintf('change list "%s" does not exist', $path));
	}

	$list = require $path;

	if (!is_array($list) || array_diff(array_keys($list), FB_API_CHANGE_KEYS) !== []) {
		fbApiFail(sprintf('change list "%s" must return an array with only the keys: %s', $path, implode(', ', FB_API_CHANGE_KEYS)));
	}

	$renamed = $list['renamed'] ?? [];
	$removed = $list['removed'] ?? [];
	$added = $list['added'] ?? [];
	$changed = $list['changed'] ?? [];

	foreach ($renamed as $old => $new) {
		if (!is_string($old) || !is_string($new) || $old === $new) {
			fbApiFail(sprintf('%s: "renamed" must map an address to a different address', $path));
		}
	}

	if (!is_array($removed) || !array_is_list($removed) || array_filter($removed, static fn (mixed $item): bool => !is_string($item)) !== []) {
		fbApiFail(sprintf('%s: "removed" must be a list of addresses', $path));
	}

	foreach ($added as $address => $entry) {
		if (!is_string($address) || !is_array($entry)) {
			fbApiFail(sprintf('%s: "added" must map an address to its manifest entry', $path));
		}
	}

	foreach ($changed as $address => $fields) {
		if (!is_string($address) || !is_array($fields) || $fields === []) {
			fbApiFail(sprintf('%s: "changed" must map an address to a non-empty [path => value] array', $path));
		}

		foreach (array_keys($fields) as $field) {
			if (!is_string($field) || ltrim($field, '-') === '') {
				fbApiFail(sprintf('%s: "changed" %s has an empty path', $path, $address));
			}
		}
	}

	return ['renamed' => $renamed, 'removed' => $removed, 'added' => $added, 'changed' => $changed];
}

/**
 * Applies one change list to $manifest. Every item must act on something; one that does not is
 * an input error, so the list cannot carry an entry the diff does not need.
 *
 * @param array{format: int, types: array<string, array<string, mixed>>} $manifest
 *
 * @return array{format: int, types: array<string, array<string, mixed>>}
 */
function fbApiApply(array $manifest, string $path): array
{
	$changes = fbApiLoadChanges($path);

	foreach ($changes['renamed'] as $old => $new) {
		$context = sprintf('%s: renamed "%s"', $path, $old);
		[$oldType, $oldGroup, $oldMember] = fbApiParseAddress($old, $manifest, $context);

		if (!fbApiHas($manifest, $oldType, $oldGroup, $oldMember)) {
			fbApiFail($context . ' does not exist in the manifest it is applied to');
		}

		if ($oldGroup === null) {
			[$newType, $newGroup] = fbApiParseAddress($new, $manifest, $context);

			if ($newGroup !== null) {
				fbApiFail($context . ': a type can only be renamed to a type');
			}

			$collapse = isset($manifest['types'][$newType]);

			if ($collapse && ($manifest['types'][$oldType]['kind'] ?? null) !== 'interface') {
				fbApiFail(sprintf('%s: "%s" already exists, and only an interface can be collapsed into a type', $context, $newType));
			}

			$entry = $manifest['types'][$oldType];
			unset($manifest['types'][$oldType]);

			if ($collapse) {
				fbApiDropInterfaces($manifest, [$oldType]);

				// the move tool copies the interface's constants into the implementation
				foreach ($entry['constants'] ?? [] as $name => $constant) {
					if (isset($manifest['types'][$newType]['constants'][$name])) {
						fbApiFail(sprintf('%s: "%s" already declares the constant %s', $context, $newType, $name));
					}

					$manifest['types'][$newType]['constants'][$name] = $constant;
				}

				ksort($manifest['types'][$newType]['constants'], SORT_STRING);
			} else {
				$manifest['types'][$newType] = $entry;
			}

			$manifest['types'] = fbApiSubstitute($manifest['types'], fbApiNamePattern($oldType), '${1}' . $newType);
			ksort($manifest['types'], SORT_STRING);

			continue;
		}

		[$newType, $newGroup, $newMember] = fbApiParseAddress($new, $manifest, $context);

		if ($newGroup === null || (($oldGroup === 'methods') !== ($newGroup === 'methods')) || (($oldGroup === 'properties') !== ($newGroup === 'properties'))) {
			fbApiFail($context . ': a member can only be renamed to a member of the same kind');
		}

		if (!isset($manifest['types'][$newType])) {
			fbApiFail(sprintf('%s: the target type "%s" does not exist', $context, $newType));
		}

		if (fbApiHas($manifest, $newType, $newGroup, $newMember)) {
			fbApiFail(sprintf('%s: "%s" already exists', $context, $new));
		}

		$entry = $manifest['types'][$oldType][$oldGroup][$oldMember];
		unset($manifest['types'][$oldType][$oldGroup][$oldMember]);
		$manifest['types'][$newType][$newGroup][$newMember] = $entry;

		if ($newGroup !== 'cases') {
			ksort($manifest['types'][$newType][$newGroup], SORT_STRING);
		}

		if ($oldGroup === 'constants' || $oldGroup === 'cases') {
			$manifest['types'] = fbApiSubstitute(
				$manifest['types'],
				'/(?<![A-Za-z0-9_\\\\])(\\\\?)' . preg_quote($oldType . '::' . $oldMember, '/') . '(?![A-Za-z0-9_])/',
				'${1}' . $newType . '::' . $newMember,
			);
		}
	}

	foreach ($changes['removed'] as $address) {
		$context = sprintf('%s: removed "%s"', $path, $address);
		[$type, $group, $member] = fbApiParseAddress($address, $manifest, $context);

		if (!fbApiHas($manifest, $type, $group, $member)) {
			fbApiFail($context . ' does not exist in the manifest it is applied to');
		}

		if ($group === null) {
			unset($manifest['types'][$type]);
			fbApiDropInterfaces($manifest, [$type]);
		} else {
			unset($manifest['types'][$type][$group][$member]);
		}
	}

	foreach ($changes['added'] as $address => $entry) {
		$context = sprintf('%s: added "%s"', $path, $address);
		[$type, $group, $member] = fbApiParseAddress($address, $manifest, $context);

		if (fbApiHas($manifest, $type, $group, $member)) {
			fbApiFail($context . ' already exists in the manifest it is applied to');
		}

		if ($group === null) {
			$manifest['types'][$type] = $entry;
			ksort($manifest['types'], SORT_STRING);
		} else {
			if (!isset($manifest['types'][$type])) {
				fbApiFail(sprintf('%s: its type does not exist', $context));
			}

			$manifest['types'][$type][$group][$member] = $entry;

			if ($group !== 'cases') {
				ksort($manifest['types'][$type][$group], SORT_STRING);
			}
		}
	}

	foreach ($changes['changed'] as $address => $fields) {
		$context = sprintf('%s: changed "%s"', $path, $address);
		[$type, $group, $member] = fbApiParseAddress($address, $manifest, $context);

		if (!fbApiHas($manifest, $type, $group, $member)) {
			fbApiFail($context . ' does not exist in the manifest it is applied to');
		}

		$entry = $group === null ? $manifest['types'][$type] : $manifest['types'][$type][$group][$member];

		foreach ($fields as $field => $value) {
			$delete = str_starts_with($field, '-');
			$keys = explode('.', ltrim($field, '-'));
			$cursor = &$entry;

			foreach (array_slice($keys, 0, -1) as $key) {
				if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
					fbApiFail(sprintf('%s: path "%s" does not exist', $context, $field));
				}

				$cursor = &$cursor[$key];
			}

			$last = $keys[count($keys) - 1];

			if (!is_array($cursor)) {
				fbApiFail(sprintf('%s: path "%s" does not exist', $context, $field));
			}

			if ($delete) {
				if (!array_key_exists($last, $cursor)) {
					fbApiFail(sprintf('%s: path "%s" does not exist', $context, $field));
				}

				unset($cursor[$last]);
			} else {
				if (array_key_exists($last, $cursor) && $cursor[$last] === $value) {
					fbApiFail(sprintf('%s: path "%s" already has that value', $context, $field));
				}

				$cursor[$last] = $value;
			}

			unset($cursor);
		}

		if ($group === null) {
			$manifest['types'][$type] = $entry;
		} else {
			$manifest['types'][$type][$group][$member] = $entry;
		}

		unset($entry);
	}

	return fbApiNormalize($manifest);
}

// ---------------------------------------------------------------------------------------------
// Diff

/**
 * The manifest as addressable entities: a type's own fields (its members taken out), and each
 * member.
 *
 * @param array{format: int, types: array<string, array<string, mixed>>} $manifest
 *
 * @return array<string, mixed>
 */
function fbApiEntities(array $manifest): array
{
	$entities = [];

	foreach ($manifest['types'] as $fqcn => $entry) {
		$own = $entry;

		foreach (FB_API_MEMBER_GROUPS as $group) {
			unset($own[$group]);

			foreach ($entry[$group] ?? [] as $name => $member) {
				$address = match ($group) {
					'methods' => $fqcn . '::' . $name . '()',
					'properties' => $fqcn . '::$' . $name,
					default => $fqcn . '::' . $name,
				};

				$entities[$address] = $group === 'cases' ? ['case' => $member] : $member;
			}
		}

		$entities[$fqcn] = $own;
	}

	return $entities;
}

/**
 * Leaf paths that differ between two entries. A list is a leaf; so is a value of a different
 * shape on each side.
 *
 * @return list<array{0: string, 1: mixed, 2: mixed, 3: bool, 4: bool}> path, base, head, inBase, inHead
 */
function fbApiFieldDiff(mixed $base, mixed $head, string $prefix = ''): array
{
	if (
		!is_array($base)
		|| !is_array($head)
		|| ($base !== [] && array_is_list($base))
		|| ($head !== [] && array_is_list($head))
	) {
		return $base === $head ? [] : [[$prefix, $base, $head, true, true]];
	}

	$differences = [];

	foreach ($base as $key => $value) {
		$path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

		if (!array_key_exists($key, $head)) {
			$differences[] = [$path, $value, null, true, false];
		} else {
			array_push($differences, ...fbApiFieldDiff($value, $head[$key], $path));
		}
	}

	foreach ($head as $key => $value) {
		if (!array_key_exists($key, $base)) {
			$differences[] = [$prefix === '' ? (string) $key : $prefix . '.' . $key, null, $value, false, true];
		}
	}

	// key order is part of the entry (parameter positions, enum case order)
	if ($differences === [] && array_keys($base) !== array_keys($head)) {
		$differences[] = [$prefix, $base, $head, true, true];
	}

	return $differences;
}

function fbApiShow(mixed $value): string
{
	return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

/**
 * PHP source for a value, short arrays, one tab per level.
 */
function fbApiPhp(mixed $value, int $indent = 0): string
{
	if (!is_array($value)) {
		return $value === null ? 'null' : var_export($value, true);
	}

	if ($value === []) {
		return '[]';
	}

	$pad = str_repeat("\t", $indent + 1);
	$lines = [];
	$isList = array_is_list($value);

	foreach ($value as $key => $item) {
		$lines[] = $pad . ($isList ? '' : var_export($key, true) . ' => ') . fbApiPhp($item, $indent + 1) . ',';
	}

	return "[\n" . implode("\n", $lines) . "\n" . str_repeat("\t", $indent) . ']';
}

/**
 * @param array{format: int, types: array<string, array<string, mixed>>} $expected
 * @param array{format: int, types: array<string, array<string, mixed>>} $head
 */
function fbApiDiff(array $expected, array $head, bool $suggest): int
{
	$base = fbApiEntities($expected);
	$now = fbApiEntities($head);
	$lines = [];
	$suggestion = ['removed' => [], 'added' => [], 'changed' => []];

	foreach ($base as $address => $entry) {
		if (!array_key_exists($address, $now)) {
			// a member of a removed type is implied by the type's removal
			$owner = explode('::', $address, 2)[0];

			if ($owner === $address || array_key_exists($owner, $now)) {
				$lines[] = sprintf('- %s', $address);
				$suggestion['removed'][] = $address;
			}

			continue;
		}

		foreach (fbApiFieldDiff($entry, $now[$address]) as [$path, $old, $new, $inBase, $inHead]) {
			$path = isset($entry['case']) && $path === 'case' ? '' : $path;
			$lines[] = sprintf(
				'~ %s%s: %s -> %s',
				$address,
				$path !== '' ? ' [' . $path . ']' : '',
				$inBase ? fbApiShow($old) : '(absent)',
				$inHead ? fbApiShow($new) : '(absent)',
			);

			if (str_contains($address, '::') && preg_match('/::[A-Za-z_][A-Za-z0-9_]*$/', $address) === 1 && isset($entry['case'])) {
				// an enum case's value is its whole entry
				$suggestion['removed'][] = $address;
				$suggestion['added'][$address] = $new;
			} elseif ($path === '') {
				$suggestion['changed'][$address] = $new;
			} else {
				$suggestion['changed'][$address][$inHead ? $path : '-' . $path] = $inHead ? $new : null;
			}
		}
	}

	foreach ($now as $address => $entry) {
		if (array_key_exists($address, $base)) {
			continue;
		}

		$owner = explode('::', $address, 2)[0];

		if ($owner !== $address && !array_key_exists($owner, $base)) {
			continue; // part of the added type's own entry
		}

		$lines[] = sprintf('+ %s', $address);
		$suggestion['added'][$address] = $owner === $address ? $head['types'][$address] : (isset($entry['case']) ? $entry['case'] : $entry);
	}

	if ($lines === []) {
		printf("API manifest: identical (%d types, %d entities).\n", count($head['types']), count($now));

		return 0;
	}

	printf("API manifest: %d difference(s) the change lists do not explain:\n%s\n", count($lines), implode("\n", $lines));

	if ($suggest) {
		$suggestion = array_filter($suggestion, static fn (array $items): bool => $items !== []);
		echo "\nA change list that explains them (turn removed+added pairs into renames by hand):\n\n";
		echo "<?php declare(strict_types = 1);\n\nreturn " . fbApiPhp($suggestion) . ";\n";
	}

	return 1;
}

// ---------------------------------------------------------------------------------------------

// Loading every Core type autoloads vendor code that trips PHP 8.4's implicitly-nullable
// deprecation (docs/deprecations.md); it is not this tool's finding and must not bury its output.
error_reporting(E_ALL & ~E_DEPRECATED);

$root = dirname(__DIR__);
$arguments = array_slice($argv, 1);
$mode = null;
$target = null;
$headPath = null;
$changePaths = [];
$suggest = false;
$package = null;

for ($i = 0; $i < count($arguments); $i++) {
	$argument = $arguments[$i];

	switch ($argument) {
		case '--write':
		case '--diff':
			if ($mode !== null || !isset($arguments[$i + 1])) {
				fbApiFail('usage: [--package <Type>/<Name>] --write <file> | --diff <base.json> [--head <head.json>] [--changes <list.php>]... [--suggest]');
			}

			$mode = substr($argument, 2);
			$target = $arguments[++$i];

			break;
		case '--head':
			$headPath = $arguments[++$i] ?? fbApiFail('--head needs a file');

			break;
		case '--changes':
			$changePaths[] = $arguments[++$i] ?? fbApiFail('--changes needs a file');

			break;
		case '--suggest':
			$suggest = true;

			break;
		case '--package':
			if ($package !== null) {
				fbApiFail('--package may be given once');
			}

			$package = $arguments[++$i] ?? fbApiFail('--package needs <Type>/<Name>');

			break;
		default:
			fbApiFail(sprintf('unknown argument "%s"', $argument));
	}
}

if ($mode === null || $target === null) {
	fbApiFail('usage: [--package <Type>/<Name>] --write <file> | --diff <base.json> [--head <head.json>] [--changes <list.php>]... [--suggest]');
}

if ($package !== null) {
	fbApiTarget(fbApiPackageTarget($root, $package));
}

if ($mode === 'write') {
	if ($headPath !== null || $changePaths !== [] || $suggest) {
		fbApiFail('--write takes no other option');
	}

	$manifest = fbApiBuild($root);
	$directory = dirname($target);

	if (!is_dir($directory) && !mkdir($directory, 0o777, true)) {
		fbApiFail(sprintf('could not create %s', $directory));
	}

	if (file_put_contents($target, fbApiEncode($manifest)) === false) {
		fbApiFail(sprintf('could not write "%s"', $target));
	}

	printf("API manifest: %d types written to %s.\n", count($manifest['types']), $target);

	exit(0);
}

$expected = fbApiLoad($target);

foreach ($changePaths as $changePath) {
	$expected = fbApiApply($expected, $changePath);
}

$head = $headPath !== null ? fbApiLoad($headPath) : fbApiBuild($root);

exit(fbApiDiff($expected, $head, $suggest));
