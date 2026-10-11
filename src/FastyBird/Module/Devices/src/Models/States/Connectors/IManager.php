<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Connectors;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Connector properties manager interface
 */
interface IManager
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ConnectorProperty;

	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\ConnectorProperty|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
