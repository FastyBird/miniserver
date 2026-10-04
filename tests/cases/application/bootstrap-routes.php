<?php declare(strict_types = 1);

/**
 * Boots the application exactly as public/index.php does -- Bootstrap::boot() with FB_APP_DIR
 * pointing at the repository root, so config/common.neon and every extension it registers
 * load -- then walks every route registered on the API router and reports, as JSON on stdout,
 * what each route's callable resolves to, and, for every controller finder trait a routed
 * controller composes, whether the controller calls it and which properties it reads.
 *
 * Run as a child process by RouteCallableGuardTest; see EntityMappingTest for why the
 * production scope cannot be booted in-process.
 */

// The deprecation notices vendor emits on PHP 8.4 would otherwise be interleaved with the
// JSON this script writes to stdout. See tools/php.d/tests.ini.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require __DIR__ . '/../../../vendor/autoload.php';

use FastyBird\Core\Boot;
use FastyBird\Core\Http\Routing;

$report = static function (array $payload): never {
	echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), PHP_EOL;

	exit(0);
};

/**
 * Every trait a class composes, through its parents and through traits that compose traits.
 *
 * @param ReflectionClass<object> $class
 *
 * @return array<class-string, ReflectionClass<object>>
 */
$traitsOf = static function (ReflectionClass $class): array {
	$traits = [];
	$pending = [];

	for ($current = $class; $current !== false; $current = $current->getParentClass()) {
		$pending = [...$pending, ...array_values($current->getTraits())];
	}

	while ($pending !== []) {
		$trait = array_shift($pending);

		if (array_key_exists($trait->getName(), $traits)) {
			continue;
		}

		$traits[$trait->getName()] = $trait;
		$pending = [...$pending, ...array_values($trait->getTraits())];
	}

	return $traits;
};

/**
 * A file's tokens, without whitespace and comments.
 *
 * @return list<PhpToken>
 */
$tokensOf = static function (string|false $file): array {
	if ($file === false) {
		return [];
	}

	return array_values(array_filter(
		PhpToken::tokenize((string) file_get_contents($file)),
		static fn (PhpToken $token): bool => !$token->isIgnorable(),
	));
};

/**
 * The method names a file calls as `->name(...)`, `?->name(...)` or `::name(...)`.
 *
 * @return array<string, true>
 */
$methodsCalledIn = static function (string|false $file) use ($tokensOf): array {
	$tokens = $tokensOf($file);

	$called = [];

	foreach ($tokens as $i => $token) {
		if (
			$token->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON])
			&& isset($tokens[$i + 1], $tokens[$i + 2])
			&& $tokens[$i + 1]->is(T_STRING)
			&& $tokens[$i + 2]->text === '('
		) {
			$called[$tokens[$i + 1]->text] = true;
		}
	}

	return $called;
};

/**
 * The property names a trait reads as `$this->name`, excluding `$this->name(...)` method calls.
 *
 * @param ReflectionClass<object> $trait
 *
 * @return list<string>
 */
$propertiesReadBy = static function (ReflectionClass $trait) use ($tokensOf): array {
	$tokens = $tokensOf($trait->getFileName());

	$properties = [];

	foreach ($tokens as $i => $token) {
		if (
			$token->is(T_VARIABLE)
			&& $token->text === '$this'
			&& isset($tokens[$i + 1], $tokens[$i + 2], $tokens[$i + 3])
			&& $tokens[$i + 1]->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])
			&& $tokens[$i + 2]->is(T_STRING)
			&& $tokens[$i + 3]->text !== '('
		) {
			$properties[$tokens[$i + 2]->text] = true;
		}
	}

	return array_keys($properties);
};

try {
	$configurator = Boot\Bootstrap::boot();

	// Same override, same fixture and same reason as bootstrap-production-scope.php
	$configurator->addConfig([
		'contributteVite' => ['manifestFile' => __DIR__ . '/fixtures/vite-manifest.json'],
	]);

	$container = $configurator->createContainer();

	$router = $container->getByType(Routing\Router::class);

	$routes = [];
	$controllers = [];

	foreach ($router as $route) {
		$callable = $route->getCallable();

		$controller = null;
		$method = null;
		$public = false;

		if (
			is_array($callable)
			&& count($callable) === 2
			&& (is_object($callable[0]) || is_string($callable[0]))
			&& is_string($callable[1])
		) {
			$controller = is_object($callable[0]) ? $callable[0]::class : $callable[0];
			$method = $callable[1];
			// The route invokes the callable from outside the controller, so only a public method
			// can answer it
			$public = class_exists($controller)
				&& method_exists($controller, $method)
				&& (new ReflectionMethod($controller, $method))->isPublic();

			if (class_exists($controller)) {
				$controllers[$controller] = true;
			}
		}

		$routes[] = [
			'route' => implode('|', $route->getMethods()) . ' ' . $route->getPattern(),
			'controller' => $controller,
			'method' => $method,
			'public' => $public,
		];
	}

	$finders = [];

	foreach (array_keys($controllers) as $controller) {
		$class = new ReflectionClass($controller);
		$traits = $traitsOf($class);

		$hierarchy = [];

		for ($current = $class; $current !== false; $current = $current->getParentClass()) {
			$hierarchy[] = $current;
		}

		foreach ($traits as $traitName => $trait) {
			if (!str_contains($traitName, '\\Controllers\\Finders\\')) {
				continue;
			}

			// A finder is only a dependency of the controller when the controller, a parent of
			// it or another trait it composes actually calls one of the finder's methods. A
			// composed but never called finder reads nothing at runtime.
			$callers = array_filter(
				[...$hierarchy, ...array_values($traits)],
				static fn (ReflectionClass $caller): bool => $caller->getName() !== $traitName,
			);

			$called = [];

			foreach ($callers as $caller) {
				$called += $methodsCalledIn($caller->getFileName());
			}

			$isCalled = false;

			foreach ($trait->getMethods() as $method) {
				// A method the controller declares itself overrides the trait's one of that name
				if (
					array_key_exists($method->getName(), $called)
					&& $class->getMethod($method->getName())->getFileName() === $trait->getFileName()
				) {
					$isCalled = true;

					break;
				}
			}

			foreach ($propertiesReadBy($trait) as $property) {
				$finders[] = [
					'controller' => $controller,
					'trait' => $traitName,
					'called' => $isCalled,
					'property' => $property,
					'declared' => $class->hasProperty($property),
				];
			}
		}
	}

	$report([
		'error' => null,
		'routes' => $routes,
		'finders' => $finders,
	]);
} catch (Throwable $ex) {
	$report([
		'error' => $ex::class . ': ' . $ex->getMessage(),
		'routes' => [],
		'finders' => [],
	]);
}
