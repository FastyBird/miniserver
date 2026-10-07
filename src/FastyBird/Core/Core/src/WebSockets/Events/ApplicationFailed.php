<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Dispatched by Controllers\Application::handleError(), before it closes the client.
 */
final class ApplicationFailed extends EventDispatcher\Event
{

	public function __construct(
		private readonly Controllers\Dispatcher $application,
		private readonly Entities\ConnectedClient $client,
		private readonly Handshake\Request $httpRequest,
		private readonly Throwable $ex,
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

	public function getHttpRequest(): Handshake\Request
	{
		return $this->httpRequest;
	}

	public function getException(): Throwable
	{
		return $this->ex;
	}

}
