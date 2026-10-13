<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Exceptions;
use Ramsey\Uuid;
use React\Promise;

class DummyConnector implements Connectors\Connector
{

	public function getId(): Uuid\UuidInterface
	{
		return Uuid\Uuid::fromString('7a3dd94c-7294-46fd-8c61-1b375c313d4d');
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function execute(): Promise\PromiseInterface
	{
		return Promise\reject(new Exceptions\InvalidState('Not implemented'));
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function discover(): Promise\PromiseInterface
	{
		return Promise\reject(new Exceptions\InvalidState('Not implemented'));
	}

	public function terminate(): void
	{
		// NOT IMPLEMENTED
	}

	public function hasUnfinishedTasks(): bool
	{
		return false;
	}

}
