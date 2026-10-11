<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Camera stream url configuration
 */
class StreamUrl extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		string $value,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Configuration::STREAM_URL,
			ValuesTypes\DataType::STRING,
			$capability,
			$value,
		);
	}

}
