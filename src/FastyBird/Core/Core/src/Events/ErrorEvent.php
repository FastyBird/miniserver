<?php declare(strict_types = 1);

/**
 * ErrorEvent.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.ipublikuj.eu
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @package        iPublikuj:WebSockets!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.11.19
 */

namespace FastyBird\Core\Events;

use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http\WebSockets as Http;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Connection close event
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class ErrorEvent extends EventDispatcher\Event
{

	private Throwable $exception;

	public function __construct(
		private Application\IApplication $application,
		private Entities\IClient $client,
		private Http\IRequest $httpRequest,
		Throwable $ex,
	)
	{
		$this->exception = $ex;
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

	public function getException(): Throwable
	{
		return $this->exception;
	}

}
