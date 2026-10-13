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
final class DevicesPropertiesRepositoryTest extends Tests\Cases\Unit\DbTestCase
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('uptime');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->startWithIdentifier('up');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->endWithIdentifier('ime');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byId(Uuid\Uuid::fromString('bbcccf8c-33ab-431b-a795-d7bb38b6b6db'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('uptime');
		$findQuery->byDeviceId(Uuid\Uuid::fromString('69786d15-fd0c4-d9f9-3783-3287c2009fa'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('uptime', $entity->getIdentifier());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(15, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Devices\Properties\Dynamic::class);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Devices\Properties\Variable::class);

		self::assertCount(13, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Devices\Properties\Mapped::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindDeviceDynamicProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Devices\Properties\Dynamic::class);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindDeviceVariableProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Devices\Properties\Variable::class);

		self::assertCount(13, $entities);

		$findQuery = new Queries\Configuration\FindDeviceMappedProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Devices\Properties\Mapped::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->settable(true);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->settable(false);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->queryable(true);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->queryable(false);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(0, $entities);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testReadAllByDevice(): void
	{
		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Devices\Repository::class);

		$findQuery = new Queries\Configuration\FindDevices();
		$findQuery->byId(Uuid\Uuid::fromString('69786d15-fd0c4-d9f9-3783-3287c2009fa'));

		$device = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Devices\Device::class, $device);
		self::assertSame('first-device', $device->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->forDevice($device);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(10, $entities);
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byIdentifier status_led');

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('STATUS_LED');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byIdentifier STATUS_LED');

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->byIdentifier('status.led');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byIdentifier status.led');

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->startWithIdentifier('hardware_');

		self::assertCount(8, $repository->findAllBy($findQuery), 'startWithIdentifier hardware_');

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->startWithIdentifier('hardware.');

		self::assertCount(0, $repository->findAllBy($findQuery), 'startWithIdentifier hardware.');

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->endWithIdentifier('_manufacturer');

		self::assertCount(4, $repository->findAllBy($findQuery), 'endWithIdentifier _manufacturer');

		$findQuery = new Queries\Configuration\FindDeviceProperties();
		$findQuery->endWithIdentifier('.manufacturer');

		self::assertCount(0, $repository->findAllBy($findQuery), 'endWithIdentifier .manufacturer');

		$findQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findQuery->byValue('device-username');

		self::assertCount(
			1,
			$repository->findAllBy($findQuery, Documents\Devices\Properties\Variable::class),
			'byValue device-username',
		);

		$findQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findQuery->byValue('device.username');

		self::assertCount(
			0,
			$repository->findAllBy($findQuery, Documents\Devices\Properties\Variable::class),
			'byValue device.username',
		);
	}

}
