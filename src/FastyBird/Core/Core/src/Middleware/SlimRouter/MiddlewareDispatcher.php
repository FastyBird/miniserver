<?php declare(strict_types = 1);

namespace FastyBird\Core\Middleware\SlimRouter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Router middleware dispatcher
 */
class MiddlewareDispatcher implements IMiddlewareDispatcher
{

	/**
	 * Tip of the middleware call stack
	 */
	private RequestHandlerInterface $tip;

	public function __construct(RequestHandlerInterface $kernel)
	{
		$this->seedMiddlewareStack($kernel);
	}

	public function seedMiddlewareStack(RequestHandlerInterface $kernel): void
	{
		$this->tip = $kernel;
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->tip->handle($request);
	}

	public function add(MiddlewareInterface $middleware): void
	{
		$next = $this->tip;

		$this->tip = new class ($middleware, $next) implements RequestHandlerInterface {

			public function __construct(private MiddlewareInterface $middleware, private RequestHandlerInterface $next)
			{
			}

			public function handle(ServerRequestInterface $request): ResponseInterface
			{
				return $this->middleware->process($request, $this->next);
			}

		};
	}

}
