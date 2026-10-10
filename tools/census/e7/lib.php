<?php declare(strict_types = 1);

/**
 * E7.0 census (#694): what every census script shares -- the package list, the mirror check and
 * the parse index.
 *
 * THE PACKAGES are found, not listed: every src/FastyBird/<Type>/<Name>/composer.json outside
 * Core/Core, with its single PSR-4 root and its composer name. There are 28.
 *
 * THE MIRROR CHECK. Reflection goes through the real autoloader, which (with
 * COMPOSER_MIRROR_PATH_REPOS=1) loads every package from its COPY under vendor/<composer name>,
 * not from src/ -- the stale-mirror trap in CLAUDE.md. So, like tools/api-surface.php, the
 * scripts refuse to run when any of the 29 mirrors (Core's included: package classes extend and
 * implement Core types) is a symlink, or differs from its src/FastyBird/<Type>/<Name>/src in any
 * file.
 *
 * THE INDEX is every first-party PHP file -- src/, tests/, bin/, public/ and migrations/ -- parsed
 * with the vendored nikic/php-parser and its NameResolver, so every name is an FQCN, through
 * imports and aliases. It records declarations (with extends, implements, traits, attributes,
 * properties, methods and constants) and the uses the tables need: extends, catch, ::class
 * arguments of DI lookups, mock creation, $this->x() calls, property fetches, isset/unset. It is
 * cached in $E7_DIR/index-<fingerprint>.json; the fingerprint is the SHA-1 of every indexed
 * file's path and content and of this file, so a changed tree or collector is re-parsed.
 */

require __DIR__ . '/../../../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

error_reporting(E_ALL & ~E_DEPRECATED);

const E7_ROOT = __DIR__ . '/../../..';

const E7_TYPES = ['Plugin', 'Automator', 'Addon', 'Module', 'Bridge', 'Connector'];

const E7_INDEXED = ['src', 'tests', 'bin', 'public', 'migrations'];

const E7_FORBIDDEN_TAGS = ['package', 'subpackage', 'author', 'copyright', 'license', 'since', 'created', 'version', 'date'];

const E7_ORM_MAPPED = ['Doctrine\\ORM\\Mapping\\Entity', 'Doctrine\\ORM\\Mapping\\MappedSuperclass', 'Doctrine\\ORM\\Mapping\\Embeddable'];

function e7Fail(string $message): never
{
	fwrite(STDERR, 'census: ' . $message . PHP_EOL);

	exit(2);
}

function e7Root(): string
{
	return (string) realpath(E7_ROOT);
}

function e7Scratch(): string
{
	$dir = getenv('E7_DIR') ?: '/tmp/e7-census';

	if (!is_dir($dir) && !mkdir($dir, 0o777, true)) {
		e7Fail('could not create ' . $dir);
	}

	return $dir;
}

/**
 * @return array<string, array{type: string, name: string, dir: string, prefix: string, composer: string}>
 *         keyed by "<Type>/<Name>", Core/Core included, in type order then name
 */
function e7AllPackages(): array
{
	static $packages = null;

	if ($packages !== null) {
		return $packages;
	}

	$packages = [];

	foreach (glob(e7Root() . '/src/FastyBird/*/*/composer.json') ?: [] as $file) {
		$json = json_decode((string) file_get_contents($file), true);
		$psr4 = $json['autoload']['psr-4'] ?? [];

		if (!is_array($psr4) || count($psr4) !== 1) {
			e7Fail($file . ' must declare exactly one PSR-4 root');
		}

		$dir = substr(dirname($file), strlen(e7Root()) + 1);
		[, , $type, $name] = explode('/', $dir);
		$packages[$type . '/' . $name] = [
			'type' => $type,
			'name' => $name,
			'dir' => $dir,
			'prefix' => (string) array_key_first($psr4),
			'composer' => (string) $json['name'],
		];
	}

	uksort($packages, static function (string $a, string $b): int {
		$ta = array_search(explode('/', $a)[0], [...E7_TYPES, 'Core'], true);
		$tb = array_search(explode('/', $b)[0], [...E7_TYPES, 'Core'], true);

		return [$ta, $a] <=> [$tb, $b];
	});

	return $packages;
}

