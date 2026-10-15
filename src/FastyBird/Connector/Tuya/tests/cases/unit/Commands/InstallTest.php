<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Connector\Tuya\Commands;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Tests;
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
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateLocalConnector(): void
	{
		// create a connector, local mode, identifier, name, access ID, access secret, Europe, then nothing
		$this->install(['0', '0', 'tuya-pin', 'Pinned connector', 'access-id', 'access-secret', '0', '5']);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'tuya-pin',
				'name' => 'Pinned connector',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'access_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_id',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'access-id',
					],
					'access_secret' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_secret',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'access-secret',
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
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'local',
					],
					'openapi_endpoint' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'openapi_endpoint',
						'name' => null,
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'https://openapi.tuyaeu.com',
							'https://openapi-weaz.tuyaeu.com',
							'https://openapi.tuyaus.com',
							'https://openapi-ueaz.tuyaus.com',
							'https://openapi.tuyacn.com',
							'https://openapi.tuyain.com',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'https://openapi.tuyaeu.com',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('tuya-pin')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateCloudConnector(): void
	{
		// create a connector, cloud mode, identifier, no name, access ID, access secret, America, user, then nothing
		$this->install(['0', '1', 'tuya-cloud-pin', '', 'access-id-2', 'access-secret-2', '2', 'uid-1', '5']);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'tuya-cloud-pin',
				'name' => null,
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'access_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_id',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'access-id-2',
					],
					'access_secret' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_secret',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'access-secret-2',
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
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'cloud',
					],
					'openapi_endpoint' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'openapi_endpoint',
						'name' => null,
						'data_type' => 'enum',
						'unit' => null,
						'format' => [
							'https://openapi.tuyaeu.com',
							'https://openapi-weaz.tuyaeu.com',
							'https://openapi.tuyaus.com',
							'https://openapi-ueaz.tuyaus.com',
							'https://openapi.tuyacn.com',
							'https://openapi.tuyain.com',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'https://openapi.tuyaus.com',
					],
					'uid' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'uid',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'uid-1',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('tuya-cloud-pin')),
		);
	}

	/**
	 * The fixture cloud connector has no user property: the first run creates it, the second changes it and the third
	 * switches the connector to local, which removes it
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnector(): void
	{
		// edit, Tuya Cloud, change the mode: no, name, disable: yes, change the access ID: yes, access ID,
		// change the access secret: yes, access secret, user, then nothing
		$this->install(['1', '0', 'no', 'Renamed', 'yes', 'yes', 'id-2', 'yes', 'secret-2', 'uid-2', '5']);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'tuya-cloud',
				'name' => 'Renamed',
				'comment' => null,
				'enabled' => false,
				'properties' => [
					'access_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_id',
						'name' => 'Access ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'id-2',
					],
					'access_secret' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_secret',
						'name' => 'Access Secret',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'secret-2',
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
					'string' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'string',
						'name' => 'UID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Bjhq01pE7q4ijNMN',
					],
					'uid' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'uid',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'uid-2',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('tuya-cloud')),
		);

		// edit, Tuya Cloud, change the mode: no, keep the name, enable: yes, the access ID: no, the secret: no,
		// change the user: yes, user, then nothing
		$this->install(['1', '0', 'no', '', 'yes', 'no', 'no', 'yes', 'uid-3', '5']);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'tuya-cloud',
				'name' => 'Renamed',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'access_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_id',
						'name' => 'Access ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'id-2',
					],
					'access_secret' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_secret',
						'name' => 'Access Secret',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'secret-2',
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
					'string' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'string',
						'name' => 'UID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Bjhq01pE7q4ijNMN',
					],
					'uid' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'uid',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'uid-3',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('tuya-cloud')),
		);

		// edit, Tuya Cloud, change the mode: yes, local, keep the name, disable: no, the access ID: no, the secret: no,
		// the user: no, then nothing
		$this->install(['1', '0', 'yes', '0', '', 'no', 'no', 'no', 'no', '5']);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'tuya-cloud',
				'name' => 'Renamed',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'access_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_id',
						'name' => 'Access ID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'id-2',
					],
					'access_secret' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_secret',
						'name' => 'Access Secret',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'secret-2',
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
						'value' => 'local',
					],
					'string' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'string',
						'name' => 'UID',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'Bjhq01pE7q4ijNMN',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('tuya-cloud')),
		);
	}

	/**
	 * A connector without its properties is asked for each of them and gets them created
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnectorWithoutProperties(): void
	{
		$propertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\Properties\PropertiesManager::class,
		);

		foreach ($this->connector('tuya-local')->getProperties() as $property) {
			$propertiesManager->delete($property);
		}

		$this->getEntityManager()->clear();

		// edit, Tuya Local, cloud mode, name, disable: no, access ID, access secret, user, then nothing
		$this->install(['1', '1', '1', 'Local again', 'no', 'id-x', 'secret-x', 'uid-x', '5']);

		self::assertSame(
			[
				'type' => 'tuya-connector',
				'identifier' => 'tuya-local',
				'name' => 'Local again',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'access_id' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_id',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'id-x',
					],
					'access_secret' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'access_secret',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'secret-x',
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
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'cloud',
					],
					'uid' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'uid',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'uid-x',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('tuya-local')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditDevice(): void
	{
		$this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesManager::class)->create([
			'entity' => Entities\Devices\Device::class,
			'connector' => $this->connector('tuya-local'),
			'identifier' => 'tuya-device',
			'name' => 'Before',
		]);

		$this->getEntityManager()->clear();

		// manage, Tuya Local, edit a device, the only device, name, nothing, nothing
		$this->install(['3', '1', '0', '', 'After', '4', '5']);

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('tuya-device');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);
		self::assertSame('After', $device->getName());
		self::assertSame('tuya-device', $device->getIdentifier());
	}

	/**
	 * @param array<string> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
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
	 * @throws TuyaExceptions\InvalidArgument
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
	 * @throws TuyaExceptions\InvalidArgument
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
