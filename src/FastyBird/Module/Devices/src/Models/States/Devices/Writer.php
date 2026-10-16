<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Devices;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;

/**
 * Device properties manager interface
 */
interface Writer
{

	/**
	 * @param array<string, mixed> $values
	 */
	public function create(
		Uuid\UuidInterface $id,
		array $values,
	): States\DeviceProperty;

	/**
	 * @param array<string, mixed> $values
	 */
	public function update(
		Uuid\UuidInterface $id,
		array $values,
	): States\DeviceProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
