<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Services;

use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Types;
use Override;
use Ramsey\Uuid;

/**
 * HAP battery service factory
 */
class BatteryFactory implements ServiceFactory
{

	/**
	 * @param array<string> $requiredCharacteristics
	 * @param array<string> $optionalCharacteristics
	 * @param array<string> $virtualCharacteristics
	 */
	#[Override]
	public function create(
		Uuid\UuidInterface $typeId,
		Types\ServiceType $type,
		Protocol\Accessories\Accessory $accessory,
		Documents\Channels\Channel|null $channel = null,
		array $requiredCharacteristics = [],
		array $optionalCharacteristics = [],
		array $virtualCharacteristics = [],
		bool $primary = false,
		bool $hidden = false,
	): Battery
	{
		return new Battery(
			$typeId,
			$type,
			$accessory,
			$channel,
			$requiredCharacteristics,
			$optionalCharacteristics,
			$virtualCharacteristics,
			$primary,
			$hidden,
		);
	}

	#[Override]
	public function getEntityClass(): string
	{
		return Entities\Channels\Battery::class;
	}

}
