<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Channels\Async;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous channel properties manager interface
 */
interface Writer
{

	/**
	 * @param array<string, mixed> $values
	 *
	 * @return Promise\PromiseInterface<States\ChannelProperty>
	 */
	public function create(
		Uuid\UuidInterface $id,
		array $values,
	): Promise\PromiseInterface;

	/**
	 * @param array<string, mixed> $values
	 *
	 * @return Promise\PromiseInterface<States\ChannelProperty|false>
	 */
	public function update(
		Uuid\UuidInterface $id,
		array $values,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
