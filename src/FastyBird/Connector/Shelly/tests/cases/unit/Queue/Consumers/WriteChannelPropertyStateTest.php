<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Tests;
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
 * What a written channel property hands the state manager once the device took the value: the expected value as the
 * actual one, and the expected value cleared
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class WriteChannelPropertyStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '86d6a4ba-6292-4947-b8c1-8681569ac28e';

	private const string IDENTIFIER = 'c45bbee4c926-shellyrgbw2';

	/** @var list<array<mixed>> */
	private array $set = [];

	/** @var list<array<mixed>> */
	private array $written = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	#[DataProvider('values')]
	public function testWrittenValueBecomesTheActualOne(
		string $identifier,
		int|float|bool|string $expected,
		int|float|bool|string $flattened,
	): void
	{
		$this->recordStates();
		$this->recordWrites();
		$this->storeDevice();

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

		self::assertSame([['10.10.0.239', '1_light_0', $identifier, $flattened]], $this->written);

		self::assertSame(
			[
				[
					$identifier,
					[
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $expected,
						DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					],
					'com.fastybird.shelly-connector',
				],
			],
			$this->set,
		);
	}

	/**
	 * @return array<string, array{string, int|float|bool|string, int|float|bool|string}>
	 */
	public static function values(): array
	{
		return [
			'brightness' => ['5101_brightness', 42, 42],
			'output on' => ['1101_output', true, true],
			'output off' => ['1101_output', false, false],
			'white' => ['5102_white', 'warm', 'warm'],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordStates(): void
	{
		$channels = $this->mockOf(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			function (MockObject $mock): void {
				$mock
					->method('set')
					->willReturnCallback(
						function (
							DevicesDocuments\Channels\Properties\Dynamic|DevicesDocuments\Channels\Properties\Mapped $property,
							Utils\ArrayHash|DevicesStates\PropertyUpdate $data,
							Sources\Source|null $source,
						): Promise\PromiseInterface {
							// What the manager makes of it on its first line
							$data = $data instanceof DevicesStates\PropertyUpdate ? $data->toArrayHash() : $data;

							$this->set[] = [$property->getIdentifier(), (array) $data, $source?->value];

							return Promise\resolve(true);
						},
					);
				$mock
					->method('setPendingState')
					->willReturn(Promise\resolve(true));
			},
		);

		$this->mockContainerService(DevicesModels\States\Async\ChannelPropertiesManager::class, $channels);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordWrites(): void
	{
		$api = $this->mockOf(
			API\Gen1HttpApi::class,
			function (MockObject $mock): void {
				$mock
					->method('setDeviceState')
					->willReturnCallback(
						function (
							string $address,
							string|null $username,
							string|null $password,
							string $channel,
							string $property,
							int|float|string|bool $value,
						): Promise\PromiseInterface {
							$this->written[] = [$address, $channel, $property, $value];

							return Promise\resolve(true);
						},
					);
			},
		);

		$connectionManager = $this->mockOf(
			API\ConnectionManager::class,
			static function (MockObject $mock) use ($api): void {
				$mock->method('getGen1HttpApiConnection')->willReturn($api);
			},
		);

		$this->mockContainerService(API\ConnectionManager::class, $connectionManager);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function storeDevice(): void
	{
		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreLocalDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreLocalDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => self::IDENTIFIER,
					'serial_number' => 'c45bbee4c926',
					'generation' => 'gen1',
					'ip_address' => '10.10.0.239',
					'domain' => null,
					'model' => 'SHRGBW2',
					'mac_address' => 'c4:5b:be:e4:c9:26',
					'auth_enabled' => false,
					'firmware_version' => '20230913-114008/v1.14.0-gcb84623',
					'channels' => [
						[
							'identifier' => '1_light_0',
							'name' => 'light_0',
							'properties' => [
								[
									'identifier' => '1101_output',
									'name' => 'output',
									'data_type' => 'bool',
									'queryable' => true,
									'settable' => true,
								],
								[
									'identifier' => '5101_brightness',
									'name' => 'brightness',
									'data_type' => 'uchar',
									'format' => [0, 100],
									'queryable' => true,
									'settable' => true,
								],
								[
									'identifier' => '5102_white',
									'name' => 'white',
									'data_type' => 'string',
									'queryable' => true,
									'settable' => true,
								],
							],
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
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): Entities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier(self::IDENTIFIER);

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
