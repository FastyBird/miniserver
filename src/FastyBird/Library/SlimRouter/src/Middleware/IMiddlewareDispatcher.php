<?php declare(strict_types = 1);

/**
 * IMiddlewareDispatcher.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Middleware
 * @since          0.1.0
 *
 * @date           15.03.20
 */

namespace FastyBird\Library\SlimRouter\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Router middleware dispatcher interface
 *
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Middleware
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
