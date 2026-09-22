<?php declare(strict_types = 1);

namespace FastyBird\Core\Events;

use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http;
use Symfony\Contracts\EventDispatcher;

/**
 * Connection open event
 */
final class OpenEvent extends EventDispatcher\Event
{

	public function __construct(
		private Application\IApplication $application,
		private Entities\IClient $client,
		private Http\IRequest $httpRequest,
	)
	{
	}

	public function getApplication(): Application\IApplication
	{
		return $this->application;
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
