<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Entities;

use DateTimeInterface;
use Doctrine\DBAL;
use Error;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
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
	 * Doctrine's TimestampableSubscriber reads the `#[Timestampable]` attribute through
	 * `Timestampable::EXTENSION_ANNOTATION`, feeding `ReflectionProperty::getAttributes()`.
	 * A wrong FQCN there returns an empty array silently -- the property is simply never
	 * stamped -- so this asserts the actual, frozen (tests/common.neon `dateTimeFactory.
	 * frozen`) value, not just that the field is non-null.
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws ApplicationExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testCreateSetsTimestamps(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\DevicesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\DevicesRepository::class);

		$findQuery = new Queries\Entities\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);

		$device = $manager->create(Utils\ArrayHash::from([
			'entity' => Tests\Fixtures\Dummy\DummyDeviceEntity::class,
			'identifier' => 'timestamped-device',
			'connector' => $parent->getConnector(),
			'name' => 'Timestamped device',
		]));

		self::assertInstanceOf(DateTimeInterface::class, $device->getCreatedAt());
		self::assertSame('2020-04-01T12:00:00+00:00', $device->getCreatedAt()->format(DateTimeInterface::ATOM));

		// TimestampableSubscriber::prePersist() stamps both the `create` and `update` fields on
		// insert (Subscribers/TimestampableSubscriber.php:334), so `updatedAt` is set too -- it
		// is only left untouched by a later, genuine update that this test does not perform.
		self::assertInstanceOf(DateTimeInterface::class, $device->getUpdatedAt());
		self::assertSame('2020-04-01T12:00:00+00:00', $device->getUpdatedAt()->format(DateTimeInterface::ATOM));
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
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
