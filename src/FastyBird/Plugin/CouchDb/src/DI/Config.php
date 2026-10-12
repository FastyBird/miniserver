<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\DI;

/**
 * The fbCouchDbPlugin configuration, which CouchDbExtension::getConfigSchema() declares and casts
 * to this class
 */
final readonly class Config
{

	public function __construct(public readonly Config\Connection $connection)
	{
	}

}
