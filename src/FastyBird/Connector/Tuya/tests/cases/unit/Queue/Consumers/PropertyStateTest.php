<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
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
 * What a reported data point and a written channel property hand the state manager
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class PropertyStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '27848e3c-23b4-4aaa-a94e-aae975d98550';

	/** @var list<array<mixed>> */
	private array $set = [];

	/** @var list<array<mixed>> */
	private array $written = [];

	/**
	 * A value the enum property does not know is refused by the state manager: the consumer adds it to the format, in
	 * lower case, and stores it again
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreReportedDataPoints(): void
	{
		$this->prepare('Music');

		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreChannelPropertyState::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreChannelPropertyState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => 'bulb-1',
					'data_points' => [
						['code' => '1', 'value' => true],
						['code' => '2', 'value' => 'colour'],
						['code' => '2', 'value' => 'Music'],
					],
				],
			),
		);

		self::assertTrue($consumed);

		self::assertSame(
			[
				['1', [DevicesStates\Property::ACTUAL_VALUE_FIELD => true], 'com.fastybird.tuya-connector'],
				['2', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 'colour'], 'com.fastybird.tuya-connector'],
				['2', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 'Music'], 'com.fastybird.tuya-connector'],
			],
			$this->set,
		);

		$this->getEntityManager()->clear();

		foreach ($this->device()->getChannels()[0]->getProperties() as $property) {
			if ($property->getIdentifier() === '2') {
				self::assertSame(
					['white', 'colour', 'scene', 'music'],
					$property->getFormat()?->toArray(),
				);
			}
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	#[DataProvider('values')]
	public function testWrittenValueBecomesTheActualOne(string $identifier, bool|string $expected): void
	{
		$this->prepare(null);

		$device = $this->device();
		$channel = $device->getChannels()[0];
		$property = null;

		foreach ($channel->getProperties() as $channelProperty) {
			if ($channelProperty->getIdentifier() === $identifier) {
				$property = $channelProperty;
			}
		}

		self::assertNotNull($property);

		$consumed = $this->getContainer()->getByType(Queue\Consumers\WriteChannelPropertyState::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\WriteChannelPropertyState::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'device' => $device->getId(),
					'channel' => $channel->getId(),
					'property' => $property->getId(),
					'state' => [
						'id' => $property->getId(),
						DevicesStates\Property::ACTUAL_VALUE_FIELD => null,
						DevicesStates\Property::EXPECTED_VALUE_FIELD => $expected,
						DevicesStates\Property::PENDING_FIELD => true,
						DevicesStates\Property::VALID_FIELD => true,
					],
				],
			),
		);

		self::assertTrue($consumed);

		self::assertSame([[$identifier, $expected, null]], $this->written);
		self::assertSame(
			[
				[
					$identifier,
					[
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $expected,
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					],
					'com.fastybird.tuya-connector',
				],
			],
			$this->set,
		);
	}

	/**
	 * @return array<string, array{string, bool|string}>
	 */
	public static function values(): array
	{
		return [
			'switch on' => ['1', true],
			'switch off' => ['1', false],
			'mode' => ['2', 'scene'],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function prepare(string|null $refusedOnce): void
	{
		$refused = false;

		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\ChannelPropertiesManager::class,
				function (MockObject $mock) use ($refusedOnce, &$refused): void {
					$mock
						->method('set')
						->willReturnCallback(
							function (
								DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
								DevicesStates\PropertyUpdate $data,
								Sources\Source|null $source,
							) use (
								$refusedOnce,
								&$refused,
							): Promise\PromiseInterface {
								if (
									!$refused
									&& $refusedOnce !== null
									&& ($data->toArray()[DevicesStates\Property::ACTUAL_VALUE_FIELD] ?? null) === $refusedOnce
								) {
									$refused = true;

									throw new CoreExceptions\InvalidArgument('Value is not in the format');
								}

								$this->set[] = [$property->getIdentifier(), $data->toArray(), $source?->value];

								return Promise\resolve(true);
							},
						);
					$mock->method('setPendingState')->willReturn(Promise\resolve(true));
				},
			),
		);

		$local = $this->mockOf(
			API\LocalApi::class,
			function (MockObject $mock): void {
				$mock
					->method('writeState')
					->willReturnCallback(
						function (string $idx, int|float|string|bool $value, string|null $child = null): Promise\PromiseInterface {
							$this->written[] = [$idx, $value, $child];

							return Promise\resolve(true);
						},
					);
			},
		);

		$this->mockContainerService(
			API\ConnectionManager::class,
			$this->mockOf(
				API\ConnectionManager::class,
				static function (MockObject $mock) use ($local): void {
					$mock->method('getLocalConnection')->willReturn($local);
				},
			),
		);

		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreLocalDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreLocalDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'id' => 'bulb-1',
					'ip_address' => '10.10.10.20',
					'local_key' => 'local-key',
					'encrypted' => true,
					'version' => '3.3',
					'gateway' => null,
					'node_id' => null,
					'name' => 'Bulb',
					'model' => null,
					'icon' => null,
					'category' => 'dj',
					'product_id' => 'product-1',
					'product_name' => 'Bulb',
					'latitude' => null,
					'longitude' => null,
					'sn' => null,
					'mac' => null,
					'data_points' => [
						[
							'device' => 'bulb-1',
							'code' => '1',
							'name' => 'switch_led',
							'data_type' => 'bool',
							'unit' => null,
							'format' => null,
							'min' => null,
							'max' => null,
							'step' => null,
							'queryable' => true,
							'settable' => true,
						],
						[
							'device' => 'bulb-1',
							'code' => '2',
							'name' => 'work_mode',
							'data_type' => 'enum',
							'unit' => null,
							'format' => 'white,colour,scene',
							'min' => null,
							'max' => null,
							'step' => null,
							'queryable' => true,
							'settable' => true,
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
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): Entities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('bulb-1');

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
