<?php declare(strict_types = 1);

/**
 * MappingMode.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           05.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Configurations;

use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Values\Types as ValuesTypes;
use Ramsey\Uuid;

/**
 * Thermostat mapping mode configuration
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class MappingMode extends Configuration
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Protocol\Capabilities\Capability $capability,
		string $value,
	)
	{
		if (NsPanelTypes\Payloads\ThermostatMode::tryFrom($value) === null) {
			throw new Exceptions\InvalidState('Configuration mapping mode value is not valid.');
		}

		parent::__construct(
			$id,
			NsPanelTypes\Configuration::MAPPING_MODE,
			ValuesTypes\DataType::STRING,
			$capability,
			$value,
		);
	}

}
