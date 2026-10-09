<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;

/**
 * Dispatched by Controllers\Application::handleOpen().
 */
final class ConnectionOpened extends EventDispatcher\Event
{

	public function __construct(
		private readonly Controllers\Application $application,
		private readonly Entities\Client $client,
		private readonly Handshake\Request $httpRequest,
	)
	{
	}

	public function getApplication(): Controllers\Application
	{
		return $this->application;
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
