<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\DI\Config;

/**
 * The fbRabbitMqPlugin > client structure of RabbitMq\DI\Config
 */
final readonly class Client
{

	public function __construct(
		public readonly string $host,
		public readonly int $port,
		public readonly string $vhost,
		public readonly string $username,
		public readonly string $password,
	)
	{
	}

}
