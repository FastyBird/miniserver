<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Connector\NsPanel\Commands;
use FastyBird\Connector\NsPanel\Entities as NsPanelEntities;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers as PersistenceHelpers;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function array_map;
use function assert;
use function ksort;
use function sort;

/**
 * What the installer stores for a connector, field by field. The installer catches every error and only prints it, so
 * each case reads the database back instead of trusting the exit code.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class InstallTest extends Tests\Cases\Unit\DbTestCase
{

	private bool $databaseKept = false;

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateConnector(): void
	{
		// create a connector, mode, identifier, name, no gateways, then nothing
		$this->install(
			['0', '0', 'ns-panel-pin', 'Pinned panel', 'no', '5'],
		);

		self::assertSame(
			[
				'type' => 'ns-panel-connector',
				'identifier' => 'ns-panel-pin',
				'name' => 'Pinned panel',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'mode' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'mode',
						'name' => null,
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'gateway',
							'device',
							'both',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'gateway',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			$this->stored($this->connector('ns-panel-pin')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnector(): void
	{
		// edit, the only connector, change the mode: yes, mode, name, disable: yes, then nothing
		$this->install(
			['1', '', 'yes', '1', 'Renamed panel', 'yes', '5'],
		);

		self::assertSame(
			[
				'type' => 'ns-panel-connector',
				'identifier' => 'ns-panel',
				'name' => 'Renamed panel',
				'comment' => null,
				'enabled' => false,
				'properties' => [
					'mode' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'mode',
						'name' => 'mode',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'device',
					],
				],
				'controls' => [
					'discover',
				],
			],
			$this->stored($this->connector('ns-panel')),
		);
	}

	/**
	 * The connector as plain arrays without the generated identifiers and timestamps
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(NsPanelEntities\Connectors\Connector $connector): array
	{
		$properties = [];

		foreach ($connector->getProperties() as $property) {
			$data = $property->toArray();

			unset($data['id'], $data['created_at'], $data['updated_at'], $data['connector'], $data['owner']);

			$properties[$property->getIdentifier()] = $data;
		}

		ksort($properties);

		$controls = array_map(
			static fn (DevicesEntities\Connectors\Controls\Control $control): string => $control->getName(),
			$connector->getControls(),
		);

		sort($controls);

		return [
			'type' => $connector::getType(),
			'identifier' => $connector->getIdentifier(),
			'name' => $connector->getName(),
			'comment' => $connector->getComment(),
			'enabled' => $connector->isEnabled(),
			'properties' => $properties,
			'controls' => $controls,
		];
	}

	/**
	 * @param array<string> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function install(array $inputs): string
	{
		$this->keepTheDatabase();

		$application = new Application();
		$application->add($this->getContainer()->getByType(Commands\Install::class));

		$commandTester = new CommandTester($application->find(Commands\Install::NAME));
		$commandTester->setInputs($inputs);
		$commandTester->execute([]);

		$this->getEntityManager()->clear();

		return $commandTester->getDisplay();
	}

	/**
	 * The installer closes the connection when it is done, and the test connection wrapper answers a reconnect with a
	 * new, empty database. The helper is replaced, once, by one that does everything else as the real one does and,
	 * instead of closing the connection, rolls back a transaction left open and clears the entity manager.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function keepTheDatabase(): void
	{
		if ($this->databaseKept) {
			return;
		}

		$database = $this->getContainer()->getByType(PersistenceHelpers\Database::class);
		$entityManager = $this->getEntityManager();

		$mock = $this->mockOf(
			PersistenceHelpers\Database::class,
			static function (MockObject $mock) use ($database, $entityManager): void {
				$mock
					->method('query')
					->willReturnCallback(static fn (callable $callback): mixed => $database->query($callback));
				$mock
					->method('transaction')
					->willReturnCallback(static fn (callable $callback): mixed => $database->transaction($callback));
				$mock
					->method('beginTransaction')
					->willReturnCallback(static function () use ($database): void {
						$database->beginTransaction();
					});
				$mock
					->method('commitTransaction')
					->willReturnCallback(static function () use ($database): void {
						$database->commitTransaction();
					});
				$mock
					->method('clear')
					->willReturnCallback(static function () use ($entityManager): void {
						// Closing the connection discards a transaction the installer did not commit; so does this
						if ($entityManager->getConnection()->isTransactionActive()) {
							$entityManager->getConnection()->rollBack();
						}

						$entityManager->clear();
					});
			},
		);

		$this->mockContainerService(PersistenceHelpers\Database::class, $mock);

		$this->databaseKept = true;
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function connector(string $identifier): NsPanelEntities\Connectors\Connector
	{
		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier($identifier);

		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->findOneBy($findConnectorQuery, NsPanelEntities\Connectors\Connector::class);

		self::assertInstanceOf(NsPanelEntities\Connectors\Connector::class, $connector);

		return $connector;
	}

	/**
	 * The services replaced are final, which PHPStan does not let a mock of one be typed as, so the mock is handed over
	 * as the class it stands in for
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 * @param Closure(MockObject): void $configure
	 *
	 * @return T
	 */
	private function mockOf(string $class, Closure $configure): object
	{
		$mock = $this->createMock($class);

		assert($mock instanceof $class);

		$configure($mock);

		return $mock;
	}

}
