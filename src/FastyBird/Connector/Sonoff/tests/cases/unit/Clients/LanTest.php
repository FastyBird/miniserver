<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Clients;

use ArrayObject;
use BadMethodCallException;
use Closure;
use Error;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Psr\Log;
use Ramsey\Uuid;
use React\EventLoop;
use ReflectionException;
use RuntimeException;
use Stringable;
use function assert;
use function count;
use function serialize;

final class LanTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string DEVICE_ID = '1000a1b2c3';

	private const string CONNECTOR_ID = '63a28696-cbea-4757-84d7-156e52ade4d3';

	private const string DEVICE_KEY = 'secret-device-key';

	private const string SUB_DEVICE_ID = '1000d4e5f6';

	/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> */
	private ArrayObject $logs;

	/** @var array<string> */
	private array $calls = [];

	/** @var (Closure(API\Messages\Message): void)|null */
	private Closure|null $listener = null;

	private Queue\Queue|null $queue = null;

	/** @var array<string> */
	private array $queries = [];

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->logs = new ArrayObject();
		$this->calls = [];
		$this->listener = null;
		$this->queue = null;
		$this->queries = [];
	}

	/**
	 * The API is given the keys before it is connected and before anything listens to it, so that nothing it
	 * announces is checked against keys that are not there yet
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testKeysAreRegisteredBeforeTheApiIsConnectedAndListenedTo(): void
	{
		$this->connectClient(self::DEVICE_KEY, self::DEVICE_KEY);

		self::assertSame(['registerDeviceKey', 'connect', 'onMessage'], $this->calls);
	}

	/**
	 * The keys the API holds can be behind the configuration, whatever the reason: the device was given its key after
	 * the client connected, or the key could not be read then. The configuration decides.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testPlainDataForADeviceWhoseKeyTheApiDoesNotHoldIsDroppedAtTheClient(): void
	{
		// No key could be read when the client connected, so none was registered; one is configured by now
		$this->connectClient(null, self::DEVICE_KEY);

		self::assertSame(['connect', 'onMessage'], $this->calls);

		$this->announce(false, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		self::assertTrue($this->getQueue()->isEmpty());
		self::assertCount(1, $this->logs);
		self::assertSame(Log\LogLevel::WARNING, $this->logs->getArrayCopy()[0]['level']);
		self::assertStringNotContainsString(self::DEVICE_KEY, serialize($this->logs->getArrayCopy()));
		self::assertStringNotContainsString('"switch"', serialize($this->logs->getArrayCopy()));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testPlainDataForADeviceWithAKeyIsDroppedAtTheClient(): void
	{
		$this->connectClient(self::DEVICE_KEY, self::DEVICE_KEY);

		$this->announce(false, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		self::assertTrue($this->getQueue()->isEmpty());
		self::assertCount(1, $this->logs);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testPlainDataForADeviceWithoutAKeyIsHandled(): void
	{
		$this->connectClient(null, null);

		$this->announce(false, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		// The device is connected, and its state is stored
		self::assertSame(
			[Queue\Messages\StoreDeviceConnectionState::class, Queue\Messages\StoreParametersStates::class],
			$this->drainQueue(),
		);
		self::assertCount(0, $this->logs);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testEncryptedDataForADeviceWithAKeyIsHandled(): void
	{
		$this->connectClient(self::DEVICE_KEY, self::DEVICE_KEY);

		$this->announce(true, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		self::assertSame(
			[Queue\Messages\StoreDeviceConnectionState::class, Queue\Messages\StoreParametersStates::class],
			$this->drainQueue(),
		);
		self::assertCount(0, $this->logs);
	}

	/**
	 * The lookup of a device by its identifier ignores case, which is looser than the id of the announcement, so it can
	 * return a device that is not exactly the one announced; that device is left alone
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testEventIsNotAppliedToADeviceWithAnotherIdentifier(): void
	{
		$this->connectClient(null, null, '1000a1b2c');

		$this->announce(false, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		self::assertTrue($this->getQueue()->isEmpty());
	}

	/**
	 * An announced id is looked up only among the devices of the client's own connector
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testAnnouncedDeviceIsLookedUpAmongTheDevicesOfTheConnector(): void
	{
		$this->connectClient(null, null);

		$this->announce(false, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		self::assertCount(1, $this->queries);
		self::assertStringContainsString(self::CONNECTOR_ID, $this->queries[0]);
	}

	/**
	 * The data of an announcement that names a sub-device is the sub-device's state, so it is the sub-device's key
	 * that decides whether plain data is taken, whatever the announcing device has
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testPlainDataForASubDeviceWithAKeyIsDropped(): void
	{
		$this->connectClient(null, null, self::DEVICE_ID, self::DEVICE_KEY);

		$this->announce(false, ['subDevId' => self::SUB_DEVICE_ID, 'switch' => 'on', 'fwVersion' => '3.5.0']);

		// The announcing device, which has no key, is connected; the sub-device takes nothing
		self::assertSame([Queue\Messages\StoreDeviceConnectionState::class], $this->drainQueue());
		self::assertCount(1, $this->logs);
		self::assertSame(Log\LogLevel::WARNING, $this->logs->getArrayCopy()[0]['level']);
		self::assertSame(
			['id' => '6b1c8b7e-3f0a-4c55-9d1e-2f9a4b1c7d20'],
			$this->logs->getArrayCopy()[0]['context']['device'] ?? null,
		);
		self::assertStringNotContainsString(self::DEVICE_KEY, serialize($this->logs->getArrayCopy()));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testEncryptedDataForASubDeviceWithAKeyIsHandled(): void
	{
		$this->connectClient(self::DEVICE_KEY, self::DEVICE_KEY, self::DEVICE_ID, self::DEVICE_KEY);

		$this->announce(true, ['subDevId' => self::SUB_DEVICE_ID, 'switch' => 'on', 'fwVersion' => '3.5.0']);

		self::assertSame(
			[Queue\Messages\StoreDeviceConnectionState::class, Queue\Messages\StoreParametersStates::class],
			$this->drainQueue(),
		);
		self::assertCount(0, $this->logs);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\Runtime
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	public function testPlainDataForASubDeviceWithoutAKeyIsHandled(): void
	{
		$this->connectClient(null, null);

		$this->announce(false, ['subDevId' => self::SUB_DEVICE_ID, 'switch' => 'on', 'fwVersion' => '3.5.0']);

		self::assertSame(
			[Queue\Messages\StoreDeviceConnectionState::class, Queue\Messages\StoreParametersStates::class],
			$this->drainQueue(),
		);
		self::assertCount(0, $this->logs);
	}

	/**
	 * @param string|null $keyAtConnect the key the configuration gives when the client connects
	 * @param string|null $keyLater the key the configuration gives when a message arrives
	 * @param string $identifier the identifier of the device the lookup finds
	 * @param string|null $subDeviceKey the key the configuration gives the sub-device the data of an announcement names
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	private function connectClient(
		string|null $keyAtConnect,
		string|null $keyLater,
		string $identifier = self::DEVICE_ID,
		string|null $subDeviceKey = null,
	): void
	{
		$connectorId = Uuid\Uuid::fromString(self::CONNECTOR_ID);

		$subDevice = $this->mockOf(
			Documents\Devices\Device::class,
			static function (MockObject $mock) use ($connectorId): void {
				$mock->method('getId')->willReturn(Uuid\Uuid::fromString('6b1c8b7e-3f0a-4c55-9d1e-2f9a4b1c7d20'));
				$mock->method('getConnector')->willReturn($connectorId);
				$mock->method('getIdentifier')->willReturn(self::SUB_DEVICE_ID);
			},
		);

		$device = $this->mockOf(
			Documents\Devices\Device::class,
			static function (MockObject $mock) use ($connectorId, $identifier): void {
				$mock->method('getId')->willReturn(Uuid\Uuid::fromString('4ac3e4d1-6d0a-4c47-9d32-a0d3c1c6f0a1'));
				$mock->method('getConnector')->willReturn($connectorId);
				$mock->method('getIdentifier')->willReturn($identifier);
			},
		);

		$connector = $this->mockOf(
			Documents\Connectors\Connector::class,
			static function (MockObject $mock) use ($connectorId): void {
				$mock->method('getId')->willReturn($connectorId);
			},
		);

		$repository = $this->mockOf(
			DevicesModels\Configuration\Devices\Repository::class,
			function (MockObject $mock) use ($device, $subDevice): void {
				$mock->method('findAllBy')->willReturn([$device]);
				$mock->method('findOneBy')->willReturnCallback(
					function (DevicesQueries\Configuration\FindDevices $query) use ($device, $subDevice): Documents\Devices\Device {
						$this->queries[] = $query->toString();

						// The announced device is looked up first, the sub-device its data names after it
						return count($this->queries) === 1 ? $device : $subDevice;
					},
				);
			},
		);

		$keyRead = 0;

		$deviceHelper = $this->mockOf(
			Helpers\Device::class,
			static function (MockObject $mock) use (&$keyRead, $keyAtConnect, $keyLater, $subDevice, $subDeviceKey): void {
				$mock->method('getDeviceKey')->willReturnCallback(
					static function (Documents\Devices\Device $of) use (&$keyRead, $keyAtConnect, $keyLater, $subDevice, $subDeviceKey): string|null {
						if ($of === $subDevice) {
							return $subDeviceKey;
						}

						// The first read is the one at the connect, every other is a message being handled
						return $keyRead++ === 0 ? $keyAtConnect : $keyLater;
					},
				);
			},
		);

		$api = $this->mockOf(API\LanApi::class, function (MockObject $mock): void {
			$mock->method('registerDeviceKey')->willReturnCallback(function (): void {
				$this->calls[] = 'registerDeviceKey';
			});
			$mock->method('connect')->willReturnCallback(function (): void {
				$this->calls[] = 'connect';
			});
			$mock->method('onMessage')->willReturnCallback(function (Closure $listener): void {
				$this->calls[] = 'onMessage';
				$this->listener = $listener;
			});
		});

		$connectionManager = $this->mockOf(
			API\ConnectionManager::class,
			static function (MockObject $mock) use ($api): void {
				$mock->method('getLanConnection')->willReturn($api);
			},
		);

		$logs = $this->logs;

		$logger = new Sonoff\Logger(new class ($logs) extends Log\AbstractLogger {

			/**
			 * @param ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs
			 */
			public function __construct(private readonly ArrayObject $logs)
			{
			}

			/**
			 * @param array<mixed> $context
			 */
			// phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint

			#[Override]
			public function log($level, string|Stringable $message, array $context = []): void
			{
				$this->logs[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
			}

		});

		$client = new Clients\Lan(
			$deviceHelper,
			$this->mockOf(DevicesUtilities\DeviceConnection::class),
			$this->createMock(ClockInterface::class),
			$this->createMock(EventLoop\LoopInterface::class),
			$connector,
			true,
			$connectionManager,
			$this->container->getByType(Helpers\MessageBuilder::class),
			$this->getQueue(),
			$logger,
			$repository,
		);

		$client->connect();
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws SonoffExceptions\Runtime
	 * @throws Nette\DI\MissingServiceException
	 */
	private function announce(bool $encrypted, array $data): void
	{
		self::assertNotNull($this->listener);

		($this->listener)(
			$this->container->getByType(Helpers\MessageBuilder::class)->create(
				API\Messages\Response\Lan\DeviceEvent::class,
				[
					'id' => self::DEVICE_ID,
					'ip_address' => '10.0.0.7',
					'domain' => 'eWeLink_' . self::DEVICE_ID . '.local',
					'port' => 8_081,
					'type' => 'plug',
					'seq' => '7',
					'iv' => null,
					'encrypt' => $encrypted,
					'data' => $data,
				],
			),
		);
	}

	/**
	 * The classes the client works with are final, which PHPStan does not let a mock of one be typed as, so the mock is
	 * handed over as the class it stands in for
	 *
	 * @template T of object
	 *
	 * @param class-string<T> $class
	 * @param (Closure(MockObject): void)|null $configure
	 *
	 * @return T
	 */
	private function mockOf(string $class, Closure|null $configure = null): object
	{
		$mock = $this->createMock($class);

		assert($mock instanceof $class);

		if ($configure !== null) {
			$configure($mock);
		}

		return $mock;
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	private function getQueue(): Queue\Queue
	{
		return $this->queue ??= $this->container->getByType(Queue\Queue::class);
	}

	/**
	 * @return array<class-string>
	 *
	 * @throws Nette\DI\MissingServiceException
	 */
	private function drainQueue(): array
	{
		$classes = [];

		while (($message = $this->getQueue()->dequeue()) !== false) {
			$classes[] = $message::class;
		}

		return $classes;
	}

}
