<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\Clients;

use ArrayObject;
use Closure;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Clients;
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Events as DevicesEvents;
use Nette;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use Ramsey\Uuid;
use React\EventLoop;
use React\Promise;
use ReflectionException;
use ReflectionProperty;
use Stringable;
use Throwable;
use function array_filter;
use function array_keys;
use function array_values;
use function assert;
use function is_array;
use function strtoupper;

final class DiscoveryTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string KEYED_DEVICE_ID = '1000a1b2c3';

	private const string PLAIN_DEVICE_ID = '1000d4e5f6';

	private const string DEVICE_KEY = 'secret-device-key';

	private const string OTHER_KEYED_DEVICE_ID = '1000a7b8c9';

	/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> */
	private ArrayObject $logs;

	/** @var array<int, array{string, array<mixed>}> */
	private array $calls = [];

	/** @var (Closure(API\Messages\Message): void)|null */
	private Closure|null $listener = null;

	private Clients\Discovery|null $discovery = null;

	/** @var (Closure(): mixed)|null */
	private Closure|null $lanSearchTimeout = null;

	/** @var array{float, Closure(): mixed}|null */
	private array|null $storingCheck = null;

	/** @var ArrayObject<int, string|null> */
	private ArrayObject $terminations;

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->logs = new ArrayObject();
		$this->calls = [];
		$this->listener = null;
		$this->discovery = null;
		$this->lanSearchTimeout = null;
		$this->storingCheck = null;
		$this->terminations = new ArrayObject();
	}

	/**
	 * The keys the cloud returns are given to the LAN API before it listens, so that it decrypts the announcements of
	 * the devices that have one
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testKeysFromTheCloudAreRegisteredBeforeTheLanApiListens(): void
	{
		$this->discover();

		self::assertSame(
			[
				['registerDeviceKey', [self::KEYED_DEVICE_ID, self::DEVICE_KEY]],
				['onMessage', []],
				['connect', []],
			],
			$this->calls,
		);
	}

	/**
	 * For a device with a key, only an announcement whose data decrypted with that key may set its address; a device
	 * without a key has nothing to check its announcement against
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws SonoffExceptions\Runtime
	 * @throws Throwable
	 */
	public function testAddressOfADeviceWithAKeyIsTakenOnlyFromDataDecryptedWithIt(): void
	{
		$this->discover();

		// Plain data, and an announcement the API could not decrypt, for the device with a key
		$this->announce(self::KEYED_DEVICE_ID, '10.0.0.66', false, ['switch' => 'on', 'fwVersion' => '3.5.0']);
		$this->announce(self::KEYED_DEVICE_ID, '10.0.0.67', true, null);

		self::assertSame([], $this->foundLocalDevices());
		self::assertCount(2, $this->warnings());

		// Data decrypted with its key
		$this->announce(self::KEYED_DEVICE_ID, '10.0.0.7', true, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		// A device without a key
		$this->announce(self::PLAIN_DEVICE_ID, '10.0.0.8', false, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		$found = $this->foundLocalDevices();

		self::assertSame([self::KEYED_DEVICE_ID, self::PLAIN_DEVICE_ID], array_keys($found));
		self::assertSame('10.0.0.7', $found[self::KEYED_DEVICE_ID]['ipAddress'] ?? null);
		self::assertSame('10.0.0.8', $found[self::PLAIN_DEVICE_ID]['ipAddress'] ?? null);
		self::assertCount(2, $this->warnings());
	}

	/**
	 * The devices the cloud knows are stored once the search of the network is over, a device it found with the
	 * address it was found at, and a device it did not find without one
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\Runtime
	 * @throws Throwable
	 */
	public function testDeviceFoundOnTheLanIsStoredWithTheAddressItWasFoundAt(): void
	{
		$this->discover([
			$this->storableCloudDevice(self::KEYED_DEVICE_ID),
			$this->storableCloudDevice(self::OTHER_KEYED_DEVICE_ID),
		]);

		$this->announce(self::KEYED_DEVICE_ID, '10.0.0.7', true, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		$this->endLanSearch();

		self::assertSame(
			[
				self::KEYED_DEVICE_ID => ['10.0.0.7', 'eWeLink_' . self::KEYED_DEVICE_ID . '.local', 8_081],
				self::OTHER_KEYED_DEVICE_ID => [null, null, null],
			],
			$this->storedAddresses(),
		);
	}

	/**
	 * An announcement that did not decrypt with the key of a device does not give the device its address, not even
	 * when the device is stored at the end of the search
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\Runtime
	 * @throws Throwable
	 */
	public function testAddressOfAnAnnouncementNotDecryptedWithTheKeyIsNotStored(): void
	{
		$this->discover([$this->storableCloudDevice(self::KEYED_DEVICE_ID)]);

		$this->announce(self::KEYED_DEVICE_ID, '10.0.0.66', false, ['switch' => 'on', 'fwVersion' => '3.5.0']);
		$this->announce(self::KEYED_DEVICE_ID, '10.0.0.67', true, null);

		$this->endLanSearch();

		self::assertSame([self::KEYED_DEVICE_ID => [null, null, null]], $this->storedAddresses());
	}

	/**
	 * The connector stops shortly after it is asked to terminate and does not wait for its queue, so discovery asks
	 * for that only once the devices it found are stored
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testDiscoveryFinishesOnceTheFoundDevicesAreStored(): void
	{
		$this->discover([
			$this->storableCloudDevice(self::KEYED_DEVICE_ID),
			$this->storableCloudDevice(self::OTHER_KEYED_DEVICE_ID),
		]);

		$this->endLanSearch();

		$queue = $this->container->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());
		self::assertSame([], $this->terminations->getArrayCopy());

		$this->checkStoring();

		self::assertSame([], $this->terminations->getArrayCopy());

		// The connector consumes one message after the other
		self::assertNotFalse($queue->dequeue());

		$this->checkStoring();

		self::assertSame([], $this->terminations->getArrayCopy());

		self::assertNotFalse($queue->dequeue());

		$this->checkStoring();

		self::assertSame(['Devices discovery finished'], $this->terminations->getArrayCopy());
		self::assertSame([], $this->warnings());
	}

	/**
	 * Discovery does not wait for ever: after a minute it finishes with the devices not yet stored, and says so
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testDiscoveryFinishesAfterAMinuteWhenTheFoundDevicesAreNotStored(): void
	{
		$this->discover([$this->storableCloudDevice(self::KEYED_DEVICE_ID)]);

		$this->endLanSearch();

		self::assertNotNull($this->storingCheck);

		[$interval] = $this->storingCheck;

		$checks = 0;

		while ($this->terminations->count() === 0 && $checks < 1_000) {
			$this->checkStoring();

			$checks++;
		}

		self::assertEqualsWithDelta(60.0, $checks * $interval, 0.001);
		self::assertSame(['Devices discovery finished'], $this->terminations->getArrayCopy());
		self::assertCount(1, $this->warnings());
		self::assertFalse($this->container->getByType(Queue\Queue::class)->isEmpty());
	}

	/**
	 * Without the LAN there is no search: the devices are stored straight away, without an address, and discovery
	 * then finishes
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	public function testCloudOnlyDiscoveryStoresTheDevicesWithoutAnAddressAndFinishes(): void
	{
		$this->discover([$this->storableCloudDevice(self::KEYED_DEVICE_ID)], Types\ClientMode::CLOUD);

		self::assertNull($this->lanSearchTimeout);
		self::assertNotContains(['connect', []], $this->calls);

		self::assertSame([self::KEYED_DEVICE_ID => [null, null, null]], $this->storedAddresses());

		$this->checkStoring();

		self::assertSame(['Devices discovery finished'], $this->terminations->getArrayCopy());
	}

	/**
	 * The cloud and the device may write the id in another case; it is the same device
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\Runtime
	 * @throws Throwable
	 */
	public function testDeviceTheCloudNamesInUpperCaseTakesTheAddressAnnouncedInLowerCase(): void
	{
		$this->discover([$this->storableCloudDevice(strtoupper(self::KEYED_DEVICE_ID))]);

		$this->announce(self::KEYED_DEVICE_ID, '10.0.0.7', true, ['switch' => 'on', 'fwVersion' => '3.5.0']);

		$this->endLanSearch();

		self::assertSame(
			[
				strtoupper(self::KEYED_DEVICE_ID) => [
					'10.0.0.7',
					'eWeLink_' . self::KEYED_DEVICE_ID . '.local',
					8_081,
				],
			],
			$this->storedAddresses(),
		);
	}

	/**
	 * @param array<API\Messages\Response\Cloud\Device>|null $devices
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	private function discover(
		array|null $devices = null,
		Types\ClientMode $mode = Types\ClientMode::LAN,
	): void
	{
		$connector = $this->mockOf(
			Documents\Connectors\Connector::class,
			static function (MockObject $mock): void {
				$mock->method('getId')->willReturn(Uuid\Uuid::fromString('63a28696-cbea-4757-84d7-156e52ade4d3'));
			},
		);

		$lanApi = $this->mockOf(API\LanApi::class, function (MockObject $mock): void {
			$mock->method('registerDeviceKey')->willReturnCallback(function (string $id, string $key): void {
				$this->calls[] = ['registerDeviceKey', [$id, $key]];
			});
			$mock->method('onMessage')->willReturnCallback(function (Closure $listener): void {
				$this->calls[] = ['onMessage', []];
				$this->listener = $listener;
			});
			$mock->method('connect')->willReturnCallback(function (): void {
				$this->calls[] = ['connect', []];
			});
		});

		$lanApiFactory = $this->mockOf(
			API\LanApiFactory::class,
			static function (MockObject $mock) use ($lanApi): void {
				$mock->method('create')->willReturn($lanApi);
			},
		);

		$things = $this->mockOf(
			API\Messages\Response\Cloud\Things::class,
			function (MockObject $mock) use ($devices): void {
				$mock->method('getDevices')->willReturn($devices ?? [
					$this->cloudDevice(self::KEYED_DEVICE_ID, self::DEVICE_KEY),
					$this->cloudDevice(self::PLAIN_DEVICE_ID, ''),
				]);
			},
		);

		$family = $this->mockOf(
			API\Messages\Response\Cloud\Family::class,
			static function (MockObject $mock): void {
				$mock->method('getFamilyId')->willReturn('family');
			},
		);

		$cloudApi = $this->mockOf(
			API\CloudApi::class,
			static function (MockObject $mock) use ($family, $things): void {
				$mock->method('getFamily')->willReturn(Promise\resolve($family));
				$mock->method('getFamilyThings')->willReturn(Promise\resolve($things));
			},
		);

		$cloudApiFactory = $this->mockOf(
			API\CloudApiFactory::class,
			static function (MockObject $mock) use ($cloudApi): void {
				$mock->method('create')->willReturn($cloudApi);
			},
		);

		$connectorHelper = $this->mockOf(
			Helpers\Connector::class,
			static function (MockObject $mock) use ($mode): void {
				$mock->method('getClientMode')->willReturn($mode);
				$mock->method('getRegion')->willReturn(Types\Region::EUROPE);
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

		$discovery = new Clients\Discovery(
			$connector,
			$lanApiFactory,
			$cloudApiFactory,
			$this->container->getByType(Helpers\MessageBuilder::class),
			$connectorHelper,
			$this->container->getByType(Queue\Queue::class),
			$logger,
			$this->mockOf(EventLoop\LoopInterface::class, function (MockObject $mock): void {
				$mock->method('addTimer')->willReturnCallback(
					function (float $interval, Closure $callback): EventLoop\TimerInterface {
						$this->lanSearchTimeout = $callback;

						return $this->createMock(EventLoop\TimerInterface::class);
					},
				);
				$mock->method('addPeriodicTimer')->willReturnCallback(
					function (float $interval, Closure $callback): EventLoop\TimerInterface {
						$this->storingCheck = [$interval, $callback];

						return $this->createMock(EventLoop\TimerInterface::class);
					},
				);
			}),
			new class ($this->terminations) implements PsrEventDispatcher\EventDispatcherInterface {

				/**
				 * @param ArrayObject<int, string|null> $terminations
				 */
				public function __construct(private readonly ArrayObject $terminations)
				{
				}

				#[Override]
				public function dispatch(object $event): object
				{
					if ($event instanceof DevicesEvents\TerminateConnector) {
						$this->terminations[] = $event->getReason();
					}

					return $event;
				}

			},
		);

		// The cloud answers at once; the LAN search then waits for its timeout, which fires only when the test ends
		// the search, so the test speaks for the network
		$discovery->discover();

		$this->discovery = $discovery;
	}

	/**
	 * A cloud device whose UIID has no mapping, so that only its id and key take part in the test
	 */
	private function cloudDevice(string $id, string $key): API\Messages\Response\Cloud\Device
	{
		return $this->mockOf(
			API\Messages\Response\Cloud\Device::class,
			static function (MockObject $mock) use ($id, $key): void {
				$mock->method('getDeviceId')->willReturn($id);
				$mock->method('getDeviceKey')->willReturn($key);
			},
		);
	}

	/**
	 * A cloud device with a key and with a UIID that has a mapping, so that discovery stores it
	 */
	private function storableCloudDevice(string $id): API\Messages\Response\Cloud\Device
	{
		$extra = $this->mockOf(
			API\Messages\Response\Cloud\DeviceExtra::class,
			static function (MockObject $mock): void {
				$mock->method('getUiid')->willReturn(1);
				$mock->method('getModel')->willReturn('PSF-BD1-GL');
				$mock->method('getMac')->willReturn('d0:27:00:00:00:01');
			},
		);

		return $this->mockOf(
			API\Messages\Response\Cloud\Device::class,
			static function (MockObject $mock) use ($id, $extra): void {
				$mock->method('getDeviceId')->willReturn($id);
				$mock->method('getDeviceKey')->willReturn(self::DEVICE_KEY);
				$mock->method('getApiKey')->willReturn('api-key');
				$mock->method('getName')->willReturn('Plug ' . $id);
				$mock->method('getBrandName')->willReturn('SONOFF');
				$mock->method('getProductModel')->willReturn('BASICR2');
				$mock->method('getExtra')->willReturn($extra);
			},
		);
	}

	/**
	 * Runs the check the connector's loop runs every interval until the found devices are stored; with nothing to
	 * store, discovery finishes without one
	 */
	private function checkStoring(): void
	{
		if ($this->storingCheck === null) {
			return;
		}

		[, $check] = $this->storingCheck;

		$check();
	}

	private function endLanSearch(): void
	{
		self::assertNotNull($this->lanSearchTimeout);

		($this->lanSearchTimeout)();
	}

	/**
	 * The address of every device discovery stored, by the device id
	 *
	 * @return array<string, array{string|null, string|null, int|null}>
	 *
	 * @throws Nette\DI\MissingServiceException
	 */
	private function storedAddresses(): array
	{
		$queue = $this->container->getByType(Queue\Queue::class);

		$addresses = [];

		while (($message = $queue->dequeue()) !== false) {
			self::assertInstanceOf(Queue\Messages\StoreDevice::class, $message);

			$addresses[$message->getId()] = [$message->getIpAddress(), $message->getDomain(), $message->getPort()];
		}

		return $addresses;
	}

	/**
	 * @param array<string, mixed>|null $data
	 *
	 * @throws SonoffExceptions\Runtime
	 * @throws Nette\DI\MissingServiceException
	 */
	private function announce(string $id, string $ipAddress, bool $encrypted, array|null $data): void
	{
		self::assertNotNull($this->listener);

		($this->listener)(
			$this->container->getByType(Helpers\MessageBuilder::class)->create(
				API\Messages\Response\Lan\DeviceEvent::class,
				[
					'id' => $id,
					'ip_address' => $ipAddress,
					'domain' => 'eWeLink_' . $id . '.local',
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
	 * @return array<string, array<string, string|int>>
	 *
	 * @throws ReflectionException
	 */
	private function foundLocalDevices(): array
	{
		self::assertNotNull($this->discovery);

		$found = (new ReflectionProperty($this->discovery, 'foundLocalDevices'))->getValue($this->discovery);
		assert(is_array($found));

		/** @var array<string, array<string, string|int>> $found */
		return $found;
	}

	/**
	 * @return array<int, array{level: mixed, message: string, context: array<mixed>}>
	 */
	private function warnings(): array
	{
		return array_values(array_filter(
			$this->logs->getArrayCopy(),
			static fn (array $log): bool => $log['level'] === Log\LogLevel::WARNING,
		));
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

}
