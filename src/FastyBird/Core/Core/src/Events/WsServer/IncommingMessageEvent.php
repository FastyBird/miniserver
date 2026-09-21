<?php declare(strict_types = 1);

/**
 * IncommingMessageEvent.php
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

namespace FastyBird\Core\Events\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http\WebSockets as Http;
use Symfony\Contracts\EventDispatcher;

/**
 * Incomming message event
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
