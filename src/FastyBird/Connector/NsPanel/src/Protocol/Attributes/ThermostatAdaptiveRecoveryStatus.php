<?php declare(strict_types = 1);

/**
 * ThermostatAdaptiveRecoveryStatus.php
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

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Ramsey\Uuid;

/**
 * Thermostat adaptive recovery status attribute
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
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
			Types\Attribute::ADAPTIVE_RECOVERY_STATUS,
			MetadataTypes\DataType::ENUM,
			$capability,
			[
				Types\Payloads\ThermostatAdaptiveRecoveryStatus::HEATING->value,
				Types\Payloads\ThermostatAdaptiveRecoveryStatus::INACTIVE->value,
			],
		);
	}

}
