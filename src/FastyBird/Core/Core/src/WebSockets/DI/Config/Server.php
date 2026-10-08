<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI\Config;

/**
 * The fbCore > webSockets > server structure of WebSockets\DI\Config
 */
final readonly class Server
{

	public function __construct(
		public readonly string $httpHost,
		public readonly int $port,
		public readonly string $address,
		public readonly ServerSecured $secured,
	)
	{
	}

}
