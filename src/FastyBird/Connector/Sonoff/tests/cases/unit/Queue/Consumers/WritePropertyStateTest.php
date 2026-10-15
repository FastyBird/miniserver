<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function assert;

/**
 * What a written device or channel property hands the state manager once the device took the value: the expected
 * value as the actual one, and the expected value cleared
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class WritePropertyStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '63a28696-cbea-4757-84d7-156e52ade4d3';

	/** @var list<array<mixed>> */
	private array $set = [];

	/** @var list<array<mixed>> */
	private array $written = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	#[DataProvider('values')]
	public function testWrittenDeviceValueBecomesTheActualOne(string $expected): void
	{
		$this->prepare();

		$device = $this->device();
		$property = null;

		foreach ($device->getProperties() as $deviceProperty) {
			if ($deviceProperty->getIdentifier() === Types\DevicePropertyIdentifier::STATUS_LED->value) {
				$property = $deviceProperty;
			}
		}

		self::assertNotNull($property);

		$consumed = $this->getContainer()->getByType(Queue\Consumers\WriteDevicePropertyState::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\WriteDevicePropertyState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'device' => $device->getId(),
					'property' => $property->getId(),
					'state' => $this->state($property->getId(), $expected),
				],
			),
		);

		self::assertTrue($consumed);

		self::assertSame([['pin-device', '10.0.0.77', 8_081, 'sledOnline', $expected, null, null]], $this->written);
		self::assertSame(
			[
				[
					'device',
					Types\DevicePropertyIdentifier::STATUS_LED->value,
					[
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $expected,
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					],
					'com.fastybird.sonoff-connector',
				],
			],
			$this->set,
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
	#[DataProvider('values')]
	public function testWrittenChannelValueBecomesTheActualOne(string $expected): void
	{
		$this->prepare();

		$device = $this->device();
		$channel = $device->getChannels()[0];
		$property = $channel->getProperties()[0];

		$consumed = $this->getContainer()->getByType(Queue\Consumers\WriteChannelPropertyState::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\WriteChannelPropertyState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'device' => $device->getId(),
					'channel' => $channel->getId(),
					'property' => $property->getId(),
					'state' => $this->state($property->getId(), $expected),
				],
			),
		);

		self::assertTrue($consumed);

		self::assertSame(
			[['pin-device', '10.0.0.77', 8_081, 'switch', $expected, Types\ChannelGroup::SWITCHES, 1]],
			$this->written,
		);
		self::assertSame(
			[
				[
					'channel',
					'switch',
					[
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $expected,
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					],
					'com.fastybird.sonoff-connector',
				],
			],
			$this->set,
		);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function values(): array
	{
		return [
			'on' => ['on'],
			'off' => ['off'],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function state(Uuid\UuidInterface $id, string $expected): array
	{
		return [
			'id' => $id,
			DevicesStates\Property::ACTUAL_VALUE_FIELD => null,
			DevicesStates\Property::EXPECTED_VALUE_FIELD => $expected,
			DevicesStates\Property::PENDING_FIELD => true,
			DevicesStates\Property::VALID_FIELD => true,
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function prepare(): void
	{
		$record = fn (string $owner): Closure => function (
			DevicesDocuments\Property $property,
			Utils\ArrayHash|DevicesStates\PropertyUpdate $data,
			Sources\Source|null $source,
		) use ($owner): Promise\PromiseInterface {
				// What the manager makes of it on its first line
				$data = $data instanceof DevicesStates\PropertyUpdate ? $data->toArrayHash() : $data;

				$this->set[] = [$owner, $property->getIdentifier(), (array) $data, $source?->value];

				return Promise\resolve(true);
		};

		$this->mockContainerService(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\DevicePropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('device'));
					$mock->method('setPendingState')->willReturn(Promise\resolve(true));
				},
			),
		);
		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\ChannelPropertiesManager::class,
				static function (MockObject $mock) use ($record): void {
					$mock->method('set')->willReturnCallback($record('channel'));
					$mock->method('setPendingState')->willReturn(Promise\resolve(true));
				},
			),
		);

		$lan = $this->mockOf(
			API\LanApi::class,
			function (MockObject $mock): void {
				$mock
					->method('setDeviceState')
					->willReturnCallback(
						function (
							string $id,
							string $ipAddress,
							int $port,
							string $parameter,
							string|int|float|bool $value,
							Types\ChannelGroup|null $group = null,
							int|null $outlet = null,
						): Promise\PromiseInterface {
							$this->written[] = [$id, $ipAddress, $port, $parameter, $value, $group, $outlet];

							return Promise\resolve(true);
						},
					);
			},
		);

		$this->mockContainerService(
			API\ConnectionManager::class,
			$this->mockOf(
				API\ConnectionManager::class,
				static function (MockObject $mock) use ($lan): void {
					$mock->method('getLanConnection')->willReturn($lan);
				},
			),
		);

		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'id' => 'pin-device',
					'apiKey' => 'api-key',
					'deviceKey' => 'device-key',
					'uiid' => 1,
					'name' => 'Pinned device',
					'description' => null,
					'brandName' => 'SONOFF',
					'brandLogo' => null,
					'productModel' => 'BASICR2',
					'model' => 'PSF-BD1-GL',
					'mac' => 'd0:27:00:00:00:77',
					'ipAddress' => '10.0.0.77',
					'domain' => 'eWeLink_pin.local',
					'port' => 8_081,
					'parameters' => [
						[
							'group' => 'device',
							'identifier' => 'sledOnline',
							'name' => 'Status LED',
							'type' => 'device',
							'dataType' => 'enum',
							'format' => ['on', 'off'],
							'settable' => true,
							'queryable' => true,
							'scale' => null,
						],
						[
							'group' => 'switch_1',
							'identifier' => 'switch',
							'name' => 'Switch',
							'type' => 'channel',
							'dataType' => 'enum',
							'format' => ['on', 'off'],
							'settable' => true,
							'queryable' => true,
							'scale' => null,
						],
					],
				],
			),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): Entities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('pin-device');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			Entities\Devices\Device::class,
		);

		self::assertInstanceOf(Entities\Devices\Device::class, $device);

		return $device;
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
