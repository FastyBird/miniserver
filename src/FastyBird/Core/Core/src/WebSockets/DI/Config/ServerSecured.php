<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI\Config;

/**
 * The fbCore > webSockets > server > secured structure of WebSockets\DI\Config
 */
final readonly class ServerSecured
{

	/**
	 * @param array<mixed> $sslSettings
	 */
	public function __construct(
		public readonly bool $enable,
		public readonly array $sslSettings,
	)
	{
	}

}
