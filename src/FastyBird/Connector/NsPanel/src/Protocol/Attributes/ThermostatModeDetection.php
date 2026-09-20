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
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Types\Metadata as MetadataTypes;
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
			$capability->getName() !== Types\ThermostatModeDetection::TEMPERATURE->value
			&& $capability->getName() !== Types\ThermostatModeDetection::HUMIDITY->value
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
			Types\Attribute::MODE,
			MetadataTypes\DataType::ENUM,
			$capability,
			$capability->getName() === Types\ThermostatModeDetection::TEMPERATURE->value
				? [
					Types\Payloads\ThermostatDetectionMode::COMFORT->value,
					Types\Payloads\ThermostatDetectionMode::COLD->value,
					Types\Payloads\ThermostatDetectionMode::HOT->value,
				]
				: [
					Types\Payloads\ThermostatDetectionMode::COMFORT->value,
					Types\Payloads\ThermostatDetectionMode::WET->value,
					Types\Payloads\ThermostatDetectionMode::DRY->value,
				],
		);
	}

}
