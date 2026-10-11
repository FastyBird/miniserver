<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Devices;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Device properties manager interface
 */
interface IManager
{

	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): States\DeviceProperty;

	public function update(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): States\DeviceProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
