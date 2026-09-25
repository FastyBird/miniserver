<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * After incomming message event
 */
final class AfterIncommingMessageEvent extends EventDispatcher\Event
{

	public function __construct(
		private Entities\ConnectedClient $client,
		private Handshake\IRequest $httpRequest,
	)
	{
	}

	public function getClient(): Entities\ConnectedClient
	{
		return $this->client;
	}

	public function getHttpRequest(): Handshake\IRequest
	{
		return $this->httpRequest;
	}

}
