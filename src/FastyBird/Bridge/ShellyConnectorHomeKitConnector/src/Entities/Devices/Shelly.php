<?php declare(strict_types = 1);

/**
 * Shelly.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Exceptions;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Core\Entities\Application\Mapping as ApplicationMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Nette\Utils;
use Ramsey\Uuid;
use function sprintf;

#[ORM\Entity]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class Shelly extends HomeKitEntities\Devices\Device
{

	public const TYPE = 'shelly-connector-homekit-connector-bridge';

	/**
	 * @param array<DevicesEntities\Devices\Device>|Utils\ArrayHash<DevicesEntities\Devices\Device> $parents
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $identifier,
		HomeKitEntities\Connectors\Connector $connector,
		array|Utils\ArrayHash $parents,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);

		$validParent = false;

		foreach ($parents as $parent) {
			if ($parent instanceof ShellyEntities\Devices\Device) {
				$validParent = true;
			}
		}

		if (!$validParent) {
			throw new Exceptions\InvalidArgument(
				sprintf(
					'At least one parent have to be instance of: %s',
					ShellyEntities\Devices\Device::class,
				),
			);
		}

		$this->setParents($parents);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Bridge
	{
		return Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): ShellyEntities\Devices\Device
	{
		foreach ($this->parents->toArray() as $parent) {
			if ($parent instanceof ShellyEntities\Devices\Device) {
				return $parent;
			}
		}

		throw new Exceptions\InvalidState(
			'Bridged shelly device have to have parent shelly device defined',
		);
	}

}
