<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Encoding;

use FastyBird\Core\WebSockets\Handshake;
use Override;

/**
 * HyBi10 webSocket protocol
 */
final class HyBi10 extends RFC6455
{

	#[Override]
	public function getVersion(): int
	{
		return 6;
	}

	#[Override]
	public function isVersion(Handshake\IRequest $httpRequest): bool
	{
		$version = (int) (string) $httpRequest->getHeader('Sec-WebSocket-Version');

		return $version >= 6 && $version < 13;
	}

}