/**
 * The 28 packages E7 covers (everything but Core), filtered by --package <Type>[/<Name>] options.
 *
 * @param list<string> $filters
 *
 * @return array<string, array{type: string, name: string, dir: string, prefix: string, composer: string}>
 */
function e7Packages(array $filters = []): array
{
	$packages = array_filter(e7AllPackages(), static fn (array $p): bool => $p['type'] !== 'Core');

	if (count($packages) !== 28) {
		e7Fail(sprintf('expected 28 packages outside Core, found %d', count($packages)));
	}

	if ($filters === []) {
		return $packages;
	}

	$selected = [];

	foreach ($filters as $filter) {
		$matched = array_filter(
			$packages,
			static fn (array $p, string $key): bool => $key === $filter || $p['type'] === $filter,
			ARRAY_FILTER_USE_BOTH,
		);

		if ($matched === []) {
			e7Fail(sprintf('--package "%s" matches none of the 28 packages', $filter));
		}

		$selected += $matched;
	}

	return array_intersect_key($packages, $selected);
}

/**
 * The package key ("<Type>/<Name>") a repository-relative path belongs to, or "" for the root
 * tests/, bin/, public/ and migrations/.
 */
function e7PackageOf(string $file): string
{
	if (preg_match('~^src/FastyBird/([^/]+)/([^/]+)/~', $file, $m) === 1) {
		return $m[1] . '/' . $m[2];
	}

	return '';
}

/**
 * "src" for a package's production code, "tests" for any test code, "other" for the rest.
 */
function e7AreaOf(string $file): string
{
	if (preg_match('~^src/FastyBird/[^/]+/[^/]+/src/~', $file) === 1) {
		return 'src';
	}

	if (preg_match('~^(src/FastyBird/[^/]+/[^/]+/)?tests/~', $file) === 1) {
		return 'tests';
	}

	return 'other';
}

/**
 * @return list<string> repository-relative PHP paths below $directory, sorted
 */
function e7PhpFiles(string $directory): array
{
	$files = [];
	$base = e7Root() . '/' . $directory;

	if (!is_dir($base)) {
		return $files;
	}

	$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
		static fn (SplFileInfo $f): bool => !$f->isDir() || !in_array($f->getFilename(), ['node_modules', 'vendor'], true),
	));

	foreach ($iterator as $file) {
		if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
			$files[] = substr($file->getPathname(), strlen(e7Root()) + 1);
		}
	}

	sort($files, SORT_STRING);

	return $files;
}

/**
 * Refuses a symlinked or stale vendor/fastybird/* mirror, for all 29 packages.
 */
function e7CheckMirrors(): void
{
	foreach (e7AllPackages() as $key => $package) {
		$mirror = 'vendor/' . $package['composer'];

		if (is_link(e7Root() . '/' . $mirror)) {
			e7Fail(sprintf(
				'%s is a symlink; reinstall it as a copy: COMPOSER_MIRROR_PATH_REPOS=1 composer reinstall %s',
				$mirror,
				$package['composer'],
			));
		}

		if (!is_dir(e7Root() . '/' . $mirror . '/src')) {
			e7Fail(sprintf('%s/src does not exist; run COMPOSER_MIRROR_PATH_REPOS=1 composer install', $mirror));
		}

		$source = array_map(static fn (string $f): string => substr($f, strlen($package['dir'] . '/src/')), e7PhpFiles($package['dir'] . '/src'));
		$copy = array_map(static fn (string $f): string => substr($f, strlen($mirror . '/src/')), e7PhpFiles($mirror . '/src'));
		$stale = array_merge(array_diff($source, $copy), array_diff($copy, $source));

		foreach (array_intersect($source, $copy) as $file) {
			if (sha1_file(e7Root() . '/' . $package['dir'] . '/src/' . $file) !== sha1_file(e7Root() . '/' . $mirror . '/src/' . $file)) {
				$stale[] = $file;
			}
		}

		if ($stale !== []) {
			sort($stale);
			e7Fail(sprintf(
				'%s/src differs from %s/src in %d file(s), e.g. %s (package %s); refresh it: COMPOSER_MIRROR_PATH_REPOS=1 composer reinstall %s',
				$mirror,
				$package['dir'],
				count($stale),
				$stale[0],
				$key,
				$package['composer'],
			));
		}
	}
}

