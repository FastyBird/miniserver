<?php declare(strict_types = 1);

/**
 * TelevisionFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           26.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Documents as VieraConnectorHomeKitConnectorDocuments;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Ramsey\Uuid;
use function assert;

/**
 * Viera television service factory
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TelevisionFactory implements HomeKitProtocol\Services\ServiceFactory
{

	/**
	 * @param array<string> $requiredCharacteristics
	 * @param array<string> $optionalCharacteristics
	 * @param array<string> $virtualCharacteristics
	 */
	public function create(
		Uuid\UuidInterface $typeId,
		HomeKitTypes\ServiceType $type,
		HomeKitProtocol\Accessories\Accessory $accessory,
		HomeKitDocuments\Channels\Channel|null $channel = null,
		array $requiredCharacteristics = [],
		array $optionalCharacteristics = [],
		array $virtualCharacteristics = [],
		bool $primary = false,
		bool $hidden = false,
	): Television
	{
		assert($channel instanceof VieraConnectorHomeKitConnectorDocuments\Channels\Viera);

		return new Television(
			$typeId,
			$type,
			$accessory,
			$channel,
			$requiredCharacteristics,
			$optionalCharacteristics,
			$virtualCharacteristics,
			true,
			false,
		);
	}

	public function getEntityClass(): string
	{
		return Entities\Channels\Television::class;
	}

}
