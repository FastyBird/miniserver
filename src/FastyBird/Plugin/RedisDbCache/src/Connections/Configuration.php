<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDbCache\Connections;

use Nette;

/**
 * Redis connection configuration
 */
final class Configuration
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $host = '127.0.0.1',
		private readonly int $port = 6_379,
		private readonly string|null $username = null,
		private readonly string|null $password = null,
		private readonly int $database = 0,
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

	public function getDatabase(): int
	{
		return $this->database;
	}

}
