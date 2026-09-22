<?php declare(strict_types = 1);

namespace FastyBird\Core\Middleware\SlimRouter;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Router middleware dispatcher interface
 */
interface IMiddlewareDispatcher extends RequestHandlerInterface
{

	/**
	 * Add a new middleware to the stack
	 *
	 * Middleware are organized as a stack. That means middleware
	 * that have been added before will be executed after the newly
	 * added one (last in, first out).
	 */
	public function add(MiddlewareInterface $middleware): void;

	/**
	 * Seed the middleware stack with the inner request handler
	 */
	public function seedMiddlewareStack(RequestHandlerInterface $kernel): void;

}
