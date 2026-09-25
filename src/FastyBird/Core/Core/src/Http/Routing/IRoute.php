<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface IRoute
{

	public function setInvocationHandler(Handlers\Handler $invocationStrategy): void;

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