final class E7Collector extends NodeVisitorAbstract
{

	public string $file = '';

	/** @var array<string, mixed> */
	public array $out = [];

	/** @var list<string|null> */
	private array $classStack = [];

	/** @var list<Node> */
	private array $parents = [];

	private static function fq(Node\Name $name): string
	{
		$resolved = $name->getAttribute('resolvedName');

		return ltrim($resolved instanceof Node\Name ? $resolved->toString() : $name->toString(), '\\');
	}

	private function classConst(Node\Arg|Node\VariadicPlaceholder|null $arg): string|null
	{
		if (
			$arg instanceof Node\Arg
			&& $arg->value instanceof Node\Expr\ClassConstFetch
			&& $arg->value->class instanceof Node\Name
			&& $arg->value->name instanceof Node\Identifier
			&& $arg->value->name->toString() === 'class'
		) {
			return self::fq($arg->value->class);
		}

		return null;
	}

	private function current(): string|null
	{
		return $this->classStack === [] ? null : end($this->classStack);
	}

	public function enterNode(Node $node)
	{
		$parent = $this->parents === [] ? null : end($this->parents);
		$this->parents[] = $node;

		if ($node instanceof Node\Stmt\ClassLike) {
			$anonymous = $node instanceof Node\Stmt\Class_ && $node->name === null;
			$name = $anonymous
				? 'class@anonymous:' . $this->file . ':' . $node->getStartLine()
				: $node->namespacedName->toString();
			$this->classStack[] = $name;

			$extends = [];
			$implements = [];

			if ($node instanceof Node\Stmt\Class_) {
				$extends = $node->extends !== null ? [self::fq($node->extends)] : [];
				$implements = array_map(static fn (Node\Name $n): string => self::fq($n), $node->implements);
			} elseif ($node instanceof Node\Stmt\Interface_) {
				$extends = array_map(static fn (Node\Name $n): string => self::fq($n), $node->extends);
			} elseif ($node instanceof Node\Stmt\Enum_) {
				$implements = array_map(static fn (Node\Name $n): string => self::fq($n), $node->implements);
			}

			$traits = [];
			$properties = [];
			$methods = [];
			$constants = [];

			foreach ($node->stmts as $stmt) {
				if ($stmt instanceof Node\Stmt\TraitUse) {
					foreach ($stmt->traits as $trait) {
						$traits[] = self::fq($trait);
					}
				} elseif ($stmt instanceof Node\Stmt\Property) {
					foreach ($stmt->props as $prop) {
						$properties[$prop->name->toString()] = [
							'line' => $prop->getStartLine(),
							'readonly' => $stmt->isReadonly(),
							'static' => $stmt->isStatic(),
							'typed' => $stmt->type !== null,
							'public' => $stmt->isPublic(),
							'type' => $stmt->type instanceof Node\Identifier ? $stmt->type->toString() : null,
						];
					}
				} elseif ($stmt instanceof Node\Stmt\ClassMethod) {
					$methods[$stmt->name->toString()] = $stmt->getStartLine();

					if ($stmt->name->toLowerString() === '__construct') {
						foreach ($stmt->params as $param) {
							if ($param->flags !== 0 && $param->var instanceof Node\Expr\Variable && is_string($param->var->name)) {
								$properties[$param->var->name] = [
									'line' => $param->getStartLine(),
									'readonly' => ($param->flags & Node\Stmt\Class_::MODIFIER_READONLY) !== 0,
									'static' => false,
									'typed' => $param->type !== null,
									'public' => ($param->flags & Node\Stmt\Class_::MODIFIER_PUBLIC) !== 0,
									'type' => null,
									'promoted' => true,
								];
							}
						}
					}
				} elseif ($stmt instanceof Node\Stmt\ClassConst) {
					foreach ($stmt->consts as $const) {
						$constants[$const->name->toString()] = [
							'line' => $const->getStartLine(),
							'typed' => $stmt->type !== null,
							'literal' => self::literalType($const->value),
						];
					}
				}
			}

			$attributes = [];

			foreach ($node->attrGroups as $group) {
				foreach ($group->attrs as $attr) {
					$attributes[] = self::fq($attr->name);
				}
			}

			$this->out['decls'][$name] = [
				'kind' => match (true) {
					$node instanceof Node\Stmt\Interface_ => 'interface',
					$node instanceof Node\Stmt\Trait_ => 'trait',
					$node instanceof Node\Stmt\Enum_ => 'enum',
					default => 'class',
				},
				'file' => $this->file,
				'line' => $node->getStartLine(),
				'final' => $node instanceof Node\Stmt\Class_ && $node->isFinal(),
				'abstract' => $node instanceof Node\Stmt\Class_ && $node->isAbstract(),
				'readonly' => $node instanceof Node\Stmt\Class_ && $node->isReadonly(),
				'anonymous' => $anonymous,
				'extends' => $extends,
				'implements' => $implements,
				'traits' => $traits,
				'attributes' => $attributes,
				'properties' => $properties,
				'methods' => $methods,
				'constants' => $constants,
				'doc' => $node->getDocComment()?->getText(),
				'docLine' => $node->getDocComment()?->getStartLine(),
			];
		}

		if ($node instanceof Node\Stmt\Catch_) {
			foreach ($node->types as $type) {
				$this->out['catches'][] = [self::fq($type), $this->file, $node->getStartLine()];
			}
		}

		if ($node instanceof Node\Expr\Instanceof_ && $node->class instanceof Node\Name) {
			$this->out['instanceofs'][] = [self::fq($node->class), $this->file, $node->getStartLine()];
		}

		if (
			($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall || $node instanceof Node\Expr\StaticCall)
			&& $node->name instanceof Node\Identifier
		) {
			$method = $node->name->toString();
			$receiver = $node instanceof Node\Expr\StaticCall
				? ($node->class instanceof Node\Name ? self::fq($node->class) : '?')
				: self::receiver($node->var);

			if (in_array($method, ['findByType', 'getByType', 'setImplement', 'getDefinitionByType', 'setType', 'getService', 'createService', 'getByName', 'findByTag'], true)) {
				$this->out['lookups'][] = [$method, $this->classConst($node->args[0] ?? null), $receiver, $this->file, $node->getStartLine(), $this->current()];
			}

			if (in_array($method, ['createMock', 'createPartialMock', 'createStub', 'getMockBuilder', 'createConfiguredMock', 'getMockForAbstractClass', 'createMockForIntersectionOfInterfaces'], true)) {
				$this->out['mocks'][] = [$this->classConst($node->args[0] ?? null), $this->file, $node->getStartLine()];
			}

			if (!$node instanceof Node\Expr\StaticCall && $receiver === '$this') {
				$this->out['thisCalls'][] = [$this->current(), $method, $this->file, $node->getStartLine(), $parent instanceof Node\Stmt\Expression];
			}
		}

		if (($node instanceof Node\Expr\PropertyFetch || $node instanceof Node\Expr\NullsafePropertyFetch) && $node->name instanceof Node\Identifier) {
			$context = 'read';

			if ($parent instanceof Node\Expr\Isset_) {
				$context = 'isset';
			} elseif ($parent instanceof Node\Stmt\Unset_) {
				$context = 'unset';
			} elseif ($parent instanceof Node\Expr\Assign && $parent->var === $node) {
				$context = 'write';
			}

			$this->out['fetches'][] = [$node->name->toString(), self::receiver($node->var), $this->current(), $this->file, $node->getStartLine(), $context];
		}

		return null;
	}

