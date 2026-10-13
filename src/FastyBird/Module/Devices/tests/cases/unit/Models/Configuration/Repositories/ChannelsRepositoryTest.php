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
final class ChannelsRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

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
		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('channel-one');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->startWithIdentifier('channel-o');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->endWithIdentifier('two');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-two', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byId(Uuid\Uuid::fromString('6821f8e9-ae69-4d5c-9b7c-d2b213f1ae0a'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-two', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byDeviceId(Uuid\Uuid::fromString('bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byDeviceId(Uuid\Uuid::fromString('bf4cd870-2aac-45f0-a85e-e1cefd2d6d9a'));
		$findQuery->byTypes(['dummy', 'unknown']);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(3, $entities);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllByDevice(): void
	{
		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byIdentifier('first-device');

		$device = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Devices\Device::class, $device);
		self::assertSame('69786d15-fd0c-4d9f-9378-33287c2009fa', $device->getId()->toString());

		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->forDevice($device);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAllWithProperties(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->withProperties();

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
		$repository = $this->getContainer()->getByType(Models\Configuration\Channels\Repository::class);

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('channel-one');

		self::assertCount(2, $repository->findAllBy($findQuery), 'byIdentifier channel-one');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('CHANNEL-ONE');

		self::assertCount(2, $repository->findAllBy($findQuery), 'byIdentifier CHANNEL-ONE');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('channel.one');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byIdentifier channel.one');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('channel-onee');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byIdentifier channel-onee');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byIdentifier('[c]hannel-one');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byIdentifier [c]hannel-one');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->startWithIdentifier('channel-');

		self::assertCount(3, $repository->findAllBy($findQuery), 'startWithIdentifier channel-');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->startWithIdentifier('CHANNEL-ONE');

		self::assertCount(2, $repository->findAllBy($findQuery), 'startWithIdentifier CHANNEL-ONE');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->startWithIdentifier('channel.');

		self::assertCount(0, $repository->findAllBy($findQuery), 'startWithIdentifier channel.');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->endWithIdentifier('-two');

		self::assertCount(1, $repository->findAllBy($findQuery), 'endWithIdentifier -two');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->endWithIdentifier('.two');

		self::assertCount(0, $repository->findAllBy($findQuery), 'endWithIdentifier .two');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byType('dummy');

		self::assertCount(3, $repository->findAllBy($findQuery), 'byType dummy');

		$findQuery = new Queries\Configuration\FindChannels();
		$findQuery->byType('dumm.');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byType dumm.');
	}

}
