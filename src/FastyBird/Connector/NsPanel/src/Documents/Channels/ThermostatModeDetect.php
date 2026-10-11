<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Documents\Channels;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Documents;
use Override;

#[Documents\Mapping\Document(entity: Entities\Channels\ThermostatModeDetect::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Channels\ThermostatModeDetect::TYPE)]
final class ThermostatModeDetect extends Channel
{

	#[Override]
	public static function getType(): string
	{
		return Entities\Channels\ThermostatModeDetect::TYPE;
	}

	#[Override]
	public function toDefinition(): array
	{
		return [
			'capability' => Types\Capability::THERMOSTAT_MODE_DETECT->value,
			'permission' => Types\Permission::READ_WRITE->value,
		];
	}

}
