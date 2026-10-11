<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Devices\Async;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous device property repository interface
 */
interface IRepository
{

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|null>
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
