<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Server;

/**
 * WebSockets server configuration container
 */
final class Configuration
{

	public function __construct(
		public private(set) int $port = 8_080,
		public private(set) string $address = '0.0.0.0',
		private bool $enableSSL = false,
		private array $sslSettings = [],
	)
	{
	}

	public function isSslEnabled(): bool
	{
		return $this->enableSSL;
	}

	public function getSslConfiguration(): array
	{
		return $this->sslSettings;
	}

}
