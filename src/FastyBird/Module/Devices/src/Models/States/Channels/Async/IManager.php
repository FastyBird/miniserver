<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels\Async;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous channel properties manager interface
 */
interface IManager
{

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty>
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|false>
	 */
	public function update(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
