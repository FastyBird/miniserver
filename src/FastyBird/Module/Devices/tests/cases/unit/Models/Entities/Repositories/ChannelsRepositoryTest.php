<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Entities\Repositories;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
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
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testFindOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Channels\ChannelsRepository::class);

		$entity = $repository->find(Uuid\Uuid::fromString('17C59DFA-2EDD-438E-8C49-FAA4E38E5A5E'));

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Channels\ChannelsRepository::class);

		$findQuery = new Queries\Entities\FindChannels();
		$findQuery->byIdentifier('channel-one');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('channel-one', $entity->getIdentifier());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws PersistenceExceptions\Query
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadResultSet(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Channels\ChannelsRepository::class);

		$findQuery = new Queries\Entities\FindChannels();

		$resultSet = $repository->getResultSet($findQuery);

		self::assertSame(3, $resultSet->getTotalCount());
	}

}
