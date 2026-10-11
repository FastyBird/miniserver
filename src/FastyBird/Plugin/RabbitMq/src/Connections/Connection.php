<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Connections;

use Nette;

/**
 * RabbitMQ connection configuration
 */
final class Connection
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $host = '127.0.0.1',
		private readonly int $port = 5_672,
		private readonly string $vhost = '/',
		private readonly string $username = 'guest',
		private readonly string $password = 'guest',
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

	public function getVhost(): string
	{
		return $this->vhost;
	}

	public function getUsername(): string
	{
		return $this->username;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

}
