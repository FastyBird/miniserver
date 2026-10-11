<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\ThermostatTargetSetPoint::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\ThermostatTargetSetPoint::TYPE)]
final class ThermostatTargetSetPoint extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\ThermostatTargetSetPoint::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::THERMOSTAT_TARGET_SET_POINT->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
