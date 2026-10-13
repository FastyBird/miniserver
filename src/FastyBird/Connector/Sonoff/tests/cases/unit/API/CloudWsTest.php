<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\API;

use Closure;
use Error;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Core\EventLoop as CoreEventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use GuzzleHttp\Psr7;
use Nette;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop as ReactEventLoop;
use React\Promise;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use function array_filter;
use function array_key_first;
use function array_map;
use function array_values;
use function func_get_args;

final class CloudWsTest extends Tests\Cases\Unit\BaseTestCase
{

	private Tests\Support\InertEventLoop|null $loop = null;

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\CloudWs::class))->getProperties(ReflectionProperty::IS_PUBLIC);

		self::assertSame([], $properties);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testConnectionNotifiesConnectedListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onConnected($this->record('first', $events));
		$client->onConnected($this->record('second', $events));

		$this->attach($client);

		self::assertSame([['first', []], ['second', []]], $events);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testClosedConnectionNotifiesDisconnectedListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onDisconnected($this->record('first', $events));
		$client->onDisconnected($this->record('second', $events));

		$stream = $this->attach($client);

		$stream->close();

		self::assertSame([['first', []], ['second', []]], $events);
		self::assertFalse($client->isConnected());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testConnectionErrorNotifiesLostThenErrorListeners(): void
	{
		$events = [];
		$error = new RuntimeException('Connection broke');

		$client = $this->createClient();

		$stream = $this->attach($client);

		// Registered once the connection is attached, so only what the connection raises from here on is counted
		$client->onLost($this->record('lost-first', $events));
		$client->onLost($this->record('lost-second', $events));
		$client->onError($this->record('error-first', $events));
		$client->onError($this->record('error-second', $events));

		$stream->emit('error', [$error]);

		self::assertSame(
			['lost-first', 'lost-second', 'error-first', 'error-second'],
			array_map(static fn (array $event): string => $event[0], $events),
		);
		self::assertSame([], $events[0][1]);
		self::assertSame([], $events[1][1]);

		foreach ([$events[2], $events[3]] as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Throwable::class, $event[1][0]);
			self::assertSame($error, $event[1][0]->getPrevious());
		}

		self::assertNotNull($client->getLost());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testDeviceConnectionMessageNotifiesMessageListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		$stream = $this->attach($client);

		$this->push(
			$stream,
			'{"action":"sysmsg","deviceid":"1000a1b2c3","apikey":"a4b2","params":{"online":true},"ts":1700000000}',
		);

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(API\Messages\Response\Sockets\DeviceConnectionStateEvent::class, $event[1][0]);
			self::assertSame('1000a1b2c3', $event[1][0]->getDeviceId());
			self::assertTrue($event[1][0]->getParams()->isOnline());
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testDeviceUpdateMessageNotifiesMessageListenersWithTheDevice(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('message', $events));

		$stream = $this->attach($client);

		$this->push(
			$stream,
			'{"action":"update","error":0,"deviceid":"1000a1b2c3","apikey":"a4b2","params":{"switch":"on"}}',
		);

		self::assertCount(1, $events);
		self::assertSame('message', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(API\Messages\Response\Sockets\DeviceStateEvent::class, $events[0][1][0]);
		self::assertSame('1000a1b2c3', $events[0][1][0]->getDeviceId());
	}

	/**
	 * The handshake is answered within a time limit, and a handshake that fails, by an answer that cannot be
	 * produced or by the time running out, is reported to the error listeners once. The test lets the time run out
	 * for whatever is still waiting.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testFailedHandshakeNotifiesErrorListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onError($this->record('first', $events));
		$client->onError($this->record('second', $events));

		$this->attach($client);

		foreach ($this->loop?->getTimers() ?? [] as $timer) {
			($timer->getCallback())();
		}

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Throwable::class, $event[1][0]);
			self::assertSame('Handshake with Sonoff sockets server failed', $event[1][0]->getMessage());
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testUndecodableMessageNotifiesErrorListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$stream = $this->attach($client);

		// Registered once the connection is attached, so only what the connection raises from here on is counted
		$client->onError($this->record('first', $events));
		$client->onError($this->record('second', $events));

		$this->push($stream, 'this is not json');

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Nette\Utils\JsonException::class, $event[1][0]);
		}
	}

	/**
	 * The handshake request is kept until the server answers it, so the answer, matched by its sequence, completes
	 * the connection
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testHandshakeAnswerCompletesTheConnection(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onError($this->record('error', $events));

		$stream = $this->attach($client);

		self::assertCount(1, $stream->getWritten());
		self::assertFalse($client->isConnected());

		$this->push(
			$stream,
			'{"error":0,"apikey":"api-key","config":{"hb":1,"hbInterval":145},"sequence":"'
				. $this->getPendingSequence($client) . '"}',
		);

		self::assertSame([], $events);
		self::assertTrue($client->isConnected());
		self::assertFalse($client->isConnecting());

		$heartbeats = array_filter(
			$this->loop?->getTimers() ?? [],
			static fn (ReactEventLoop\TimerInterface $timer): bool => $timer->isPeriodic(),
		);

		self::assertCount(1, $heartbeats);
		self::assertSame(145, array_values($heartbeats)[0]->getInterval());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 */
	public function testReadStatesIsAnsweredByTheReplyWithTheSameSequence(): void
	{
		$client = $this->createClient();

		$stream = $this->attach($client);

		$this->push(
			$stream,
			'{"error":0,"apikey":"api-key","config":{"hb":0,"hbInterval":90},"sequence":"'
				. $this->getPendingSequence($client) . '"}',
		);

		$states = [];

		$client->readStates('1000a1b2c3', 'a4b2')->then(static function ($state) use (&$states): void {
			$states[] = $state;
		});

		self::assertCount(2, $stream->getWritten());
		self::assertSame([], $states);

		$this->push(
			$stream,
			'{"error":0,"apikey":"a4b2","deviceid":"1000a1b2c3","sequence":"' . $this->getPendingSequence($client)
				. '","params":{"switch":"on"}}',
		);

		self::assertCount(1, $states);
		self::assertInstanceOf(API\Messages\Response\Sockets\DeviceStateEvent::class, $states[0]);
		self::assertSame('1000a1b2c3', $states[0]->getDeviceId());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	private function createClient(): API\CloudWs
	{
		// The container's loop runs at the end of the process, and a timer the client sets would keep it alive
		$this->loop = new Tests\Support\InertEventLoop();

		$this->mockContainerService(CoreEventLoop\Wrapper::class, $this->loop);

		return $this->container->getByType(API\CloudWsFactory::class)->create(
			'access-token',
			'app-id',
			'api-key',
			Types\Region::EUROPE,
		);
	}

	/**
	 * `connect()` logs in at the dispatch endpoint and opens the WebSocket to the sockets server it names, both in
	 * the cloud. Everything the client raises on its hooks happens once that WebSocket exists, in
	 * `connectionCreated()`, so the test hands the client a WebSocket sitting on a connection it drives.
	 *
	 * @throws ReflectionException
	 */
	private function attach(API\CloudWs $client): Tests\Support\FakeConnection
	{
		$stream = new Tests\Support\FakeConnection();

		$connection = new Ratchet\Client\WebSocket(
			$stream,
			new Psr7\Response(101),
			new Psr7\Request('GET', 'wss://eu-pconnect3.coolkit.cc:8080/api/ws'),
		);

		(new ReflectionMethod($client, 'connectionCreated'))->invoke($client, $connection, new Promise\Deferred());

		return $stream;
	}

	/**
	 * The sequence of the one request waiting for its answer, which is what the server echoes back
	 *
	 * @throws ReflectionException
	 */
	private function getPendingSequence(API\CloudWs $client): string
	{
		$pending = (new ReflectionProperty($client, 'messages'))->getValue($client);

		self::assertIsArray($pending);
		self::assertCount(1, $pending);

		return (string) array_key_first($pending);
	}

	private function push(Tests\Support\FakeConnection $stream, string $payload): void
	{
		$stream->emit(
			'data',
			[(new RFC6455\Messaging\Frame($payload, true, RFC6455\Messaging\Frame::OP_TEXT))->getContents()],
		);
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
