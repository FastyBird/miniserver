<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use Ramsey\Uuid;
use React\Promise;

class DummyConnector implements DevicesConnectors\Connector
{

	public function getId(): Uuid\UuidInterface
	{
		return Uuid\Uuid::fromString('bda37bc7-9bd7-4083–a925-386ac5522325');
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
