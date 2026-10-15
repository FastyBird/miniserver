<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Commands;

use Closure;
use Error;
use FastyBird\Connector\Zigbee2Mqtt\Commands;
use FastyBird\Connector\Zigbee2Mqtt\Entities as Zigbee2MqttEntities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
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
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testCreateConnector(): void
	{
		// create a connector, identifier, name, server, port, secured port, username, password, no bridge, then nothing
		$this->install(
			['0', 'zigbee2mqtt-pin', 'Pinned broker', '10.0.0.5', '1884', '8884', 'pin-user', 'pin-secret', 'no', '5'],
		);

		self::assertSame(
			[
				'type' => 'zigbee2mqtt-connector',
				'identifier' => 'zigbee2mqtt-pin',
				'name' => 'Pinned broker',
				'comment' => null,
				'enabled' => true,
				'properties' => [
					'mode' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'mode',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 'mqtt',
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
						'value' => 'pin-secret',
					],
					'port' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'port',
						'name' => null,
						'data_type' => 'uint',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 1_884,
					],
					'secured_port' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'secured_port',
						'name' => null,
						'data_type' => 'uint',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 8_884,
					],
					'server' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'server',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '10.0.0.5',
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
						'value' => 'pin-user',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			$this->stored($this->connector('zigbee2mqtt-pin')),
		);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testEditConnector(): void
	{
		// edit, the only connector, name, disable: yes, server, port, secured port, username, password, then nothing
		$this->install(
			['1', '', 'Renamed broker', 'yes', '10.0.0.6', '1885', '8885', 'pin-user-2', 'pin-secret-2', '5'],
		);

		self::assertSame(
			[
				'type' => 'zigbee2mqtt-connector',
				'identifier' => 'zigbee2mqtt',
				'name' => 'Renamed broker',
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
						'value' => 'mqtt',
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
						'value' => 'pin-secret-2',
					],
					'port' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'port',
						'name' => null,
						'data_type' => 'uint',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 1_885,
					],
					'secured_port' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'secured_port',
						'name' => null,
						'data_type' => 'uint',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => 8_885,
					],
					'server' => [
						'type' => 'variable',
						'category' => 'generic',
						'identifier' => 'server',
						'name' => null,
						'data_type' => 'string',
						'unit' => null,
						'format' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'default' => null,
						'value_transformer' => null,
						'value' => '10.0.0.6',
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
						'value' => 'pin-user-2',
					],
				],
				'controls' => [
					'discover',
					'reboot',
				],
			],
			$this->stored($this->connector('zigbee2mqtt')),
		);
	}

	/**
	 * The connector as plain arrays without the generated identifiers and timestamps
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Throwable
	 */
	private function stored(Zigbee2MqttEntities\Connectors\Connector $connector): array
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
	 * @throws Zigbee2MqttExceptions\InvalidArgument
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
	 * @throws Zigbee2MqttExceptions\InvalidArgument
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
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function connector(string $identifier): Zigbee2MqttEntities\Connectors\Connector
	{
		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier($identifier);

		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->findOneBy($findConnectorQuery, Zigbee2MqttEntities\Connectors\Connector::class);

		self::assertInstanceOf(Zigbee2MqttEntities\Connectors\Connector::class, $connector);

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
