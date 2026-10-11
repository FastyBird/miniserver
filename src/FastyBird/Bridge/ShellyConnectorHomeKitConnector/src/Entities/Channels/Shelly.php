<?php declare(strict_types = 1);

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities as ShellyConnectorHomeKitConnectorEntities;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;
use function assert;

#[ORM\MappedSuperclass]
abstract class Shelly extends HomeKitEntities\Channels\Channel
{

	public function __construct(
		ShellyConnectorHomeKitConnectorEntities\Devices\Shelly $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public function getSource(): Sources\Bridge
	{
		return Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR;
	}

	public function getDevice(): ShellyConnectorHomeKitConnectorEntities\Devices\Shelly
	{
		assert($this->device instanceof ShellyConnectorHomeKitConnectorEntities\Devices\Shelly);

		return $this->device;
	}

}
