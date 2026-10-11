<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Connectors\Async;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous connector property repository interface
 */
interface IRepository
{

	/**
	 * @return Promise\PromiseInterface<States\ConnectorProperty|null>
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
