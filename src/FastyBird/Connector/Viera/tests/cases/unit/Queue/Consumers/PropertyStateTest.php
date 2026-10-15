<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\Viera\API;
use FastyBird\Connector\Viera\Entities as VieraEntities;
use FastyBird\Connector\Viera\Exceptions as VieraExceptions;
use FastyBird\Connector\Viera\Helpers;
use FastyBird\Connector\Viera\Queries;
use FastyBird\Connector\Viera\Queue;
use FastyBird\Connector\Viera\Tests;
use FastyBird\Connector\Viera\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
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
 * What a reported television state and a written channel property hand the state manager. A written input source, an
 * application or an HDMI input also changes the two related properties.
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class PropertyStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = '0fb0b34f-7804-4127-b65c-0561dff092e4';

	/** @var list<array<mixed>> */
	private array $set = [];

	/** @var list<array<mixed>> */
	private array $written = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreReportedStates(): void
	{
		$this->prepare();

		$device = $this->device();

		foreach (
			[
				[Types\ChannelPropertyIdentifier::VOLUME, 25],
				[Types\ChannelPropertyIdentifier::MUTE, true],
				[Types\ChannelPropertyIdentifier::KEY_HOME, null],
			] as [$identifier, $value]
		) {
			$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreChannelPropertyState::class)->consume(
				$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
					Queue\Messages\StoreChannelPropertyState::class,
					[
						'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
						'device' => $device->getId(),
						'channel' => Types\ChannelType::TELEVISION,
						'property' => $identifier,
						'value' => $value,
					],
				),
			);

			self::assertTrue($consumed);
		}

		self::assertSame(
			[
				['volume', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 25.0], 'com.fastybird.viera-connector'],
				['mute', [DevicesStates\Property::ACTUAL_VALUE_FIELD => true], 'com.fastybird.viera-connector'],
				[
					'key_home',
					[DevicesStates\Property::ACTUAL_VALUE_FIELD => null, DevicesStates\Property::VALID_FIELD => true],
					'com.fastybird.viera-connector',
				],
			],
			$this->set,
		);
	}

	/**
	 * @param list<array<mixed>> $written
	 * @param list<array<mixed>> $expected
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	#[DataProvider('writes')]
	public function testWrittenValueBecomesTheActualOne(
		string $identifier,
		int|bool|string $value,
		array $written,
		array $expected,
	): void
	{
		$this->prepare();

		$device = $this->device();
		$channel = $device->getChannels()[0];
		$property = null;

		foreach ($channel->getProperties() as $channelProperty) {
			if ($channelProperty->getIdentifier() === $identifier) {
				$property = $channelProperty;
			}
		}

		self::assertInstanceOf(DevicesEntities\Channels\Properties\Dynamic::class, $property);

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
						DevicesStates\Property::EXPECTED_VALUE_FIELD => $value,
						DevicesStates\Property::PENDING_FIELD => true,
						DevicesStates\Property::VALID_FIELD => true,
					],
				],
			),
		);

		self::assertTrue($consumed);

		self::assertSame($written, $this->written);
		self::assertSame($expected, $this->set);
	}

	/**
	 * @return array<string, array{string, int|bool|string, list<array<mixed>>, list<array<mixed>>}>
	 */
	public static function writes(): array
	{
		$changed = static fn (string $identifier, int|bool|string|null $value): array => [
			$identifier,
			[
				DevicesStates\Property::ACTUAL_VALUE_FIELD => $value,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
			],
			'com.fastybird.viera-connector',
		];

		return [
			'volume' => ['volume', 30, [['setVolume', 30]], [$changed('volume', 30)]],
			'mute' => ['mute', true, [['setMute', true]], [$changed('mute', true)]],
			'input source, an HDMI input' => [
				'input_source',
				3,
				[['sendKey', 'NRC_HDMI3-ONOFF']],
				[$changed('input_source', 3), $changed('application', null), $changed('hdmi', 3)],
			],
			'input source, an application' => [
				'input_source',
				'0387878700000014',
				[['launchApplication', '0387878700000014']],
				[
					$changed('input_source', '0387878700000014'),
					$changed('application', '0387878700000014'),
					$changed('hdmi', null),
				],
			],
			'application' => [
				'application',
				'0070000200180001',
				[['launchApplication', '0070000200180001']],
				[
					$changed('application', '0070000200180001'),
					$changed('input_source', '0070000200180001'),
					$changed('hdmi', null),
				],
			],
			'hdmi' => [
				'hdmi',
				1,
				[['sendKey', 'NRC_HDMI1-ONOFF']],
				[$changed('hdmi', 1), $changed('input_source', 1), $changed('application', null)],
			],
		];
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function prepare(): void
	{
		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
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
					$mock->method('setPendingState')->willReturn(Promise\resolve(true));
				},
			),
		);

		$record = fn (string $method): Closure => function (mixed $value) use ($method): Promise\PromiseInterface {
			$this->written[] = [$method, $value];

			return Promise\resolve(true);
		};

		$television = $this->mockOf(
			API\TelevisionApi::class,
			static function (MockObject $mock) use ($record): void {
				$mock->method('isConnected')->willReturn(true);
				$mock->method('setVolume')->willReturnCallback($record('setVolume'));
				$mock->method('setMute')->willReturnCallback($record('setMute'));
				$mock->method('sendKey')->willReturnCallback($record('sendKey'));
				$mock->method('launchApplication')->willReturnCallback($record('launchApplication'));
			},
		);

		$this->mockContainerService(
			API\ConnectionManager::class,
			$this->mockOf(
				API\ConnectionManager::class,
				static function (MockObject $mock) use ($television): void {
					$mock->method('getConnection')->willReturn($television);
				},
			),
		);

		$consumed = $this->getContainer()->getByType(Queue\Consumers\StoreDevice::class)->consume(
			$this->getContainer()->getByType(Helpers\MessageBuilder::class)->create(
				Queue\Messages\StoreDevice::class,
				[
					'connector' => Uuid\Uuid::fromString(self::CONNECTOR_ID),
					'identifier' => 'viera-tv',
					'ip_address' => '10.10.0.50',
					'port' => 55_000,
					'name' => 'Living room TV',
					'model' => 'TX-55EZ950E',
					'manufacturer' => 'Panasonic',
					'serial_number' => '4D454930-0200-1000-8001-PIN000000001',
					'mac_address' => null,
					'encrypted' => false,
					'app_id' => null,
					'encryption_key' => null,
					'hdmi' => [
						['id' => 1, 'name' => 'Apple TV'],
						['id' => 3, 'name' => 'Game console'],
					],
					'applications' => [
						['id' => '0387878700000014', 'name' => 'Netflix'],
						['id' => '0070000200180001', 'name' => 'YouTube'],
					],
				],
			),
		);

		self::assertTrue($consumed);

		$this->getEntityManager()->clear();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws VieraExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function device(): VieraEntities\Devices\Device
	{
		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byIdentifier('viera-tv');

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class)->findOneBy(
			$findDeviceQuery,
			VieraEntities\Devices\Device::class,
		);

		self::assertInstanceOf(VieraEntities\Devices\Device::class, $device);

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
