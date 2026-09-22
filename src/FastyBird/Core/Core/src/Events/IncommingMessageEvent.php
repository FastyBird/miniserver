<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http;
use Symfony\Contracts\EventDispatcher;

/**
 * Incomming message event
 */
final class IncommingMessageEvent extends EventDispatcher\Event
{

	public function __construct(
		private Entities\IClient $client,
		private Http\IRequest $httpRequest,
		private string $message,
	)
	{
	}

	public function getClient(): Entities\IClient
	{
		return $this->client;
	}

	public function getHttpRequest(): Http\IRequest
	{
		return $this->httpRequest;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

}
