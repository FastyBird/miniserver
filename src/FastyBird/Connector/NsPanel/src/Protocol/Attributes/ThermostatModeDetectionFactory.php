<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Override;
use Ramsey\Uuid;

/**
 * NS panel thermostat mode detection attribute factory
 */
class ThermostatModeDetectionFactory implements AttributeFactory
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
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
	): ThermostatModeDetection
	{
		return new ThermostatModeDetection($id, $capability);
	}

	#[Override]
	public function getType(): NsPanelTypes\Attribute
	{
		return NsPanelTypes\Attribute::MODE;
	}

}
