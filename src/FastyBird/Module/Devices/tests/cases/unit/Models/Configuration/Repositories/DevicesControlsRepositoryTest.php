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
final class DevicesControlsRepositoryTest extends Tests\Cases\Unit\DbTestCase
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byName('configure');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('configure', $entity->getName());

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byName('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byId(Uuid\Uuid::fromString('7c055b2b-60c3-4017-93db-e9478d8aa662'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('configure', $entity->getName());

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byDeviceId(Uuid\Uuid::fromString('69786d15-fd0c-4d9f-9378-33287c2009fa'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('configure', $entity->getName());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
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
		$findQuery->byId(Uuid\Uuid::fromString('69786d15-fd0c-4d9f-9378-33287c2009fa'));

		$device = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Devices\Device::class, $device);
		self::assertSame('first-device', $device->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->forDevice($device);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Devices\Controls\Repository::class);

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byName('configure');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byName configure');

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byName('CONFIGURE');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byName CONFIGURE');

		$findQuery = new Queries\Configuration\FindDeviceControls();
		$findQuery->byName('conf.gure');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byName conf.gure');
	}

}
