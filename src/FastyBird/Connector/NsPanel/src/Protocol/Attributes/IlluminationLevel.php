<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Illumination level attribute
 */
class IlluminationLevel extends Attribute
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Attribute::LEVEL,
			ValuesTypes\DataType::ENUM,
			$capability,
			[
				NsPanelTypes\Payloads\IlluminationLevel::BRIGHTER->value,
				NsPanelTypes\Payloads\IlluminationLevel::DARKER->value,
			],
		);
	}

}
