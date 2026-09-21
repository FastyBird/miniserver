<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Thermostat extends HomeKitEntities\Channels\Channel
{

	public const TYPE = 'virtual-thermostat-addon-homekit-connector-bridge';

	public function __construct(
		Entities\Devices\Thermostat $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Bridge
	{
		return MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR;
	}

	public function getDevice(): Entities\Devices\Thermostat
	{
		assert($this->device instanceof Entities\Devices\Thermostat);

		return $this->device;
	}

}
