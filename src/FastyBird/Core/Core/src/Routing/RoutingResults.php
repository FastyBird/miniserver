<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing;

use function rawurldecode;

class RoutingResults
{

	public const int NOT_FOUND = 0;

	public const int FOUND = 1;

	public const int METHOD_NOT_ALLOWED = 2;

	/**
	 * The status is one of the constants shown above
	 *
	 * NOT_FOUND = 0
	 * FOUND = 1
	 * METHOD_NOT_ALLOWED = 2
	 */
	private int $routeStatus;

	/**
	 * @param array<mixed> $routeArguments
	 */
	public function __construct(
		private string $method,
		private string $uri,
		int $routeStatus,
		private string|null $routeIdentifier = null,
		private array $routeArguments = [],
	)
	{
		$this->routeStatus = $routeStatus;
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function getRouteStatus(): int
	{
		return $this->routeStatus;
	}

	public function getRouteIdentifier(): string|null
	{
		return $this->routeIdentifier;
	}

	/**
	 * @return array<mixed>
	 */
	public function getRouteArguments(bool $urlDecode = true): array
	{
		if (!$urlDecode) {
			return $this->routeArguments;
		}

		$routeArguments = [];

		foreach ($this->routeArguments as $key => $value) {
			$routeArguments[$key] = rawurldecode($value);
		}

		return $routeArguments;
	}

}
