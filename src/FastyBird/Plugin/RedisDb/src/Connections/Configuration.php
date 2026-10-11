<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Connections;

use Nette;

/**
 * Redis connection configuration
 */
final readonly class Configuration
{

	use Nette\SmartObject;

	public function __construct(
		private string $host = '127.0.0.1',
		private int $port = 6_379,
		private string|null $username = null,
		private string|null $password = null,
	)
	{
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getUsername(): string|null
	{
		return $this->username;
	}

	public function getPassword(): string|null
	{
		return $this->password;
	}

}
