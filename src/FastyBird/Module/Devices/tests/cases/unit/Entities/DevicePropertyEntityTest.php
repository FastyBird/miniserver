<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Entities;

use Doctrine\DBAL;
use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Library\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use FastyBird\Library\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;
use function assert;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DevicePropertyEntityTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Error
	 */
	public function testAddChildProperty(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesRepository::class);

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertTrue($parent instanceof Entities\Devices\Properties\Variable);
		self::assertSame('status_led', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity' => Entities\Devices\Properties\Mapped::class,
			'identifier' => 'new-child-property',
			'device' => $parent->getDevice(),
			'parent' => $parent,
			'dataType' => $parent->getDataType(),
			'format' => $parent->getFormat(),
		]));

		self::assertTrue($child instanceof Entities\Devices\Properties\Mapped);
		self::assertSame('new-child-property', $child->getIdentifier());
		self::assertSame($parent, $child->getParent());
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
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Error
	 */
	public function testRemoveChildProperty(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesRepository::class);

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertTrue($parent instanceof Entities\Devices\Properties\Variable);
		self::assertSame('status_led', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity' => Entities\Devices\Properties\Mapped::class,
			'identifier' => 'new-child-property',
			'device' => $parent->getDevice(),
			'parent' => $parent,
			'dataType' => $parent->getDataType(),
			'format' => $parent->getFormat(),
		]));

		self::assertTrue($child instanceof Entities\Devices\Properties\Mapped);
		self::assertSame('new-child-property', $child->getIdentifier());
		self::assertSame($parent, $child->getParent());

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		assert($parent instanceof Entities\Devices\Properties\Property);

		self::assertCount(1, $parent->getChildren());

		$manager->delete($child);

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertTrue($parent instanceof Entities\Devices\Properties\Variable);
		self::assertSame('status_led', $parent->getIdentifier());
		self::assertCount(0, $parent->getChildren());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testRemoveParentProperty(): void
	{
		$manager = $this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesManager::class);

		$repository = $this->getContainer()->getByType(Models\Entities\Devices\Properties\PropertiesRepository::class);

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		self::assertIsObject($parent);
		self::assertTrue($parent instanceof Entities\Devices\Properties\Variable);
		self::assertSame('status_led', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity' => Entities\Devices\Properties\Mapped::class,
			'identifier' => 'new-child-property',
			'device' => $parent->getDevice(),
			'parent' => $parent,
			'dataType' => $parent->getDataType(),
			'format' => $parent->getFormat(),
		]));

		self::assertTrue($child instanceof Entities\Devices\Properties\Mapped);
		self::assertSame('new-child-property', $child->getIdentifier());
		self::assertSame($parent, $child->getParent());

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('new-child-property');

		$child = $repository->findOneBy($findQuery);

		self::assertIsObject($child);
		self::assertTrue($child instanceof Entities\Devices\Properties\Mapped);

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		assert($parent instanceof Entities\Devices\Properties\Property);

		self::assertCount(1, $parent->getChildren());

		$manager->delete($parent);

		$findQuery = new Queries\Entities\FindDeviceProperties();
		$findQuery->byIdentifier('new-child-property');

		$child = $repository->findOneBy($findQuery);

		self::assertIsNotObject($child);
	}

}
