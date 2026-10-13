<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Connectors\Repository::class);

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byIdentifier('generic');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byIdentifier generic');

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byIdentifier('gener.c');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byIdentifier gener.c');

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byType('dummy');

		self::assertCount(2, $repository->findAllBy($findQuery), 'byType dummy');

		$findQuery = new Queries\Configuration\FindConnectors();
		$findQuery->byType('d.mmy');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byType d.mmy');
	}

}
