<?php declare(strict_types = 1);

/**
 * ThermostatTargetSetPoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           16.10.24
 */

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents\Application as ApplicationDocuments;

#[ApplicationDocuments\Mapping\Document(entity: Entities\Channels\ThermostatTargetSetPoint::class)]
#[ApplicationDocuments\Mapping\DiscriminatorEntry(name: Entities\Channels\ThermostatTargetSetPoint::TYPE)]
class ThermostatTargetSetPoint extends Channel
{

	public static function getType(): string
	{
		return Entities\Channels\ThermostatTargetSetPoint::TYPE;
	}

	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::THERMOSTAT_TARGET_SET_POINT->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
