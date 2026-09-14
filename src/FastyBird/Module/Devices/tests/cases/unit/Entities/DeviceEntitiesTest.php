<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Entities;

use Doctrine\DBAL;
use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use FastyBird\Library\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DeviceEntitiesTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testFindChildren(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->forParent($parent);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('child-device', $entity->getIdentifier());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testCreateChild(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity' => Tests\Fixtures\Dummy\DummyDeviceEntity::class,
			'identifier' => 'new-child-device',
			'connector' => $parent->getConnector(),
			'name' => 'New child device',
			'parents' => [
				$parent,
			],
		]));

		self::assertSame('new-child-device', $child->getIdentifier());
		self::assertCount(1, $child->getParents());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testRemoveParent(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());

		$manager->delete($parent);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsNotObject($parent);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('child-device');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsNotObject($entity);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testChildParent(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('child-device');

		$child = $repository->findOneBy($findQuery);

		self::assertIsObject($child);
		self::assertSame('child-device', $child->getIdentifier());

		$manager->delete($child);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertSame('first-device', $parent->getIdentifier());
	}

}
