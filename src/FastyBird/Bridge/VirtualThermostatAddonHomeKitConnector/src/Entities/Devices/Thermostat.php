<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use function sprintf;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Thermostat extends HomeKitEntities\Devices\Device
{

	public const string TYPE = 'virtual-thermostat-addon-homekit-connector-bridge';

	/**
	 * @param array<DevicesEntities\Devices\Device> $parents
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $identifier,
		HomeKitEntities\Connectors\Connector $connector,
		array $parents,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);

		$validParent = false;

		foreach ($parents as $parent) {
			if ($parent instanceof VirtualThermostatEntities\Devices\Device) {
				$validParent = true;
			}
		}

		if (!$validParent) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'At least one parent have to be instance of: %s',
					VirtualThermostatEntities\Devices\Device::class,
				),
			);
		}

		$this->setParents($parents);
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

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): VirtualThermostatEntities\Devices\Device
	{
		foreach ($this->parents->toArray() as $parent) {
			if ($parent instanceof VirtualThermostatEntities\Devices\Device) {
				return $parent;
			}
		}

		throw new Exceptions\InvalidState(
			'Bridged thermostat device have to have parent virtual thermostat device defined',
		);
	}

}
