<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;
use function assert;
use function is_string;

/**
 * NS panel thermostat supported modes capability configuration factory
 */
class ThermostatSupportedModesFactory implements ConfigurationFactory
{

	public function create(
		Uuid\UuidInterface $id,
		NsPanelTypes\Configuration $type,
		ValuesTypes\DataType $dataType,
		Protocol\Capabilities\Capability $capability,
		float|int|bool|string|array|null $value,
		array|null $validValues = [],
		int|null $maxLength = null,
		float|null $minValue = null,
		float|null $maxValue = null,
		float|null $minStep = null,
		string|null $unit = null,
	): ThermostatSupportedModes
	{
		assert(is_string($value));

		return new ThermostatSupportedModes($id, $capability, $value);
	}

	public function getType(): NsPanelTypes\Configuration
	{
		return NsPanelTypes\Configuration::SUPPORTED_MODES;
	}

}
