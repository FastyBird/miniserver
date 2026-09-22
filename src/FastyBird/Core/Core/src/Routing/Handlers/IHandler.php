<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Defines a contract for invoking a route callable.
 */
interface IHandler
{

	/**
	 * @param array<mixed> $routeArguments
	 */
	public function __invoke(
		callable $callable,
		ServerRequestInterface $request,
		ResponseInterface $response,
		array $routeArguments,
	): ResponseInterface;

}
