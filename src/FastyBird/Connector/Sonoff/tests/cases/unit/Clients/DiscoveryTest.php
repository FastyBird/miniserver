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
use Nette;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
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

final class DiscoveryTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string KEYED_DEVICE_ID = '1000a1b2c3';

	private const string PLAIN_DEVICE_ID = '1000d4e5f6';

	private const string DEVICE_KEY = 'secret-device-key';

	/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> */
	private ArrayObject $logs;

	/** @var array<int, array{string, array<mixed>}> */
	private array $calls = [];

	/** @var (Closure(API\Messages\Message): void)|null */
	private Closure|null $listener = null;

	private Clients\Discovery|null $discovery = null;

	#[Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->logs = new ArrayObject();
		$this->calls = [];
		$this->listener = null;
		$this->discovery = null;
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
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Throwable
	 */
	private function discover(): void
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
			function (MockObject $mock): void {
				$mock->method('getDevices')->willReturn([
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
			static function (MockObject $mock): void {
				$mock->method('getClientMode')->willReturn(Types\ClientMode::LAN);
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
			$this->createMock(EventLoop\LoopInterface::class),
		);

		// The cloud answers at once; the LAN search then waits for a timer that never fires, so the test speaks for
		// the network
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
