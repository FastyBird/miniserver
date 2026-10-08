<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\DI\Config;

/**
 * The fbCore > webSockets > storage structure of WebSockets\DI\Config
 */
final readonly class Storage
{

	public function __construct(
		public readonly StorageClients $clients,
		public readonly StorageTopics $topics,
	)
	{
	}

}
