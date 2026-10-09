<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Server\Wrapper after the application has handled a message.
 */
final class MessageProcessed extends EventDispatcher\Event
{

	public function __construct(
		private readonly Entities\Client $client,
		private readonly Handshake\Request $httpRequest,
	)
	{
	}

	public function getClient(): Entities\Client
	{
		return $this->client;
	}

	public function getHttpRequest(): Handshake\Request
	{
		return $this->httpRequest;
	}

}
