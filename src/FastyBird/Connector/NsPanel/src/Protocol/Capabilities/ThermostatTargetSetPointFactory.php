<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Capabilities;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Protocol;
use Override;
use function array_key_exists;
use function array_map;
use function assert;
use function preg_match;

/**
 * NS panel thermostat target set point capability factory
 */
final class ThermostatTargetSetPointFactory implements CapabilityFactory
{

	#[Override]
	public function create(
		Documents\Channels\Channel $channel,
		Protocol\Devices\Device $device,
		Mapping\Capabilities\Capability $capabilityMetadata,
	): ThermostatTargetSetPoint
	{
		assert($channel instanceof Documents\Channels\ThermostatTargetSetPoint);

		preg_match(Entities\Channels\Channel::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches);

		return new ThermostatTargetSetPoint(
			$channel->getId(),
			$capabilityMetadata->getPermission(),
			$device,
			array_key_exists('name', $matches) ? $matches['name'] : null,
			array_map(
				static fn (Mapping\Configurations\Configuration $configuration) => $configuration->getConfiguration(),
				$capabilityMetadata->getConfigurations(),
			),
			[],
			array_map(
				static fn (Mapping\Attributes\Attribute $attribute) => $attribute->getAttribute(),
				$capabilityMetadata->getAttributes(),
			),
			[],
		);
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\ThermostatTargetSetPoint::class;
	}

}
