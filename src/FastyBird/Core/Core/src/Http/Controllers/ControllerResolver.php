<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Controllers;

use FastyBird\Core\Exceptions;
use Override;
use function class_exists;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function json_encode;
use function preg_match;
use function sprintf;

/**
 * Endpoint controller callback resolver
 */
final class ControllerResolver implements IControllerResolver
{

	private const string CALLABLE_PATTERN = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\Runtime
	 */
	#[Override]
	public function resolve($toResolve): callable
	{
		if (is_callable($toResolve)) {
			return $toResolve;
		}

		$resolved = $toResolve;

		if (is_string($toResolve)) {
			$resolved = $this->resolveClassNotation($toResolve);
			$resolved[1] ??= '__invoke';
		}

		return $this->assertCallable($resolved, $toResolve);
	}

	/**
	 * @return array<mixed> [Instance, Method Name]
	 *
	 * @throws Exceptions\Runtime
	 */
	private function resolveClassNotation(string $toResolve): array
	{
		preg_match(self::CALLABLE_PATTERN, $toResolve, $matches);

		[$class, $method] = $matches ? [$matches[1], $matches[2]] : [$toResolve, null];

		if (!class_exists($class)) {
			throw new Exceptions\Runtime(sprintf('Callable %s does not exist', $class));
		}

		$instance = new $class();

		return [$instance, $method];
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function assertCallable(mixed $resolved, mixed $toResolve): callable
	{
		if (!is_callable($resolved)) {
			throw new Exceptions\Runtime(sprintf(
				'%s is not resolvable',
				is_callable($toResolve) || is_object($toResolve) || is_array($toResolve)
					? json_encode($toResolve)
					: $toResolve,
			));
		}

		return $resolved;
	}

}
