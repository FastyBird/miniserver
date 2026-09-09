<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Ui\Documents;
use FastyBird\Module\Ui\Exceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Tests;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WidgetsRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Widgets\Repository::class);

		$findQuery = new Queries\Configuration\FindWidgets();
		$findQuery->byIdentifier('room-temperature');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertInstanceOf(Documents\Widgets\AnalogSensor::class, $entity);
		self::assertSame('Room temperature', $entity->getName());

		$findQuery = new Queries\Configuration\FindWidgets();
		$findQuery->byName('Room temperature');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertInstanceOf(Documents\Widgets\AnalogSensor::class, $entity);
		self::assertSame('Room temperature', $entity->getName());

		$findQuery = new Queries\Configuration\FindWidgets();
		$findQuery->byName('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindWidgets();
		$findQuery->byId(Uuid\Uuid::fromString('15553443-4564-454d-af04-0dfeef08aa96'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertInstanceOf(Documents\Widgets\AnalogSensor::class, $entity);
		self::assertSame('Room temperature', $entity->getName());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAll(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Widgets\Repository::class);

		$findQuery = new Queries\Configuration\FindWidgets();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(4, $entities);
	}

}
