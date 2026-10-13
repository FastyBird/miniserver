<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\API;

use Closure;
use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Core\EventLoop as CoreEventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use Nette;
use Override;
use React\Socket;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use function array_map;
use function assert;
use function crc32;
use function func_get_args;
use function pack;

final class LocalApiTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string DEVICE_IDENTIFIER = 'bf0123456789abcdef';

	/** The port a Tuya device listens on, which the client connects to */
	private const int DEVICE_PORT = 6_668;

	private API\LocalApi|null $client = null;

	private Tests\Support\FakeDevice|null $device = null;

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\LocalApi::class))->getProperties(ReflectionProperty::IS_PUBLIC);

		self::assertSame([], $properties);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testConnectionNotifiesConnectedListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onConnected($this->record('first', $events));
		$client->onConnected($this->record('second', $events));

		$this->connect($client);

		self::assertSame([['first', []], ['second', []]], $events);
		self::assertTrue($client->isConnected());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testClosedConnectionNotifiesDisconnectedListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onDisconnected($this->record('first', $events));
		$client->onDisconnected($this->record('second', $events));

		$this->connect($client);

		$this->device()->closeConnection();

		$this->runUntil(static function () use (&$events): bool {
			return $events !== [];
		});

		self::assertSame([['first', []], ['second', []]], $events);
		self::assertFalse($client->isConnected());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testConnectionErrorNotifiesErrorThenLostListeners(): void
	{
		$events = [];
		$error = new RuntimeException('Connection broke');

		$client = $this->createClient();

		$client->onError($this->record('error-first', $events));
		$client->onError($this->record('error-second', $events));
		$client->onLost($this->record('lost-first', $events));
		$client->onLost($this->record('lost-second', $events));

		$this->connect($client);

		// A socket reports an error only when the operating system does, so the test raises it on the connection
		$connection = (new ReflectionProperty($client, 'connection'))->getValue($client);
		assert($connection instanceof Socket\ConnectionInterface);

		$connection->emit('error', [$error]);

		self::assertSame(
			['error-first', 'error-second', 'lost-first', 'lost-second'],
			array_map(static fn (array $event): string => $event[0], $events),
		);

		foreach ([$events[0], $events[1]] as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Throwable::class, $event[1][0]);
			self::assertSame($error, $event[1][0]->getPrevious());
		}

		self::assertSame([], $events[2][1]);
		self::assertSame([], $events[3][1]);
		self::assertNotNull($client->getLost());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testDeviceMessageNotifiesMessageListenersInOrderWithTheMessage(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		$this->connect($client);

		$this->device()->send($this->buildHeartbeatReply(7));

		$this->runUntil(static function () use (&$events): bool {
			return $events !== [];
		});

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(API\Messages\Response\LocalDeviceMessage::class, $event[1][0]);
			self::assertSame(self::DEVICE_IDENTIFIER, $event[1][0]->getIdentifier());
			self::assertSame(Types\LocalDeviceCommand::HEART_BEAT, $event[1][0]->getCommand());
			self::assertSame(7, $event[1][0]->getSequence());
		}

		self::assertNotNull($client->getLastHeartbeat());
	}

	#[Override]
	protected function tearDown(): void
	{
		// Stops the heartbeat timer and closes the socket and the device, which the loop would keep watching for ever
		$this->client?->disconnect();
		$this->client = null;

		$this->device?->close();
		$this->device = null;

		parent::tearDown();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function createClient(): API\LocalApi
	{
		$this->device = new Tests\Support\FakeDevice($this->loop(), self::DEVICE_PORT);

		$this->client = $this->container->getByType(API\LocalApiFactory::class)->create(
			self::DEVICE_IDENTIFIER,
			null,
			null,
			'0123456789abcdef',
			'127.0.0.1',
			Types\DeviceProtocolVersion::V33,
		);

		return $this->client;
	}

	/**
	 * @throws RuntimeException
	 */
	private function connect(API\LocalApi $client): void
	{
		$client->connect();

		$this->runUntil(fn (): bool => $client->isConnected() && $this->device()->hasClient());
	}

	private function device(): Tests\Support\FakeDevice
	{
		assert($this->device instanceof Tests\Support\FakeDevice);

		return $this->device;
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	private function loop(): CoreEventLoop\Wrapper
	{
		return $this->container->getByType(CoreEventLoop\Wrapper::class);
	}

	/**
	 * Runs the container's loop until the condition holds, and fails when it does not within a few seconds
	 *
	 * @param Closure(): bool $condition
	 *
	 * @throws RuntimeException
	 */
	private function runUntil(Closure $condition): void
	{
		$loop = $this->loop();

		$guard = $loop->addTimer(5.0, static function () use ($loop): void {
			$loop->stop();
		});
		$poll = $loop->addPeriodicTimer(0.01, static function () use ($loop, $condition): void {
			if ($condition()) {
				$loop->stop();
			}
		});

		$loop->run();

		$loop->cancelTimer($guard);
		$loop->cancelTimer($poll);

		self::assertTrue($condition(), 'The condition did not hold before the guard timer fired');
	}

	/**
	 * The frame a device answers a heartbeat with: the 0x000055aa prefix, sequence number, command, length, return
	 * code, the CRC of all that, and the 0x0000aa55 suffix
	 */
	private function buildHeartbeatReply(int $sequence): string
	{
		$frame = pack('N', 0x000055AA)
			. pack('N', $sequence)
			. pack('N', Types\LocalDeviceCommand::HEART_BEAT->value)
			. pack('N', 12)
			. pack('N', 0);

		return $frame . pack('N', crc32($frame)) . pack('N', 0x0000AA55);
	}

	/**
	 * @param array<int, array{string, array<mixed>}> $events
	 *
	 * @return Closure(): void
	 */
	private function record(string $name, array &$events): Closure
	{
		return static function () use ($name, &$events): void {
			$events[] = [$name, func_get_args()];
		};
	}

}
