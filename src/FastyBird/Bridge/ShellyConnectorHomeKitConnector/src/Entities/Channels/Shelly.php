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

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Entities;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Ramsey\Uuid;
use function assert;

#[ORM\MappedSuperclass]
abstract class Shelly extends HomeKitEntities\Channels\Channel
{

	public function __construct(
		Entities\Devices\Shelly $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public function getSource(): MetadataTypes\Sources\Bridge
	{
		return MetadataTypes\Sources\Bridge::SHELLY_CONNECTOR_HOMEKIT_CONNECTOR;
	}

	public function getDevice(): Entities\Devices\Shelly
	{
		assert($this->device instanceof Entities\Devices\Shelly);

		return $this->device;
	}

}
