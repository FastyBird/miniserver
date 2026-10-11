<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Services;

use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Types;
use Ramsey\Uuid;

/**
 * HAP accessory service factory interface
 */
interface ServiceFactory
{

	/**
	 * @param array<string> $requiredCharacteristics
	 * @param array<string> $optionalCharacteristics
	 * @param array<string> $virtualCharacteristics
	 */
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
	): Service;

	/**
	 * @return class-string<Entities\Channels\Channel>
	 */
	public function getEntityClass(): string;

}
