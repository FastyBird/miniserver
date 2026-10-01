<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Tests;
use FastyBird\Core\Tests\Fixtures\Dummy;
use FastyBird\Core\WebSockets\Clients;
use FastyBird\Core\WebSockets\Clients\Drivers as ClientsDrivers;
use FastyBird\Core\WebSockets\Exceptions as WebSocketsExceptions;
use FastyBird\Core\WebSockets\Topics;
use FastyBird\Core\WebSockets\Topics\Drivers as TopicsDrivers;
use Nette;

/**
 * fbCore.webSockets.storage.clients.driver and .topics.driver name the service the clients and
 * topics storages use (#565).
 *
 * Each option takes a service reference, "@name" as its default does, or a bare "name". NEON
 * reads an unquoted @name as a reference and a quoted "@name" as a literal string, which nette/di
 * escapes to "@@name"; both are accepted. Every overlay that adds a driver registers it under
 * services:, which nette/di processes after every extension's loadConfiguration(), so the driver
 * does not exist yet when fbCore reads the option.
 */
final class WebSocketsStorageDriversTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws WebSocketsExceptions\Storage
	 * @throws Error
	 */
	public function testClientsStorageUsesADriverConfiguredAsAReference(): void
	{
		$container = $this->createContainer(__DIR__ . '/webSocketsClientsDriverReference.neon');

		$driver = $container->getByType(Dummy\DummyClientsDriver::class);

		$container->getByType(Clients\Storage::class)->hasClient(7);

		self::assertSame([7], $driver->getContainsCalls());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws WebSocketsExceptions\Storage
	 * @throws Error
	 */
	public function testClientsStorageUsesADriverConfiguredAsAQuotedReference(): void
	{
		$container = $this->createContainer(__DIR__ . '/webSocketsClientsDriverQuotedReference.neon');

		$driver = $container->getByType(Dummy\DummyClientsDriver::class);

		$container->getByType(Clients\Storage::class)->hasClient(7);

		self::assertSame([7], $driver->getContainsCalls());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws WebSocketsExceptions\Storage
	 * @throws Error
	 */
	public function testClientsStorageUsesADriverConfiguredAsABareName(): void
	{
		$container = $this->createContainer(__DIR__ . '/webSocketsClientsDriverName.neon');

		$driver = $container->getByType(Dummy\DummyClientsDriver::class);

		$container->getByType(Clients\Storage::class)->hasClient(7);

		self::assertSame([7], $driver->getContainsCalls());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws WebSocketsExceptions\Storage
	 * @throws Error
	 */
	public function testClientsStorageUsesTheMemoryDriverConfiguredExplicitly(): void
	{
		$container = $this->createContainer(__DIR__ . '/webSocketsClientsDriverMemory.neon');

		$driver = $container->getService('fbCore.webSockets.clients.driver.memory');
		self::assertInstanceOf(ClientsDrivers\InMemory::class, $driver);

		$driver->save(7, 'client');

		self::assertTrue($container->getByType(Clients\Storage::class)->hasClient(7));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws WebSocketsExceptions\Storage
	 * @throws Error
	 */
	public function testTopicsStorageUsesADriverConfiguredAsAReference(): void
	{
		$container = $this->createContainer(__DIR__ . '/webSocketsTopicsDriverReference.neon');

		$driver = $container->getByType(Dummy\DummyTopicsDriver::class);

		$container->getByType(Topics\Storage::class)->hasTopic('topic');

		self::assertSame(['topic'], $driver->getContainsCalls());

		// The in-memory topics driver is registered only when the option names it
		self::assertFalse($container->hasService('fbCore.webSockets.wamp.topics.driver.memory'));
		self::assertSame([], $container->findByType(TopicsDrivers\InMemory::class));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws WebSocketsExceptions\Storage
	 * @throws Error
	 */
	public function testTopicsStorageUsesADriverConfiguredAsAQuotedReference(): void
	{
		$container = $this->createContainer(__DIR__ . '/webSocketsTopicsDriverQuotedReference.neon');

		$driver = $container->getByType(Dummy\DummyTopicsDriver::class);

		$container->getByType(Topics\Storage::class)->hasTopic('topic');

		self::assertSame(['topic'], $driver->getContainsCalls());
		self::assertFalse($container->hasService('fbCore.webSockets.wamp.topics.driver.memory'));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws WebSocketsExceptions\Storage
	 * @throws Error
	 */
	public function testTopicsStorageUsesTheMemoryDriverConfiguredExplicitly(): void
	{
		$container = $this->createContainer(__DIR__ . '/webSocketsTopicsDriverMemory.neon');

		$driver = $container->getService('fbCore.webSockets.wamp.topics.driver.memory');
		self::assertInstanceOf(TopicsDrivers\InMemory::class, $driver);

		$driver->save('topic', 'topic');

		self::assertTrue($container->getByType(Topics\Storage::class)->hasTopic('topic'));
	}

}
