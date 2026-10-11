<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Motor reverse attribute
 */
final class MotorReverse extends Attribute
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Attribute::MOTOR_REVERSE,
			ValuesTypes\DataType::BOOLEAN,
			$capability,
			[],
			null,
			null,
			null,
			null,
			false,
		);
	}

}
