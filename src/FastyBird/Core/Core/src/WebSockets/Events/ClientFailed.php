<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Server\Wrapper when an established connection fails, before the application
 * handles the error. It carries no exception; ApplicationFailed does.
 */
final class ClientFailed extends EventDispatcher\Event
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
