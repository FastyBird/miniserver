<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Connector\Shelly\Commands;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers as PersistenceHelpers;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function assert;

/**
 * What the installer stores for a connector and a device, field by field. The installer catches every error and only
 * prints it, so each case reads the database back instead of trusting the exit code.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class InstallTest extends Tests\Cases\Unit\DbTestCase
{

	private bool $databaseKept = false;

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateLocalConnector(): void
	{
		// create a connector, local mode, identifier, name, then nothing
		$this->install(['0', '0', 'shelly-pin', 'Pinned connector', '5']);

		self::assertSame(
			[
				'type' => 'shelly-connector',
				'identifier' => 'shelly-pin',
				'name' => 'Pinned connector',
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
							'local',
							'cloud',
							'mqtt',
							'integrator',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'local',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('shelly-pin')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateCloudConnector(): void
	{
		$this->install(
			['0', '1', 'shelly-cloud-pin', '', 'auth-key-1', 'https://shelly-1-eu.shelly.cloud', '5'],
		);

		self::assertSame(
			[
				'type' => 'shelly-connector',
				'identifier' => 'shelly-cloud-pin',
				'name' => null,
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'cloud_auth_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'cloud_auth_key',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'auth-key-1',
					],
					'cloud_server' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'cloud_server',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'https://shelly-1-eu.shelly.cloud',
					],
					'mode' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'mode',
						'name' => null,
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'local',
							'cloud',
							'mqtt',
							'integrator',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'cloud',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('shelly-cloud-pin')),
		);
	}

	/**
	 * The fixture connector is local, with a string mode property: the installer switches it to the cloud, which
	 * updates the mode and creates both cloud properties, then a second run changes both cloud properties
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnector(): void
	{
		// edit, the only connector, change the mode: yes, cloud, name, disable: yes, key, server, then nothing
		$this->install(['1', '', 'yes', '1', 'Renamed', 'yes', 'auth-key-2', 'server-2', '5']);

		self::assertSame(
			[
				'type' => 'shelly-connector',
				'identifier' => 'shelly-local',
				'name' => 'Renamed',
				'comment' => null,
				'enabled' => false,
				'properties' => [
					'cloud_auth_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'cloud_auth_key',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'auth-key-2',
					],
					'cloud_server' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'cloud_server',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'server-2',
					],
					'mode' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'mode',
						'name' => 'Mode',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'cloud',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('shelly-local')),
		);

		// edit, change the mode: no, keep the name, enable: yes, change the key: yes, key, the server: yes, server
		$this->install(['1', '', 'no', '', 'yes', 'yes', 'auth-key-3', 'yes', 'server-3', '5']);

		self::assertSame(
			[
				'type' => 'shelly-connector',
				'identifier' => 'shelly-local',
				'name' => 'Renamed',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'cloud_auth_key' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'cloud_auth_key',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'auth-key-3',
					],
					'cloud_server' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'cloud_server',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'server-3',
					],
					'mode' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'mode',
						'name' => 'Mode',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'cloud',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('shelly-local')),
		);
	}

	/**
	 * A connector without a mode property is always asked for the mode, and gets the property created
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnectorWithoutMode(): void
	{
		$propertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\Properties\PropertiesManager::class,
		);

		foreach ($this->connector('shelly-local')->getProperties() as $property) {
			$propertiesManager->delete($property);
		}

		$this->getEntityManager()->clear();

		// edit, local mode, name, disable: no, then nothing
		$this->install(['1', '', '0', 'Local again', 'no', '5']);

		self::assertSame(
			[
				'type' => 'shelly-connector',
				'identifier' => 'shelly-local',
				'name' => 'Local again',
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
							'local',
							'cloud',
							'mqtt',
							'integrator',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'local',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('shelly-local')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditDevice(): void
	{
		$this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesManager::class)->create([
			'entity' => Entities\Devices\Device::class,
			'connector' => $this->connector('shelly-local'),
			'identifier' => 'shelly-device',
			'name' => 'Before',
		]);

		$this->getEntityManager()->clear();

		// manage, the only connector, edit a device, the only device, name, nothing, nothing
		$this->install(['3', '', '0', '', 'After', '4', '5']);

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('shelly-device');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);
		self::assertSame('After', $device->getName());
		self::assertSame('shelly-device', $device->getIdentifier());
	}

	/**
	 * @param array<string> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function install(array $inputs): void
	{
		$this->keepTheDatabase();

		$application = new Application();
		$application->add($this->getContainer()->getByType(Commands\Install::class));

		$commandTester = new CommandTester($application->find(Commands\Install::NAME));
		$commandTester->setInputs($inputs);
		$commandTester->execute([]);

		$this->getEntityManager()->clear();
	}

	/**
	 * The installer closes the connection when it is done, and the test connection wrapper answers a reconnect with a
	 * new, empty database. The helper is replaced, once, by one that does everything else as the real one does and, instead
	 * of closing the connection, rolls back a transaction left open and clears the entity manager.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
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
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function connector(string $identifier): Entities\Connectors\Connector
	{
		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier($identifier);

		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);

		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

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
