<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Thermostat adaptive recovery status attribute
 */
class ThermostatAdaptiveRecoveryStatus extends Attribute
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Attribute::ADAPTIVE_RECOVERY_STATUS,
			ValuesTypes\DataType::ENUM,
			$capability,
			[
				NsPanelTypes\Payloads\ThermostatAdaptiveRecoveryStatus::HEATING->value,
				NsPanelTypes\Payloads\ThermostatAdaptiveRecoveryStatus::INACTIVE->value,
			],
		);
	}

}
