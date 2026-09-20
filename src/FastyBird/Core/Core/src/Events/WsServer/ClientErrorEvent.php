<?php declare(strict_types = 1);

/**
 * ClientErrorEvent.php
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
 * Client error event
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class ClientErrorEvent extends EventDispatcher\Event
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