	public function leaveNode(Node $node)
	{
		array_pop($this->parents);

		if ($node instanceof Node\Stmt\ClassLike) {
			array_pop($this->classStack);
		}

		return null;
	}

	private static function receiver(Node\Expr $expr): string
	{
		if ($expr instanceof Node\Expr\Variable && is_string($expr->name)) {
			return '$' . $expr->name;
		}

		if ($expr instanceof Node\Expr\PropertyFetch && $expr->var instanceof Node\Expr\Variable && $expr->name instanceof Node\Identifier) {
			return '$' . $expr->var->name . '->' . $expr->name->toString();
		}

		return '?';
	}

	private static function literalType(Node\Expr $value): string|null
	{
		return match (true) {
			$value instanceof Node\Scalar\String_, $value instanceof Node\Scalar\InterpolatedString => 'string',
			$value instanceof Node\Scalar\Int_ => 'int',
			$value instanceof Node\Scalar\Float_ => 'float',
			$value instanceof Node\Expr\Array_ => 'array',
			$value instanceof Node\Expr\UnaryMinus && $value->expr instanceof Node\Scalar\Int_ => 'int',
			$value instanceof Node\Expr\UnaryMinus && $value->expr instanceof Node\Scalar\Float_ => 'float',
			$value instanceof Node\Expr\ConstFetch && in_array($value->name->toLowerString(), ['true', 'false'], true) => 'bool',
			$value instanceof Node\Expr\ConstFetch && $value->name->toLowerString() === 'null' => 'null',
			default => null,
		};
	}

}

