<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\DI\Config;

/**
 * The fbRabbitMqPlugin > exchange structure of RabbitMq\DI\Config
 */
final readonly class Exchange
{

	public function __construct(public readonly string $name)
	{
	}

}
