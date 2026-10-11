<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Thermostat maximal temperature configuration
 */
class TemperatureScale extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		string $value,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Configuration::TEMPERATURE_SCALE,
			ValuesTypes\DataType::ENUM,
			$capability,
			$value,
			[
				NsPanelTypes\Payloads\TemperatureScale::CELSIUS->value,
				NsPanelTypes\Payloads\TemperatureScale::FAHRENHEIT->value,
			],
		);
	}

}
