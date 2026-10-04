<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Middleware;

use FastyBird\Core\Http\Events;
use FastyBird\Core\Http\Routing;
use InvalidArgumentException;
use Psr\EventDispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application router middleware
 */
final readonly class Router
{

	public function __construct(
		private Routing\Router $router,
		private EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		$this->dispatcher?->dispatch(new Events\HttpServerRequest($request));

		$response = $this->router->handle($request);

		$this->dispatcher?->dispatch(new Events\HttpServerResponse($request, $response));

		return $response;
	}

}
