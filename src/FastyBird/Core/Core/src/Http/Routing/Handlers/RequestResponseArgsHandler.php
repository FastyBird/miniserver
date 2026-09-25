<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing\Handlers;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function array_values;

/**
 * Route callback strategy with route parameters as individual arguments.
 */
final class RequestResponseArgsHandler implements Handler
{

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function __invoke(
		callable $callable,
		ServerRequestInterface $request,
		ResponseInterface $response,
		array $routeArguments,
	): ResponseInterface
	{
		return $callable($request, $response, ...array_values($routeArguments));
	}

}
