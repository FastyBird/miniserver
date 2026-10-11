<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;

#[Documents\Mapping\Document(entity: Entities\Channels\ThermostatTargetSetPoint::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\ThermostatTargetSetPoint::TYPE)]
final class ThermostatTargetSetPoint extends Channel
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
