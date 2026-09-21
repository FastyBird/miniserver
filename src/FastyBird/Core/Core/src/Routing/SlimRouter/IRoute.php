<?php declare(strict_types = 1);

/**
 * Route.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:SlimRouter!
 * @subpackage     Routing
 * @since          0.1.0
 *
 * @date           14.03.20
 */

namespace FastyBird\Core\Routing\SlimRouter;

use FastyBird\Core\Routing\SlimRouter as Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface IRoute
{

	public function setInvocationHandler(Routing\Handlers\IHandler $invocationStrategy): void;

	/**
	 * @return array<string>
	 */
	public function getMethods(): array;

	public function getPattern(): string;

	/**
	 * @return callable|string|array<mixed>
	 */
	public function getCallable(): callable|string|array;

	public function setName(string $name): void;

	public function getName(): string|null;

	public function getIdentifier(): string;

	public function setArgument(string $name, string $value): void;

	public function getArgument(string $name, string|null $default = null): string|null;

	/**
	 * @param array<string> $arguments
	 */
	public function setArguments(array $arguments): void;

	/**
	 * @return array<string>
	 */
	public function getArguments(): array;

	public function addMiddleware(MiddlewareInterface $middleware): void;

	/**
	 * @param array<mixed> $arguments
	 */
	public function prepare(array $arguments): void;

	/**
	 * Run route
	 *
	 * This method traverses the middleware stack, including the route's callable
	 * and captures the resultant HTTP response object. It then sends the response
	 * back to the Application.
	 */
	public function run(ServerRequestInterface $request): ResponseInterface;

}
