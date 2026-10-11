<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Devices;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;

/**
 * Device property repository interface
 */
interface Reader
{

	public function find(Uuid\UuidInterface $id): States\DeviceProperty|null;

}
