<?php declare(strict_types = 1);

namespace FastyBird\Core\Encoding\WebSockets;

use FastyBird\Core\Http\WebSockets as Http;

/**
 * HyBi10 webSocket protocol
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Protocols
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class HyBi10 extends RFC6455
{

	public function getVersion(): int
	{
		return 6;
	}

	public function isVersion(Http\IRequest $httpRequest): bool
	{
		$version = (int) (string) $httpRequest->getHeader('Sec-WebSocket-Version');

		return $version >= 6 && $version < 13;
	}

}
