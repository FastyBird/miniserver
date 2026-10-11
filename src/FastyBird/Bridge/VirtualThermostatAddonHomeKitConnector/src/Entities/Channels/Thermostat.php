<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities as VirtualThermostatAddonHomeKitConnectorEntities;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use Override;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Thermostat extends HomeKitEntities\Channels\Channel
{

	public const string TYPE = 'virtual-thermostat-addon-homekit-connector-bridge';

	public function __construct(
		VirtualThermostatAddonHomeKitConnectorEntities\Devices\Thermostat $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	#[Override]
	public function getSource(): Sources\Bridge
	{
		return Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR;
	}

	#[Override]
	public function getDevice(): VirtualThermostatAddonHomeKitConnectorEntities\Devices\Thermostat
	{
		assert($this->device instanceof VirtualThermostatAddonHomeKitConnectorEntities\Devices\Thermostat);

		return $this->device;
	}

}
