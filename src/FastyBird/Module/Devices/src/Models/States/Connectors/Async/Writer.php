<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Models\States\Connectors\Async;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous connector properties manager interface
 *
 * The values to store come as a plain array or as an ArrayHash (#723). An implementation stores an
 * array exactly as the ArrayHash Utils\ArrayHash::from() makes of it.
 */
interface Writer
{

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return Promise\PromiseInterface<States\ConnectorProperty>
	 */
	public function create(
		Uuid\UuidInterface $id,
		array|Utils\ArrayHash $values,
	): Promise\PromiseInterface;

	/**
	 * @param array<string, mixed>|Utils\ArrayHash<mixed> $values
	 *
	 * @return Promise\PromiseInterface<States\ConnectorProperty|false>
	 */
	public function update(
		Uuid\UuidInterface $id,
		array|Utils\ArrayHash $values,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
