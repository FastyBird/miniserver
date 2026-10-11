<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Thermostat minimal temperature configuration
 */
final class TemperatureMin extends Configuration
{

	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		float|int $value,
		float $minValue,
		float $maxValue,
	)
	{
		parent::__construct(
			$id,
			NsPanelTypes\Configuration::TEMPERATURE_MIN,
			ValuesTypes\DataType::FLOAT,
			$capability,
			$value,
			[],
			null,
			$minValue,
			$maxValue,
			0.1,
		);
	}

}
