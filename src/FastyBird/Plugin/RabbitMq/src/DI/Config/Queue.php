<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\DI\Config;

/**
 * The fbRabbitMqPlugin > queue structure of RabbitMq\DI\Config
 */
final readonly class Queue
{

	public function __construct(public readonly string|null $name)
	{
	}

}