/**
 * @return array{decls: array<string, array<string, mixed>>, catches: list<array{string, string, int}>, instanceofs: list<array{string, string, int}>, lookups: list<array{string, string|null, string, string, int, string|null}>, mocks: list<array{string|null, string, int}>, thisCalls: list<array{string|null, string, string, int, bool}>, fetches: list<array{string, string, string|null, string, int, string}>, files: list<string>, errors: list<array{string, string}>}
 */
function e7Index(): array
{
	static $index = null;

	if ($index !== null) {
		return $index;
	}

	$files = [];

	foreach (E7_INDEXED as $directory) {
		array_push($files, ...e7PhpFiles($directory));
	}

	$hash = hash_init('sha1');

	foreach ($files as $file) {
		hash_update($hash, $file . "\0" . sha1_file(e7Root() . '/' . $file) . "\n");
	}

	hash_update($hash, (string) sha1_file(__FILE__));
	$cache = e7Scratch() . '/index-' . hash_final($hash) . '.json';

	if (is_file($cache)) {
		return $index = json_decode((string) file_get_contents($cache), true, 512, JSON_THROW_ON_ERROR);
	}

	$parser = (new ParserFactory())->createForHostVersion();
	$collector = new E7Collector();
	$collector->out = ['decls' => [], 'catches' => [], 'instanceofs' => [], 'lookups' => [], 'mocks' => [], 'thisCalls' => [], 'fetches' => [], 'files' => $files, 'errors' => []];
	// Two passes: NameResolver resolves a name when it enters the node that holds it, so a
	// ::class argument is not resolved yet when the collector enters the call around it.
	$resolver = new NodeTraverser();
	$resolver->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true, 'replaceNodes' => false]));
	$traverser = new NodeTraverser();
	$traverser->addVisitor($collector);

	foreach ($files as $file) {
		try {
			$ast = $parser->parse((string) file_get_contents(e7Root() . '/' . $file));
		} catch (Throwable $e) {
			$collector->out['errors'][] = [$file, $e->getMessage()];

			continue;
		}

		$collector->file = $file;
		$traverser->traverse($resolver->traverse($ast ?? []));
	}

	file_put_contents($cache, json_encode($collector->out, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

	return $index = $collector->out;
}

/**
 * Every class, interface or enum the index knows, with the full set of interfaces it implements
 * (its own, their parents, and its parent class's), as FQCN => list.
 *
 * @return array<string, list<string>>
 */
function e7InterfaceClosure(): array
{
	static $closure = null;

	if ($closure !== null) {
		return $closure;
	}

	$decls = e7Index()['decls'];
	$closure = [];

	$resolve = static function (string $fqcn, array $seen = []) use (&$resolve, &$closure, $decls): array {
		if (isset($closure[$fqcn])) {
			return $closure[$fqcn];
		}

		if (!isset($decls[$fqcn]) || isset($seen[$fqcn])) {
			return [];
		}

		$seen[$fqcn] = true;
		$decl = $decls[$fqcn];
		$set = [];
		$direct = $decl['kind'] === 'interface' ? $decl['extends'] : $decl['implements'];

		foreach ($direct as $interface) {
			$set[$interface] = true;

			foreach ($resolve($interface, $seen) as $inherited) {
				$set[$inherited] = true;
			}
		}

		if ($decl['kind'] !== 'interface') {
			foreach ($decl['extends'] as $parentClass) {
				foreach ($resolve($parentClass, $seen) as $inherited) {
					$set[$inherited] = true;
				}
			}
		}

		return $closure[$fqcn] = array_keys($set);
	};

	foreach (array_keys($decls) as $fqcn) {
		$resolve($fqcn);
	}

	return $closure;
}

/**
 * The ancestors of a class known to the index, nearest first.
 *
 * @return list<string>
 */
function e7Ancestors(string $fqcn): array
{
	$decls = e7Index()['decls'];
	$chain = [];

	while (isset($decls[$fqcn]) && $decls[$fqcn]['kind'] === 'class' && $decls[$fqcn]['extends'] !== []) {
		$fqcn = $decls[$fqcn]['extends'][0];

		if (in_array($fqcn, $chain, true)) {
			break;
		}

		$chain[] = $fqcn;
	}

	return $chain;
}

/**
 * Whether a class is Doctrine-mapped, directly (#[ORM\Entity|MappedSuperclass|Embeddable]).
 */
function e7IsMapped(array $decl): bool
{
	return array_intersect($decl['attributes'], E7_ORM_MAPPED) !== [];
}

function e7Short(string $fqcn, string $package = ''): string
{
	$packages = e7AllPackages();

	if ($package !== '' && isset($packages[$package]) && str_starts_with($fqcn, $packages[$package]['prefix'])) {
		return substr($fqcn, strlen($packages[$package]['prefix']));
	}

	return preg_replace('~^FastyBird\\\\~', '', $fqcn) ?? $fqcn;
}

/**
 * @param list<string> $argv
 *
 * @return array{0: list<string>, 1: list<string>, 2: array<string, string>} positional arguments, --package filters, other --options
 */
function e7Arguments(array $argv): array
{
	$positional = [];
	$packages = [];
	$options = [];

	for ($i = 1; $i < count($argv); $i++) {
		if ($argv[$i] === '--package') {
			$packages[] = $argv[++$i] ?? e7Fail('--package needs <Type> or <Type>/<Name>');
		} elseif (str_starts_with($argv[$i], '--')) {
			$options[substr($argv[$i], 2)] = $argv[++$i] ?? e7Fail($argv[$i - 1] . ' needs a value');
		} else {
			$positional[] = $argv[$i];
		}
	}

	return [$positional, $packages, $options];
}
