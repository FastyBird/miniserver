<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Connector\HomeKit\Commands;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers as HomeKitHelpers;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Tests;
use FastyBird\Connector\HomeKit\Types;
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
use function is_string;
use function ksort;
use function sort;

/**
 * What the installer stores for a connector, field by field, together with what the connector's entity subscribers add
 * to a new one. The installer catches every error and only prints it, so each case reads the database back instead of
 * trusting the exit code.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class InstallTest extends Tests\Cases\Unit\DbTestCase
{

	/** The values the subscribers generate at random, by the pattern each one has */
	private const array GENERATED = [
		'mac_address' => '/^([0-9A-F]{2}:){6}[0-9A-F]{2}$/',
		'setup_id' => '/^[0-9a-f]{4}$/',
		'pin_code' => '/^\d{3}-\d{2}-\d{3}$/',
		'server_secret' => '/^[0-9a-f]{64}$/',
		'xhm_uri' => '/^X-HM:\/\/[0-9A-Z]{9}[0-9a-f]{4}$/',
	];

	private bool $databaseKept = false;

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateConnector(): void
	{
		// create a connector, identifier, name, port, no devices, then nothing
		$this->install(['0', 'homekit-pin', 'Pinned bridge', '51830', 'no', '5']);

		$connector = $this->connector('homekit-pin');

		self::assertSame(
			HomeKitHelpers\Protocol::getXhmUri(
				$connector->getPinCode(),
				$connector->getSetupId(),
				Types\AccessoryCategory::BRIDGE,
			),
			$this->propertyValue($connector, 'xhm_uri'),
		);

		self::assertSame(
			[
				'type' => 'homekit-connector',
				'identifier' => 'homekit-pin',
				'name' => 'Pinned bridge',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'configuration_version' => $this->variable('configuration_version', 'ushort', 1),
					'mac_address' => $this->variable('mac_address', 'string', 'generated'),
					'paired' => $this->variable('paired', 'bool', false),
					'pin_code' => $this->variable('pin_code', 'string', 'generated'),
					'port' => $this->variable('port', 'uchar', 51_830),
					'server_secret' => $this->variable('server_secret', 'string', 'generated'),
					'setup_id' => $this->variable('setup_id', 'string', 'generated'),
					'xhm_uri' => $this->variable('xhm_uri', 'string', 'generated'),
				],
				'controls' => [
					'reboot',
				],
			],
			$this->stored($connector),
		);
	}

	/**
	 * The fixture connector has no properties: the first edit creates its port, the second changes it
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnector(): void
	{
		// edit, the only connector, name, disable: yes, port, then nothing
		$this->install(['1', '', 'Renamed bridge', 'yes', '51831', '5']);

		self::assertSame(
			[
				'type' => 'homekit-connector',
				'identifier' => 'homekit',
				'name' => 'Renamed bridge',
				'comment' => null,
				'enabled' => false,
				'properties' => [
					'port' => $this->variable('port', 'uchar', 51_831),
				],
				'controls' => [],
			],
			$this->stored($this->connector('homekit')),
		);

		// edit, the only connector, name, enable: yes, port, then nothing
		$this->install(['1', '', '', 'yes', '51832', '5']);

		self::assertSame(
			[
				'type' => 'homekit-connector',
				'identifier' => 'homekit',
				'name' => 'Renamed bridge',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'port' => $this->variable('port', 'uchar', 51_832),
				],
				'controls' => [],
			],
			$this->stored($this->connector('homekit')),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function variable(string $identifier, string $dataType, mixed $value): array
	{
		return [
			'type' => 'variable',
			'category' => 'generic',
			'identifier' => $identifier,
			'name' => null,
			'data_type' => $dataType,
			'unit' => null,
			'format' => null,
			'invalid' => null,
			'scale' => null,
			'step' => null,
			'default' => null,
			'value_transformer' => null,
			'value' => $value,
		];
	}

	/**
	 * The connector as plain arrays without the generated identifiers and timestamps. A value generated at random is
	 * checked against its pattern and stands as `generated`.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(HomeKitEntities\Connectors\Connector $connector): array
	{
		$properties = [];

		foreach ($connector->getProperties() as $property) {
			$data = $property->toArray();

			unset($data['id'], $data['created_at'], $data['updated_at'], $data['connector'], $data['owner']);

			if (isset(self::GENERATED[$property->getIdentifier()])) {
				assert(is_string($data['value']));
				self::assertMatchesRegularExpression(self::GENERATED[$property->getIdentifier()], $data['value']);

				$data['value'] = 'generated';
			}

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
	 * @throws Throwable
	 */
	private function propertyValue(HomeKitEntities\Connectors\Connector $connector, string $identifier): mixed
	{
		foreach ($connector->getProperties() as $property) {
			if ($property->getIdentifier() === $identifier) {
				self::assertInstanceOf(DevicesEntities\Connectors\Properties\Variable::class, $property);

				return $property->getValue();
			}
		}

		self::fail('Connector has no ' . $identifier . ' property');
	}

	/**
	 * @param array<string> $inputs
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
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
	 * new, empty database. The helper is replaced, once, by one that does everything else as the real one does and,
	 * instead of closing the connection, rolls back a transaction left open and clears the entity manager.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
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
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function connector(string $identifier): HomeKitEntities\Connectors\Connector
	{
		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier($identifier);

		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->findOneBy($findConnectorQuery, HomeKitEntities\Connectors\Connector::class);

		self::assertInstanceOf(HomeKitEntities\Connectors\Connector::class, $connector);

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
