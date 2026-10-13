<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\API;

use Closure;
use Error;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use GuzzleHttp\Psr7;
use Nette;
use Ramsey\Uuid;
use Ratchet;
use Ratchet\RFC6455;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use function array_map;
use function func_get_args;

final class Gen2WsApiTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string DEVICE_ID = '9d3a4a35-9b64-4e7a-a1c3-3f9d4a0e6a10';

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\Gen2WsApi::class))->getProperties(ReflectionProperty::IS_PUBLIC);

		self::assertSame([], $properties);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testConnectionNotifiesConnectedListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onConnected($this->record('first', $events));
		$client->onConnected($this->record('second', $events));

		$this->attach($client);

		self::assertSame([['first', []], ['second', []]], $events);
		self::assertTrue($client->isConnected());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
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
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testStatusNotificationNotifiesMessageListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		$stream = $this->attach($client);

		$this->push(
			$stream,
			'{"src":"shellyplus1-a8032abe5f10","dst":"fb_ws_client","method":"NotifyStatus",'
			. '"params":{"ts":1700000000.1,"input:0":{"id":0,"state":true}}}',
		);

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(API\Messages\Response\Gen2\GetDeviceState::class, $event[1][0]);
			self::assertCount(1, $event[1][0]->getInputs());
			self::assertNotNull($event[1][0]->findInput(0));
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testEventNotificationNotifiesMessageListenersWithTheEvent(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('message', $events));

		$stream = $this->attach($client);

		$this->push(
			$stream,
			'{"src":"shellyplus1-a8032abe5f10","dst":"fb_ws_client","method":"NotifyEvent",'
			. '"params":{"ts":1700000000.1,"events":[{"component":"input:0","id":0,"event":"single_push","ts":1700000000.1}]}}',
		);

		self::assertCount(1, $events);
		self::assertSame('message', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(API\Messages\Response\Gen2\DeviceEvent::class, $events[0][1][0]);
		self::assertCount(1, $events[0][1][0]->getEvents());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testUndecodableMessageNotifiesErrorListenersInOrder(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onError($this->record('first', $events));
		$client->onError($this->record('second', $events));

		$stream = $this->attach($client);

		$this->push($stream, 'this is not json');

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Nette\Utils\JsonException::class, $event[1][0]);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testStatusNotificationThatBreaksTheSchemaNotifiesErrorListeners(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onError($this->record('error', $events));

		$stream = $this->attach($client);

		// A component state has to carry its id
		$this->push(
			$stream,
			'{"src":"shellyplus1-a8032abe5f10","dst":"fb_ws_client","method":"NotifyStatus",'
			. '"params":{"input:0":{"state":true}}}',
		);

		self::assertCount(1, $events);
		self::assertSame('error', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(Throwable::class, $events[0][1][0]);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testEventNotificationThatBreaksTheSchemaNotifiesErrorListeners(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onError($this->record('error', $events));

		$stream = $this->attach($client);

		// The events have to be a list
		$this->push(
			$stream,
			'{"src":"shellyplus1-a8032abe5f10","dst":"fb_ws_client","method":"NotifyEvent",'
			. '"params":{"ts":1700000000.1,"events":"single_push"}}',
		);

		self::assertCount(1, $events);
		self::assertSame('error', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(Throwable::class, $events[0][1][0]);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws Uuid\Exception\InvalidArgumentException
	 */
	public function testConnectionErrorNotifiesLostThenErrorListenersWithTheError(): void
	{
		$events = [];
		$error = new RuntimeException('Connection broke');

		$client = $this->createClient();

		$client->onLost($this->record('lost-first', $events));
		$client->onLost($this->record('lost-second', $events));
		$client->onError($this->record('error-first', $events));
		$client->onError($this->record('error-second', $events));

		$stream = $this->attach($client);

		$stream->emit('error', [$error]);

		self::assertSame(
			[
				['lost-first', []],
				['lost-second', []],
				['error-first', [$error]],
				['error-second', [$error]],
			],
			$events,
		);
		self::assertNotNull($client->getLost());
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Uuid\Exception\InvalidArgumentException
	 * @throws Error
	 */
	private function createClient(): API\Gen2WsApi
	{
		return $this->container->getByType(API\Gen2WsApiFactory::class)->create(
			Uuid\Uuid::fromString(self::DEVICE_ID),
			'10.0.0.8',
			null,
			null,
			null,
		);
	}

	/**
	 * `connect()` opens a TCP connection to the device and performs the WebSocket handshake; everything the client
	 * raises on its hooks happens once that connection exists, so the test hands the client a ready one
	 *
	 * @throws ReflectionException
	 */
	private function attach(API\Gen2WsApi $client): Tests\Support\FakeConnection
	{
		$stream = new Tests\Support\FakeConnection();

		$connection = new Ratchet\Client\WebSocket(
			$stream,
			new Psr7\Response(101),
			new Psr7\Request('GET', 'ws://10.0.0.8/rpc'),
		);

		(new ReflectionMethod($client, 'connectionCreated'))->invoke($client, $connection);

		return $stream;
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
