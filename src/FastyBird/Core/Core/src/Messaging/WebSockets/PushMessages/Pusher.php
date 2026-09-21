<?php declare(strict_types = 1);

/**
 * Pusher.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 * @since          1.0.0
 *
 * @date           28.02.17
 */

namespace FastyBird\Core\Messaging\WebSockets\PushMessages;

use FastyBird\Core\Encoding\WebSockets as Serializers;
use FastyBird\Core\Entities\WebSockets\PushMessages as Entities;
use FastyBird\Core\Exceptions as WebSocketsExceptions;
use FastyBird\Core\Routing\WebSockets as WebSocketsRouter;
use Nette;
use ReflectionException;

/**
 * Server message pusher
 *
 * @package        iPublikuj:WebSocketsWAMP!
 * @subpackage     PushMessages
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
abstract class Pusher implements IPusher
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	private bool $connected = false;

	public function __construct(
		private string $name,
		private Serializers\PushMessageSerializer $serializer,
		private WebSocketsRouter\LinkGenerator $linkGenerator,
	)
	{
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws WebSocketsExceptions\InvalidLink
	 * @throws ReflectionException
	 */
	public function push(
		array|string $data,
		string $destination,
		array $routeParameters = [],
		array $context = [],
	): void
	{
		$channel = $this->linkGenerator->link($destination, $routeParameters);

		$message = new Entities\Message($channel, $data);

		$this->doPush($this->serializer->serialize($message), $context);
	}

	public function setConnected(bool $bool = true): void
	{
		$this->connected = $bool;
	}

	public function isConnected(): bool
	{
		return $this->connected;
	}

	public function getName(): string
	{
		return $this->name;
	}

	abstract protected function doPush(string $data, array $context = []): void;

}
