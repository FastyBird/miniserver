<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Models\Entities\Repositories;

use Error;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions as DoctrineOrmQueryExceptions;
use FastyBird\Module\Ui\Exceptions;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Tests;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Ramsey\Uuid;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class DashboardsRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testFindOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Dashboards\Repository::class);

		$entity = $repository->find(Uuid\Uuid::fromString('ab369e71-ada6-4d1a-a5a8-b6ee5cd58296'));

		self::assertIsObject($entity);
		self::assertNull($entity->getName());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Dashboards\Repository::class);

		$findQuery = new Queries\Entities\FindDashboards();
		$findQuery->byId(Uuid\Uuid::fromString('ab369e71-ada6-4d1a-a5a8-b6ee5cd58296'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertNull($entity->getName());
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\Query
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadResultSet(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Dashboards\Repository::class);

		$findQuery = new Queries\Entities\FindDashboards();

		$resultSet = $repository->getResultSet($findQuery);

		self::assertSame(2, $resultSet->getTotalCount());
	}

}
