<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Entities as VieraConnectorHomeKitConnectorEntities;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Values\Types\Sources;
use Override;
use Ramsey\Uuid;
use function assert;

#[ORM\MappedSuperclass]
abstract class Viera extends HomeKitEntities\Channels\Channel
{

	public function __construct(
		VieraConnectorHomeKitConnectorEntities\Devices\Viera $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	#[Override]
	public function getSource(): Sources\Bridge
	{
		return Sources\Bridge::VIERA_CONNECTOR_HOMEKIT_CONNECTOR;
	}

	#[Override]
	public function getDevice(): VieraConnectorHomeKitConnectorEntities\Devices\Viera
	{
		assert($this->device instanceof VieraConnectorHomeKitConnectorEntities\Devices\Viera);

		return $this->device;
	}

}
