<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Tests\Cases\Unit\Queue\Consumers;

use Closure;
use Error;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Queue;
use FastyBird\Connector\HomeKit\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_merge;
use function assert;

/**
 * What storing a reported property state hands the state managers for a dynamic property and for a property mapped to
 * a dynamic one, and what it stores for a variable property and for a property mapped to a variable one, on a device
 * and on a channel
 */
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
final class StorePropertyStateTest extends Tests\Cases\Unit\DbTestCase
{

	private const string CONNECTOR_ID = 'f5a8691b-4917-4866-878f-5217193cf14b';

	/** @var list<array<mixed>> */
	private array $calls = [];

	/** @var array<string, Uuid\UuidInterface> */
	private array $ids = [];

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreDevicePropertyState(): void
	{
		$this->recordStates();
		$this->createEntities();

		$consumer = $this->getContainer()->getByType(Queue\Consumers\StoreDevicePropertyState::class);

		foreach (['dynamic' => 21.5, 'mapped_dynamic' => 22.5, 'variable' => 23.5, 'mapped_variable' => 24.5] as $identifier => $value) {
			self::assertTrue($consumer->consume($this->message(
				Queue\Messages\StoreDevicePropertyState::class,
				[
					'connector' => self::CONNECTOR_ID,
					'device' => $this->ids['device']->toString(),
					'property' => $this->ids['device_' . $identifier]->toString(),
					'value' => $value,
				],
			)));
		}

		self::assertSame(
			[
				['set', 'device', 'dynamic', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 21.5], 'com.fastybird.homekit-connector'],
				['setValidState', 'device', 'dynamic', true, 'com.fastybird.homekit-connector'],
				['write', 'device', 'mapped_dynamic', [DevicesStates\Property::EXPECTED_VALUE_FIELD => 22.5], 'com.fastybird.homekit-connector'],
			],
			$this->calls,
		);

		$this->getEntityManager()->clear();

		self::assertSame(23.5, $this->variable($this->ids['device_variable'], 'device')->getValue());
		self::assertSame(24.5, $this->variable($this->ids['device_variable_parent'], 'device')->getValue());
		self::assertSame(1.0, $this->variable($this->ids['channel_variable'], 'channel')->getValue());
		self::assertSame(1.0, $this->variable($this->ids['channel_variable_parent'], 'channel')->getValue());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	public function testStoreChannelPropertyState(): void
	{
		$this->recordStates();
		$this->createEntities();

		$consumer = $this->getContainer()->getByType(Queue\Consumers\StoreChannelPropertyState::class);

		foreach (['dynamic' => 31.5, 'mapped_dynamic' => 32.5, 'variable' => 33.5, 'mapped_variable' => 34.5] as $identifier => $value) {
			self::assertTrue($consumer->consume($this->message(
				Queue\Messages\StoreChannelPropertyState::class,
				[
					'connector' => self::CONNECTOR_ID,
					'device' => $this->ids['device']->toString(),
					'channel' => $this->ids['channel']->toString(),
					'property' => $this->ids['channel_' . $identifier]->toString(),
					'value' => $value,
				],
			)));
		}

		self::assertSame(
			[
				['set', 'channel', 'dynamic', [DevicesStates\Property::ACTUAL_VALUE_FIELD => 31.5], 'com.fastybird.homekit-connector'],
				['setValidState', 'channel', 'dynamic', true, 'com.fastybird.homekit-connector'],
				['write', 'channel', 'mapped_dynamic', [DevicesStates\Property::EXPECTED_VALUE_FIELD => 32.5], 'com.fastybird.homekit-connector'],
			],
			$this->calls,
		);

		$this->getEntityManager()->clear();

		self::assertSame(33.5, $this->variable($this->ids['channel_variable'], 'channel')->getValue());
		self::assertSame(34.5, $this->variable($this->ids['channel_variable_parent'], 'channel')->getValue());
		self::assertSame(1.0, $this->variable($this->ids['device_variable'], 'device')->getValue());
		self::assertSame(1.0, $this->variable($this->ids['device_variable_parent'], 'device')->getValue());
	}

