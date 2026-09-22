<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing\Handlers;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-15 RequestHandler invocation strategy
 */
final class RequestHandler implements IRequestHandler
{

	public function __construct(private bool $appendRouteArgumentsToRequestAttributes = false)
	{
	}

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
		if ($this->appendRouteArgumentsToRequestAttributes) {
			foreach ($routeArguments as $k => $v) {
				$request = $request->withAttribute($k, $v);
			}
		}

		return $callable($request);
	}

}
