<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Devices\Async;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous device properties manager interface
 */
interface IManager
{

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty>
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|false>
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
