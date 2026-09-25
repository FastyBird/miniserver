<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\PushMessages;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;
use FastyBird\Core\WebSockets\Encoding;
use FastyBird\Core\WebSockets\Entities\PushMessages;
use Override;
use ReflectionException;

/**
 * Server message pusher
 */
abstract class Pusher implements IPusher
{

	private bool $connected = false;

	public function __construct(
		private string $name,
		private Encoding\PushMessageSerializer $serializer,
		private Routing\LinkGenerator $linkGenerator,
	)
	{
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidLink
	 * @throws ReflectionException
	 */
	#[Override]
	public function push(
		array|string $data,
		string $destination,
		array $routeParameters = [],
		array $context = [],
	): void
	{
		$channel = $this->linkGenerator->link($destination, $routeParameters);

		$message = new PushMessages\Message($channel, $data);

		$this->doPush($this->serializer->serialize($message), $context);
	}

	#[Override]
	public function setConnected(bool $bool = true): void
	{
		$this->connected = $bool;
	}

	#[Override]
	public function isConnected(): bool
	{
		return $this->connected;
	}

	#[Override]
	public function getName(): string
	{
		return $this->name;
	}

	abstract protected function doPush(string $data, array $context = []): void;

}
