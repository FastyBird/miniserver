<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Connectors;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;

/**
 * Connector property repository interface
 */
interface IRepository
{

	public function find(
		Uuid\UuidInterface $id,
	): States\ConnectorProperty|null;

}
