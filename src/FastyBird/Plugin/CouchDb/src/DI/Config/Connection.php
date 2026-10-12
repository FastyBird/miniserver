<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\DI\Config;

/**
 * The fbCouchDbPlugin > connection structure of CouchDb\DI\Config
 */
final readonly class Connection
{

	public function __construct(
		public readonly string $database,
		public readonly string $host,
		public readonly int $port,
		public readonly string|null $username,
		public readonly string|null $password,
	)
	{
	}

}
