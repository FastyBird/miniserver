<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests;
use Nette;
use Ramsey\Uuid;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ConnectorsPropertiesRepositoryTest extends Tests\Cases\Unit\DbTestCase
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byIdentifier('username');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->startWithIdentifier('user');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->endWithIdentifier('ame');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byIdentifier('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byId(Uuid\Uuid::fromString('5a8b01f2-621c-4c41-bc83-c089d72b2366'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->byIdentifier('username');
		$findQuery->byConnectorId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('username', $entity->getIdentifier());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorProperties();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindConnectorProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Connectors\Properties\Dynamic::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindConnectorProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Connectors\Properties\Variable::class);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindConnectorDynamicProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Connectors\Properties\Dynamic::class);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindConnectorVariableProperties();

		$entities = $repository->findAllBy($findQuery, Documents\Connectors\Properties\Variable::class);

		self::assertCount(2, $entities);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->settable(true);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(0, $entities);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->queryable(true);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(0, $entities);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testReadAllByConnector(): void
	{
		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byId(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		$connector = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Connectors\Connector::class, $connector);
		self::assertSame('generic', $connector->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Properties\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectorProperties();
		$findQuery->forConnector($connector);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

}
