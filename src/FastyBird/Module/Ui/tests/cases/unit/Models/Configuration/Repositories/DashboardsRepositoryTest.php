<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Models\Configuration\Repositories;

use Error;
use FastyBird\Core\Exceptions as CoreExceptions;
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
final class DashboardsRepositoryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws UiExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testReadOne(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Dashboards\Repository::class);

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byIdentifier('main-dashboard');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('Main dashboard', $entity->getName());

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byName('Main dashboard');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('Main dashboard', $entity->getName());

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byName('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byId(Uuid\Uuid::fromString('272379d8-8351-44b6-ad8d-73a0abcb7f9c'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('Main dashboard', $entity->getName());

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byName(null);

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('first-floor', $entity->getIdentifier());
		self::assertNull($entity->getName());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws UiExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testReadAll(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Dashboards\Repository::class);

		$findQuery = new Queries\Configuration\FindDashboards();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

	/**
	 * Every filter matches its argument as it is, ignoring case, and never as a pattern: what a pattern would make of
	 * its characters must not find anything else. A prefix also matches the whole value.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws UiExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testFiltersMatchTheirArgumentLiterally(): void
	{
		$repository = $this->getContainer()->getByType(Models\Configuration\Dashboards\Repository::class);

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byIdentifier('main-dashboard');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byIdentifier main-dashboard');

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byIdentifier('main.dashboard');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byIdentifier main.dashboard');

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byName('Main dashboard');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byName Main dashboard');

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byName('MAIN DASHBOARD');

		self::assertCount(1, $repository->findAllBy($findQuery), 'byName MAIN DASHBOARD');

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byName('Main.dashboard');

		self::assertCount(0, $repository->findAllBy($findQuery), 'byName Main.dashboard');
	}

}
