<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Sonoff\Entities as SonoffEntities;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Override;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function assert;

/**
 * The connector has two devices, `abc` and `abcd`, and the identifier of the second extends the first: what is stored
 * for one must reach that one only
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StoreStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '63a28696-cbea-4757-84d7-156e52ade4d3';

	private const string DEVICE_ABC = '1e4b1d2f-6e3c-4b9f-ad8e-7c6b5a4f3e2d';

	#[Override]
	public function setUp(): void
	{
		// The files load in the reverse order they are registered in, so this one comes after the connectors
		$this->registerDatabaseSchemaFile(__DIR__ . '/../../../../sql/identifiers.data.sql');

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
	public function testConnectionStateReachesTheDeviceWithThatIdentifierOnly(): void
	{
		$devices = [];

		$connection = $this->mockOf(
			DevicesUtilities\DeviceConnection::class,
			static function (MockObject $mock) use (&$devices): void {
				$mock->method('getState')->willReturn(DevicesTypes\ConnectionState::UNKNOWN);
				$mock
					->method('setState')
					->willReturnCallback(
						static function (DevicesDocuments\Devices\Device $device) use (&$devices): bool {
							$devices[] = $device->getId()->toString();

							return true;
						},
					);
			},
		);

		$this->mockContainerService(DevicesUtilities\DeviceConnection::class, $connection);

		$consumer = $this->getContainer()->getByType(Queue\Consumers\StoreDeviceConnectionState::class);

		$consumer->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDeviceConnectionState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => 'abc',
					'state' => DevicesTypes\ConnectionState::CONNECTED,
				],
			),
		);

		self::assertSame([self::DEVICE_ABC], $devices);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testParameterStateReachesTheDeviceWithThatIdentifierOnly(): void
	{
		$devices = [];

		$states = $this->mockOf(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			static function (MockObject $mock) use (&$devices): void {
				$mock
					->method('set')
					->willReturnCallback(
						static function (DevicesDocuments\Devices\Properties\Dynamic $property) use (&$devices): Promise\PromiseInterface {
							$devices[] = $property->getDevice()->toString();

							return Promise\resolve(true);
						},
					);
			},
		);

		$this->mockContainerService(DevicesModels\States\Async\DevicePropertiesManager::class, $states);

		$consumer = $this->getContainer()->getByType(Queue\Consumers\StoreParametersStates::class);

		$consumer->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreParametersStates::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => 'abc',
					'parameters' => [
						['name' => 'rssi', 'value' => -50],
					],
				],
			),
		);

		self::assertSame([self::DEVICE_ABC], $devices);
	}

	/**
	 * What storing the reported parameters hands the state managers for a dynamic property, and what it stores for a
	 * variable one, on the device and on a channel
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testParameterStatesFieldByField(): void
	{
		$set = [];

		$record = static function (string $owner) use (&$set): Closure {
			return static function (
				DevicesDocuments\Property $property,
				DevicesStates\PropertyUpdate $data,
				Sources\Source|null $source,
			) use (
				$owner,
				&$set,
): Promise\PromiseInterface {
				$set[] = [$owner, $property->getIdentifier(), $data->toArray(), $source?->value];

				return Promise\resolve(true);
			};
		};

		$this->mockContainerService(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\DevicePropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('device'));
				},
			),
		);
		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\ChannelPropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('channel'));
				},
			),
		);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->find(
			Uuid\Uuid::fromString(self::DEVICE_ABC),
		);
		self::assertNotNull($device);

		$devicePropertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\Properties\PropertiesManager::class,
		);
		$devicePropertiesManager->create([
			'entity' => DevicesEntities\Devices\Properties\Variable::class,
			'device' => $device,
			'identifier' => 'note',
			'dataType' => ValuesTypes\DataType::STRING,
			'value' => 'none',
		]);

		$channel = $this->getContainer()->getByType(DevicesModels\Entities\Channels\ChannelsManager::class)->create([
			'entity' => SonoffEntities\Channels\Channel::class,
			'device' => $device,
			'identifier' => 'switch_0',
		]);

		$channelPropertiesManager = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\Properties\PropertiesManager::class,
		);
		$channelPropertiesManager->create([
			'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
			'channel' => $channel,
			'identifier' => 'switch',
			'dataType' => ValuesTypes\DataType::BOOLEAN,
			'settable' => true,
			'queryable' => true,
		]);
		$channelPropertiesManager->create([
			'entity' => DevicesEntities\Channels\Properties\Variable::class,
			'channel' => $channel,
			'identifier' => 'label',
			'dataType' => ValuesTypes\DataType::STRING,
			'value' => 'none',
		]);

		$this->getEntityManager()->clear();

		// Built directly from the parameter state classes, one device and one channel kind each
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreParametersStates::class)->consume(
			new Queue\Messages\StoreParametersStates(
				Uuid\Uuid::fromString(self::CONNECTOR_ID),
				'abc',
				[
					new Queue\Messages\States\DeviceParameterState('rssi', -61),
					new Queue\Messages\States\DeviceParameterState('note', 'stored'),
					new Queue\Messages\States\ChannelParameterState('switch', true, 'switch_0'),
					new Queue\Messages\States\ChannelParameterState('label', 'kitchen', 'switch_0'),
				],
			),
		);

		self::assertTrue($consumed);

		self::assertSame(
			[
				['device', 'rssi', [DevicesStates\Property::ACTUAL_VALUE_FIELD => -61], 'com.fastybird.sonoff-connector'],
				['channel', 'switch', [DevicesStates\Property::ACTUAL_VALUE_FIELD => true], 'com.fastybird.sonoff-connector'],
			],
			$set,
		);

		$this->getEntityManager()->clear();

		$values = [];

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->find(
			Uuid\Uuid::fromString(self::DEVICE_ABC),
		);
		self::assertNotNull($device);

		foreach ($device->getProperties() as $property) {
			if ($property instanceof DevicesEntities\Devices\Properties\Variable) {
				$values['device'][$property->getIdentifier()] = $property->getValue();
			}
		}

		foreach ($device->getChannels() as $deviceChannel) {
			foreach ($deviceChannel->getProperties() as $property) {
				if ($property instanceof DevicesEntities\Channels\Properties\Variable) {
					$values['channel'][$property->getIdentifier()] = $property->getValue();
				}
			}
		}

		self::assertSame(['device' => ['note' => 'stored'], 'channel' => ['label' => 'kitchen']], $values);
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
