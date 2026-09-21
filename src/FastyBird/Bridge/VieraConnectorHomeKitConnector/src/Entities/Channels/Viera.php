<?php declare(strict_types = 1);

/**
 * Viera.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Ramsey\Uuid;
use function assert;

#[ORM\MappedSuperclass]
abstract class Viera extends HomeKitEntities\Channels\Channel
{

	public function __construct(
		Entities\Devices\Viera $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public function getSource(): MetadataTypes\Sources\Bridge
	{
		return MetadataTypes\Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR;
	}

	public function getDevice(): Entities\Devices\Viera
	{
		assert($this->device instanceof Entities\Devices\Viera);

		return $this->device;
	}

}
