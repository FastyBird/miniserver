<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing\Handlers;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Default route callback strategy with route parameters as an array of arguments.
 */
final class RequestResponseHandler implements Handler
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
		foreach ($routeArguments as $k => $v) {
			$request = $request->withAttribute($k, $v);
		}

		return $callable($request, $response, $routeArguments);
	}

}
