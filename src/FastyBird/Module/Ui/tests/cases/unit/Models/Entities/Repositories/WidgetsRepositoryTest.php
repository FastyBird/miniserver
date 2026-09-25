<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Models\Entities\Repositories;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
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
final class WidgetsRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws PersistenceExceptions\Query
	 * @throws UiExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testFindOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Widgets\Repository::class);

		$entity = $repository->find(Uuid\Uuid::fromString('15553443-4564-454d-af04-0dfeef08aa96'));

		self::assertIsObject($entity);
		self::assertSame('Room temperature', $entity->getName());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws PersistenceExceptions\Query
	 * @throws UiExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Widgets\Repository::class);

		$findQuery = new Queries\Entities\FindWidgets();
		$findQuery->byId(Uuid\Uuid::fromString('15553443-4564-454d-af04-0dfeef08aa96'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('Room temperature', $entity->getName());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws PersistenceExceptions\Query
	 * @throws UiExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadResultSet(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Widgets\Repository::class);

		$findQuery = new Queries\Entities\FindWidgets();

		$resultSet = $repository->getResultSet($findQuery);

		self::assertSame(4, $resultSet->getTotalCount());
	}

}
