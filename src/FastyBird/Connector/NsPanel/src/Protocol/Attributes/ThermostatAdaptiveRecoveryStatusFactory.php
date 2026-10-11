<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * NS panel thermostat adaptive recovery status attribute factory
 */
class ThermostatAdaptiveRecoveryStatusFactory implements AttributeFactory
{

	public function create(
		Uuid\UuidInterface $id,
		NsPanelTypes\Attribute $type,
		ValuesTypes\DataType $dataType,
		Protocol\Capabilities\Capability $capability,
		array|null $validValues = [],
		int|null $maxLength = null,
		float|null $minValue = null,
		float|null $maxValue = null,
		float|null $minStep = null,
		float|int|bool|string|null $defaultValue = null,
		string|null $unit = null,
	): ThermostatAdaptiveRecoveryStatus
	{
		return new ThermostatAdaptiveRecoveryStatus($id, $capability);
	}

	public function getType(): NsPanelTypes\Attribute
	{
		return NsPanelTypes\Attribute::ADAPTIVE_RECOVERY_STATUS;
	}

}