	/**
	 * The state managers are replaced by recorders of what set(), write() and setValidState() are handed. A
	 * PropertyUpdate is read the way the managers read it on their first line, so the record is the same for it and for
	 * the ArrayHash it replaced.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function recordStates(): void
	{
		$record = fn (string $method, string $owner): Closure => function (
			DevicesDocuments\Property $property,
			Utils\ArrayHash|DevicesStates\PropertyUpdate $data,
			Sources\Source|null $source,
		) use (
			$method,
			$owner,
): Promise\PromiseInterface {
			$data = $data instanceof DevicesStates\PropertyUpdate ? $data->toArrayHash() : $data;

			$this->calls[] = [$method, $owner, $property->getIdentifier(), (array) $data, $source?->value];

			return Promise\resolve(true);
		};

		$recordValid = fn (string $owner): Closure => function (
			DevicesDocuments\Property $property,
			bool $state,
			Sources\Source|null $source,
		) use ($owner): Promise\PromiseInterface {
			$this->calls[] = ['setValidState', $owner, $property->getIdentifier(), $state, $source?->value];

			return Promise\resolve(true);
		};

		$this->mockContainerService(
			DevicesModels\States\Async\DevicePropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\DevicePropertiesManager::class,
				static function (MockObject $mock) use ($record, $recordValid): void {
					$mock->method('set')->willReturnCallback($record('set', 'device'));
					$mock->method('write')->willReturnCallback($record('write', 'device'));
					$mock->method('setValidState')->willReturnCallback($recordValid('device'));
				},
			),
		);

		$this->mockContainerService(
			DevicesModels\States\Async\ChannelPropertiesManager::class,
			$this->mockOf(
				DevicesModels\States\Async\ChannelPropertiesManager::class,
				static function (MockObject $mock) use ($record, $recordValid): void {
					$mock->method('set')->willReturnCallback($record('set', 'channel'));
					$mock->method('write')->willReturnCallback($record('write', 'channel'));
					$mock->method('setValidState')->willReturnCallback($recordValid('channel'));
				},
			),
		);
	}

	/**
	 * A HomeKit device with a channel, and on each of them a dynamic property, a variable one, and a property mapped to
	 * each of two more
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function createEntities(): void
	{
		$connector = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		)->find(
			Uuid\Uuid::fromString(self::CONNECTOR_ID),
			HomeKitEntities\Connectors\Connector::class,
		);
		assert($connector instanceof HomeKitEntities\Connectors\Connector);

		$device = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesManager::class)->create([
			'entity' => HomeKitEntities\Devices\Device::class,
			'connector' => $connector,
			'identifier' => 'store-property-state',
		]);
		$this->ids['device'] = $device->getId();

		$channel = $this->getContainer()->getByType(DevicesModels\Entities\Channels\ChannelsManager::class)->create([
			'entity' => HomeKitEntities\Channels\Generic::class,
			'device' => $device,
			'identifier' => 'store_property_state',
		]);
		$this->ids['channel'] = $channel->getId();

		$devicesProperties = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\Properties\PropertiesManager::class,
		);
		$channelsProperties = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\Properties\PropertiesManager::class,
		);

		foreach (['device' => [$devicesProperties, $device], 'channel' => [$channelsProperties, $channel]] as $owner => [$manager, $entity]) {
			$created = [];

			foreach (['dynamic', 'dynamic_parent'] as $identifier) {
				$created[$identifier] = $manager->create([
					'entity' => $owner === 'device'
						? DevicesEntities\Devices\Properties\Dynamic::class
						: DevicesEntities\Channels\Properties\Dynamic::class,
					$owner => $entity,
					'identifier' => $identifier,
					'dataType' => ValuesTypes\DataType::FLOAT,
					'settable' => true,
					'queryable' => true,
				]);
			}

			foreach (['variable', 'variable_parent'] as $identifier) {
				$created[$identifier] = $manager->create([
					'entity' => $owner === 'device'
						? DevicesEntities\Devices\Properties\Variable::class
						: DevicesEntities\Channels\Properties\Variable::class,
					$owner => $entity,
					'identifier' => $identifier,
					'dataType' => ValuesTypes\DataType::FLOAT,
					'value' => 1.0,
				]);
			}

			// only a property mapped to a dynamic one has the settable and queryable flags
			foreach (['mapped_dynamic' => 'dynamic_parent', 'mapped_variable' => 'variable_parent'] as $identifier => $parent) {
				$created[$identifier] = $manager->create(array_merge(
					[
						'entity' => $owner === 'device'
							? DevicesEntities\Devices\Properties\Mapped::class
							: DevicesEntities\Channels\Properties\Mapped::class,
						$owner => $entity,
						'parent' => $created[$parent],
						'identifier' => $identifier,
						'dataType' => ValuesTypes\DataType::FLOAT,
					],
					$parent === 'dynamic_parent' ? ['settable' => true, 'queryable' => true] : [],
				));
			}

			foreach ($created as $identifier => $property) {
				$this->ids[$owner . '_' . $identifier] = $property->getId();
			}
		}

		$this->getEntityManager()->clear();
	}

	/**
	 * @param class-string<Queue\Messages\Message> $class
	 * @param array<string, mixed> $data
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Throwable
	 */
	private function message(string $class, array $data): Queue\Messages\Message
	{
		return $this->getContainer()->getByType(Helpers\MessageBuilder::class)->create($class, $data);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function variable(
		Uuid\UuidInterface $id,
		string $owner,
	): DevicesEntities\Devices\Properties\Variable|DevicesEntities\Channels\Properties\Variable
	{
		$property = $owner === 'device'
			? $this->getContainer()->getByType(
				DevicesModels\Entities\Devices\Properties\PropertiesRepository::class,
			)->find($id, DevicesEntities\Devices\Properties\Variable::class)
			: $this->getContainer()->getByType(
				DevicesModels\Entities\Channels\Properties\PropertiesRepository::class,
			)->find($id, DevicesEntities\Channels\Properties\Variable::class);

		self::assertNotNull($property);

		return $property;
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
