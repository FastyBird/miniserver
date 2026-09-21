<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Entities\Connectors;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Connector extends DevicesEntities\Connectors\Connector
{

	public const TYPE = 'virtual-connector';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::VIRTUAL;
	}

	/**
	 * @return array<Entities\Devices\Device>
	 */
	public function getDevices(): array
	{
		$devices = [];

		foreach (parent::getDevices() as $device) {
			if ($device instanceof Entities\Devices\Device) {
				$devices[] = $device;
			}
		}

		return $devices;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function addDevice(DevicesEntities\Devices\Device $device): void
	{
		if (!$device instanceof Entities\Devices\Device) {
			throw new Exceptions\InvalidArgument('Provided device type is not valid');
		}

		parent::addDevice($device);
	}

}
