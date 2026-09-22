<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http;
use Symfony\Contracts\EventDispatcher;

/**
 * After incomming message event
 */
final class AfterIncommingMessageEvent extends EventDispatcher\Event
{

	public function __construct(
		private Entities\IClient $client,
		private Http\IRequest $httpRequest,
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

}
