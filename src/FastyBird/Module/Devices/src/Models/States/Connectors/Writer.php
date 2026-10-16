<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Connectors;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;

/**
 * Connector properties manager interface
 */
interface Writer
{

	/**
	 * @param array<string, mixed> $values
	 */
	public function create(Uuid\UuidInterface $id, array $values): States\ConnectorProperty;

	/**
	 * @param array<string, mixed> $values
	 */
	public function update(Uuid\UuidInterface $id, array $values): States\ConnectorProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
