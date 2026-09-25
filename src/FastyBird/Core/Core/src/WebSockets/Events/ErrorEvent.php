<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Events;

use FastyBird\Core\WebSockets\Controllers;
use FastyBird\Core\WebSockets\Entities;
use FastyBird\Core\WebSockets\Handshake;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Connection close event
 */
final class ErrorEvent extends EventDispatcher\Event
{

	private Throwable $exception;

	public function __construct(
		private Controllers\Dispatcher $application,
		private Entities\ConnectedClient $client,
		private Handshake\IRequest $httpRequest,
		Throwable $ex,
	)
	{
		$this->exception = $ex;
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

	public function getException(): Throwable
	{
		return $this->exception;
	}

}
