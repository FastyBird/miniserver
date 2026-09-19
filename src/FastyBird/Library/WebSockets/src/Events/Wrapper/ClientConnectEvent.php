<?php declare(strict_types = 1);

/**
 * ClientConnectEvent.php
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

namespace FastyBird\Library\WebSockets\Events\Wrapper;

use FastyBird\Library\WebSockets\Entities;
use FastyBird\Library\WebSockets\Http;
use Symfony\Contracts\EventDispatcher;

/**
 * Client connected event
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class ClientConnectEvent extends EventDispatcher\Event
{

	public function __construct(
		private Entities\Clients\IClient $client,
		private Http\IRequest $httpRequest,
	)
	{
	}

	public function getClient(): Entities\Clients\IClient
	{
		return $this->client;
	}

	public function getHttpRequest(): Http\IRequest
	{
		return $this->httpRequest;
	}

}
