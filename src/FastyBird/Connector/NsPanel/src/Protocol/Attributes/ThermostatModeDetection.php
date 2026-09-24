<?php declare(strict_types = 1);

/**
 * ThermostatModeDetection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           04.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Attributes;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;
use function sprintf;

/**
 * Thermostat mode detection attribute
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ThermostatModeDetection extends Attribute
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
	)
	{
		if (
			$capability->getName() !== NsPanelTypes\ThermostatModeDetection::TEMPERATURE->value
			&& $capability->getName() !== NsPanelTypes\ThermostatModeDetection::HUMIDITY->value
		) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'Provided capability name: %s for thermostat mode detection is not valid',
					$capability->getName(),
				),
			);
		}

		parent::__construct(
			$id,
			NsPanelTypes\Attribute::MODE,
			ValuesTypes\DataType::ENUM,
			$capability,
			$capability->getName() === NsPanelTypes\ThermostatModeDetection::TEMPERATURE->value
				? [
					NsPanelTypes\Payloads\ThermostatDetectionMode::COMFORT->value,
					NsPanelTypes\Payloads\ThermostatDetectionMode::COLD->value,
					NsPanelTypes\Payloads\ThermostatDetectionMode::HOT->value,
				]
				: [
					NsPanelTypes\Payloads\ThermostatDetectionMode::COMFORT->value,
					NsPanelTypes\Payloads\ThermostatDetectionMode::WET->value,
					NsPanelTypes\Payloads\ThermostatDetectionMode::DRY->value,
				],
		);
	}

}
