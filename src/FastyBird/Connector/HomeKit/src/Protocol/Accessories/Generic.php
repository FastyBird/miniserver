<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Protocol\Accessories;

use FastyBird\Connector\HomeKit\Documents;
use FastyBird\Connector\HomeKit\Types;
use Override;
use Ramsey\Uuid;

/**
 * HAP generic device accessory
 */
class Generic extends Accessory
{

	public function __construct(
		string $name,
		int|null $aid,
		Types\AccessoryCategory $category,
		private readonly Documents\Devices\Device $device,
	)
	{
		parent::__construct($name, $aid, $category);
	}

	#[Override]
	public function getId(): Uuid\UuidInterface
	{
		return $this->device->getId();
	}

	public function getDevice(): Documents\Devices\Device
	{
		return $this->device;
	}

}
