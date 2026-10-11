<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Startup attribute
 */
class Startup extends Attribute
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Attribute::STARTUP,
			ValuesTypes\DataType::ENUM,
			$capability,
			[
				NsPanelTypes\Payloads\Startup::ON->value,
				NsPanelTypes\Payloads\Startup::OFF->value,
				NsPanelTypes\Payloads\Startup::STAY->value,
			],
		);
	}

}
