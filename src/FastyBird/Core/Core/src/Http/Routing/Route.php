<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Routing;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Controllers;
use FastyBird\Core\Http\Middleware;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;
use function array_key_exists;
use function array_replace;
use function class_implements;
use function in_array;
use function is_array;

final class Route implements IRoute, RequestHandlerInterface
{

	private string $identifier;

	private string|null $name = null;

	/** @var array<mixed> */
	private array $arguments = [];

	/** @var array<mixed> */
	private array $savedArguments = [];

	/** @var callable|string|array<mixed> */
	private $callable;

	private Middleware\MiddlewareDispatcher $middlewareDispatcher;

	private bool $groupMiddlewareAppended = false;

	/**
	 * @param array<string> $methods                 The route HTTP methods
	 * @param string $pattern                   The route pattern
	 * @param callable|string|array<mixed> $callable The route callable
	 *
	 * @throws Exceptions\Runtime
	 */
	public function __construct(
		private array $methods,
		private string $pattern,
		callable|string|array $callable,
		private IRouteCollector $routeCollector,
		private ResponseFactoryInterface $responseFactory,
		private Controllers\IControllerResolver $controllerResolver,
		private Handlers\Handler $invocationHandler,
	)
	{
		$this->callable = $callable;

		try {
			$this->identifier = Uuid::uuid4()->toString();

		} catch (Throwable) {
			throw new Exceptions\Runtime('Could not create route identifier');
		}

		$this->middlewareDispatcher = new Middleware\MiddlewareDispatcher($this);
	}

	#[Override]
	public function setInvocationHandler(Handlers\Handler $invocationHandler): void
	{
		$this->invocationHandler = $invocationHandler;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getMethods(): array
	{
		return $this->methods;
	}

	#[Override]
	public function getPattern(): string
	{
		return $this->routeCollector->getPattern() . $this->pattern;
	}

	#[Override]
	public function getCallable(): callable|string|array
	{
		return $this->callable;
	}

	#[Override]
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	#[Override]
	public function getName(): string|null
	{
		return $this->name;
	}

	#[Override]
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	#[Override]
	public function setArgument(string $name, string $value, bool $includeInSavedArguments = true): void
	{
		if ($includeInSavedArguments) {
			$this->savedArguments[$name] = $value;
		}

		$this->arguments[$name] = $value;
	}

	#[Override]
	public function getArgument(string $name, string|null $default = null): string|null
	{
		if (array_key_exists($name, $this->arguments)) {
			return $this->arguments[$name];
		}

		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function setArguments(array $arguments, bool $includeInSavedArguments = true): void
	{
		if ($includeInSavedArguments) {
			$this->savedArguments = $arguments;
		}

		$this->arguments = $arguments;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function getArguments(): array
	{
		return $this->arguments;
	}

	#[Override]
	public function addMiddleware(MiddlewareInterface $middleware): void
	{
		$this->middlewareDispatcher->add($middleware);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function prepare(array $arguments): void
	{
		$this->arguments = array_replace($this->savedArguments, $arguments) ?? [];
	}

	#[Override]
	public function run(ServerRequestInterface $request): ResponseInterface
	{
		if (!$this->groupMiddlewareAppended) {
			$inner = $this->middlewareDispatcher;

			$this->middlewareDispatcher = new Middleware\MiddlewareDispatcher($inner);

			$this->routeCollector->appendMiddlewareToDispatcher($this->middlewareDispatcher);

			$this->groupMiddlewareAppended = true;
		}

		return $this->middlewareDispatcher->handle($request);
	}

	#[Override]
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$callable = $this->controllerResolver->resolve($this->callable);
		$strategy = $this->invocationHandler;

		if (
			is_array($callable)
			&& $callable[0] instanceof RequestHandlerInterface
			&& class_implements($strategy) !== false
			&& !in_array(Handlers\IRequestHandler::class, class_implements($strategy), true)
		) {
			$strategy = new Handlers\RequestHandler();
		}

		$response = $this->responseFactory->createResponse();

		return $strategy($callable, $request, $response, $this->arguments);
	}

}
