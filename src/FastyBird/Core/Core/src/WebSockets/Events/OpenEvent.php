<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * Connection open event
 */
final class OpenEvent extends EventDispatcher\Event
{

	public function __construct(
		private Controllers\Dispatcher $application,
		private Entities\ConnectedClient $client,
		private Handshake\IRequest $httpRequest,
	)
	{
	}

	public function getApplication(): Controllers\Dispatcher
	{
		return $this->application;
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
