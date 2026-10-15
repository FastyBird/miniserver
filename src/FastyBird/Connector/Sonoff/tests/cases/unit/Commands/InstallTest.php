<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Connector\Sonoff\Commands;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Helpers as PersistenceHelpers;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Override;
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

	#[Override]
	public function setUp(): void
	{
		// The files load in the reverse order they are registered in, so this one comes after the connectors
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../sql/identifiers.data.sql');

		parent::setUp();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateConnector(): void
	{
		// create a connector, LAN mode, identifier, name, username, password, Europe, then nothing
		$this->install(['0', '1', 'sonoff-pin', 'Pinned connector', 'user@pin.com', 'secret-pin', '0', '5']);

		// A defect older than this test: the installer hands the region property the CloudApiEndpoint cases, not their
		// values, as its format, the property cannot be created, and the whole connector is rolled back
		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('sonoff-pin');

		self::assertNull(
			$this->getContainer()->getByType(DevicesModels\Entities\Connectors\ConnectorsRepository::class)->findOneBy(
				$findConnectorQuery,
				Entities\Connectors\Connector::class,
			),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnector(): void
	{
		// edit, the second connector by name (Sonoff Local), change the mode: yes, cloud, name, disable: yes,
		// change the username: yes, username, change the password: yes, password, then nothing
		$this->install(['1', '1', 'yes', '2', 'Renamed', 'yes', 'yes', 'new@user.com', 'yes', 'new-secret', '5']);

		self::assertSame(
			[
				'type' => 'sonoff-connector',
				'identifier' => 'sonoff-local',
				'name' => 'Renamed',
				'comment' => null,
				'enabled' => false,
				'properties' => [
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
					'password' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'password',
						'name' => 'Password',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'new-secret',
					],
					'username' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'username',
						'name' => 'Username',
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'new@user.com',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('sonoff-local')),
		);
	}

	/**
	 * A connector without its mode, username and password properties is asked for all three and gets them created
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
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

		foreach ($this->connector('sonoff-local')->getProperties() as $property) {
			$propertiesManager->delete($property);
		}

		$this->getEntityManager()->clear();

		// edit, Sonoff Local, auto mode, name, disable: no, username, password, then nothing
		$this->install(['1', '1', '0', 'Local again', 'no', 'other@user.com', 'other-secret', '5']);

		self::assertSame(
			[
				'type' => 'sonoff-connector',
				'identifier' => 'sonoff-local',
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
							'lan',
							'cloud',
							'auto',
						],
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'auto',
					],
					'password' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'password',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'other-secret',
					],
					'username' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'username',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'other@user.com',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			Tests\Support\StoredEntities::connector($this->connector('sonoff-local')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditDevice(): void
	{
		// manage, Sonoff Local, edit a device, the first by name (Longer identifier), name, nothing, nothing
		$this->install(['3', '1', '0', '0', 'Renamed device', '4', '5']);

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('abcd');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);
		self::assertSame('Renamed device', $device->getName());

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('abc');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);
		self::assertSame('Shorter identifier', $device->getName());
	}

	/**
	 * @param array<string> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
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
	 * @throws SonoffExceptions\InvalidArgument
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
	 * @throws SonoffExceptions\InvalidArgument
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
