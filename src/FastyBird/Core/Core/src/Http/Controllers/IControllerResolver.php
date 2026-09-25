<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Controllers;

/**
 * Endpoint controller callback resolver interface
 */
interface IControllerResolver
{

	/**
	 * Resolve $toResolve into a callable
	 *
	 * @param string|callable|array<mixed> $toResolve
	 */
	public function resolve(string|callable|array $toResolve): callable;

}
