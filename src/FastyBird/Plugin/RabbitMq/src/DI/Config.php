<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\DI;

/**
 * The fbRabbitMqPlugin configuration, which RabbitMqExtension::getConfigSchema() declares and casts
 * to this class
 */
final readonly class Config
{

	public function __construct(
		public readonly Config\Client $client,
		public readonly Config\Exchange $exchange,
		public readonly Config\Queue $queue,
	)
	{
	}

}
