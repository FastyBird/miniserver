<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels\Async;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous channel property repository interface
 */
interface IRepository
{

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|null>
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
