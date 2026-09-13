<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Tests;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class ConnectorsRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byIdentifier('dummy');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('dummy', $entity->getIdentifier());

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byTypes(['dummy', 'unknown']);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('generic', $entity->getIdentifier());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

}
