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
final class DashboardTabsRepositoryTest extends Tests\Cases\Unit\DbTestCase
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Dashboards\Tabs\Repository::class);

		$findQuery = new Queries\Configuration\FindDashboardTabs();
		$findQuery->byName('Default');

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('Default', $entity->getName());

		$findQuery = new Queries\Configuration\FindDashboardTabs();
		$findQuery->byName('invalid');

		$entity = $repository->findOneBy($findQuery);

		self::assertNull($entity);

		$findQuery = new Queries\Configuration\FindDashboardTabs();
		$findQuery->byId(Uuid\Uuid::fromString('3333dd94-0605-45b5-ac51-c08cfb604e64'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('Default', $entity->getName());

		$findQuery = new Queries\Configuration\FindDashboardTabs();
		$findQuery->byDashboardId(Uuid\Uuid::fromString('272379d8-8351-44b6-ad8d-73a0abcb7f9c'));

		$entity = $repository->findOneBy($findQuery);

		self::assertIsObject($entity);
		self::assertSame('Default', $entity->getName());
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
		$repository = $this->getContainer()->getByType(Models\Configuration\Dashboards\Tabs\Repository::class);

		$findQuery = new Queries\Configuration\FindDashboardTabs();

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(2, $entities);
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testReadAllByDashboard(): void
	{
		$devicesRepository = $this->getContainer()->getByType(Models\Configuration\Dashboards\Repository::class);

		$findQuery = new Queries\Configuration\FindDashboards();
		$findQuery->byId(Uuid\Uuid::fromString('272379d8-8351-44b6-ad8d-73a0abcb7f9c'));

		$dashboard = $devicesRepository->findOneBy($findQuery);

		self::assertInstanceOf(Documents\Dashboards\Dashboard::class, $dashboard);
		self::assertSame('main-dashboard', $dashboard->getIdentifier());

		$repository = $this->getContainer()->getByType(Models\Configuration\Dashboards\Tabs\Repository::class);

		$findQuery = new Queries\Configuration\FindDashboardTabs();
		$findQuery->forDashboard($dashboard);

		$entities = $repository->findAllBy($findQuery);

		self::assertCount(1, $entities);
	}

}
