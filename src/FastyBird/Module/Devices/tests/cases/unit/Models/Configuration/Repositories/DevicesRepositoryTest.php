<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DevicesRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testFindOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$entity = $repository->find(Uuid\Uuid::fromString('69786d15-fd0c-4d9f-9378-33287c2009fa'));

		self::assertIsObject($entity);
		self::assertInstanceOf(Tests\Fixtures\Dummy\DummyDeviceDocument::class, $entity);
		self::assertSame('first-device', $entity->getIdentifier());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byIdentifier('first-device');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->startWithIdentifier('first-');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->endWithIdentifier('st-device');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byId(Uuid\Uuid::fromString('69786d15-fd0c-4d9f-9378-33287c2009fa'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));
		$findQuery->withoutParents();

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));
		$findQuery->withParents();

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('child-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));
		$findQuery->byType('dummy');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));
		$findQuery->byTypes(['dummy', 'unknown']);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-device', $entity->getIdentifier());
	}

	/**
	 * An identifier is matched as it is, ignoring case, and never as a pattern: what a pattern would make of its
	 * characters must not find a device with another identifier
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadByIdentifierMatchesTheIdentifierLiterally(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		foreach (['first-device', 'FIRST-DEVICE', 'First-Device'] as $identifier) {
			$findQuery = new Queries\Configuration\FindDevices();
			$findQuery->byIdentifier($identifier);

			$entity = $repository->findOneBy($findQuery);

			self::assertIsObject($entity, $identifier);
			self::assertSame('first-device', $entity->getIdentifier());
		}

		foreach (
			[
				'first.device',
				'first-devicee',
				'first-devic.',
				'first-[d]evice',
				'[a-z]+-device',
				'.+',
				'first-device/',
				' first-device',
				"first-device\n",
			] as $identifier
		) {
			$findQuery = new Queries\Configuration\FindDevices();
			$findQuery->byIdentifier($identifier);

			self::assertSame([], $repository->findAllBy($findQuery), $identifier);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAll(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(4, $entities);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllByParent(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Devices\Device::class, $parent);
		self::assertSame('69786d15-fd0c-4d9f-9378-33287c2009fa', $parent->getId()->toString());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->forParent($parent);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllByChild(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byIdentifier('child-device');

		$child = $repository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Devices\Device::class, $child);
		self::assertSame('a1036ff8-6ee8-4405-aaed-58bae0814596', $child->getId()->toString());

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->forChild($child);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllWithChannels(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->withChannels();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

	/**
	 * Every filter matches its argument as it is, ignoring case, and never as a pattern: what a pattern would make of
	 * its characters must not find anything else. A prefix also matches the whole value.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testFiltersMatchTheirArgumentLiterally(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->startWithIdentifier('first-');

		self::assertCount(1, $repository->findAllBy($findQuery), 'startWithIdentifier first-');

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->startWithIdentifier('first-device');

		self::assertCount(1, $repository->findAllBy($findQuery), 'startWithIdentifier first-device');

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->startWithIdentifier('first.');

		self::assertCount(0, $repository->findAllBy($findQuery), 'startWithIdentifier first.');

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->endWithIdentifier('-device');

		self::assertCount(4, $repository->findAllBy($findQuery), 'endWithIdentifier -device');

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->endWithIdentifier('.device');

		self::assertCount(0, $repository->findAllBy($findQuery), 'endWithIdentifier .device');

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byType('dummy');

		self::assertCount(4, $repository->findAllBy($findQuery), 'byType dummy');

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byType('dumm.');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byType dumm.');
	}

}
