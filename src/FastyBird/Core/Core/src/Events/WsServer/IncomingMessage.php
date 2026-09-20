<?php declare(strict_types = 1);

/**
 * IncomingMessage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.01.22
 */

namespace FastyBird\Core\Events\WsServer;

use FastyBird\Core\Entities\WsServer as Entities;
use FastyBird\Core\Http\WebSockets as Http;

/**
 * WS client sent message event
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class IncomingMessage
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
