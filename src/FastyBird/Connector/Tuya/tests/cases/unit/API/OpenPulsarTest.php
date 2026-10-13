<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\API;

use Closure;
use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Core\EventLoop as CoreEventLoop;
use FastyBird\Core\Exceptions as CoreExceptions;
use GuzzleHttp\Psr7;
use JsonException;
use Nette;
use Ratchet;
use Ratchet\RFC6455;
use React\Promise;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Throwable;
use function array_map;
use function base64_encode;
use function func_get_args;
use function json_encode;
use function openssl_encrypt;
use function ord;
use function substr;
use const JSON_THROW_ON_ERROR;
use const OPENSSL_RAW_DATA;

final class OpenPulsarTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string ACCESS_SECRET = 'abcdefghijklmnopqrstuvwxyz012345';

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\OpenPulsar::class))->getProperties(ReflectionProperty::IS_PUBLIC);

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

		$stream = $this->attach($client);

		self::assertSame([['first', []], ['second', []]], $events);
		self::assertSame([], $stream->getWritten());
		self::assertTrue($client->isConnected());
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
	 * @throws ReflectionException
	 * @throws Error
	 * @throws JsonException
	 */
	public function testDeviceStatusMessageNotifiesMessageListenersInOrderWithTheReportedState(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		$stream = $this->attach($client);

		$this->push(
			$stream,
			$this->buildMessage(
				'{"devId":"bf0123456789","dataId":"d1","productKey":"pk","status":[{"code":"switch_1","value":true,"t":1700000000}]}',
			),
		);

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(API\Messages\Response\ReportDeviceState::class, $event[1][0]);
			self::assertSame('bf0123456789', $event[1][0]->getIdentifier());
			self::assertCount(1, $event[1][0]->getDataPoints());
			self::assertSame('switch_1', $event[1][0]->getDataPoints()[0]->getCode());
		}

		// The message is confirmed to the server, which removes it from its queue
		self::assertSame(['{"messageId":"msg-1"}'], $this->decodeWrittenTexts($stream));
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws ReflectionException
	 * @throws Error
	 * @throws JsonException
	 */
	public function testDeviceOnlineMessageNotifiesMessageListenersWithTheOnlineState(): void
	{
		$events = [];

		$client = $this->createClient();

		$client->onMessage($this->record('message', $events));

		$stream = $this->attach($client);

		$this->push(
			$stream,
			$this->buildMessage(
				'{"devId":"bf0123456789","dataId":"d1","productKey":"pk","bizCode":"offline","bizData":{"time":1700000000}}',
			),
		);

		self::assertCount(1, $events);
		self::assertSame('message', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(API\Messages\Response\ReportDeviceOnline::class, $events[0][1][0]);
		self::assertSame('bf0123456789', $events[0][1][0]->getIdentifier());
		self::assertFalse($events[0][1][0]->isOnline());
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

		$client->onError($this->record('first', $events));
		$client->onError($this->record('second', $events));

		$stream = $this->attach($client);

		$this->push($stream, 'this is not json');

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(Throwable::class, $event[1][0]);
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	private function createClient(): API\OpenPulsar
	{
		// The container's loop runs at the end of the process, and a timer the client sets would keep it alive
		$this->mockContainerService(CoreEventLoop\Wrapper::class, new Tests\Support\InertEventLoop());

		return $this->container->getByType(API\OpenPulsarFactory::class)->create(
			'connector-identifier',
			'access-id',
			self::ACCESS_SECRET,
			Types\OpenPulsarTopic::PROD,
			Types\OpenPulsarEndpoint::EUROPE,
		);
	}

	/**
	 * `connect()` opens a WebSocket to Tuya's message queue in the cloud. Everything the client raises on its hooks
	 * happens once that WebSocket exists, in `connectionCreated()`, so the test hands the client a WebSocket sitting
	 * on a connection it drives.
	 *
	 * @throws ReflectionException
	 */
	private function attach(API\OpenPulsar $client): Tests\Support\FakeConnection
	{
		$stream = new Tests\Support\FakeConnection();

		$connection = new Ratchet\Client\WebSocket(
			$stream,
			new Psr7\Response(101),
			new Psr7\Request('GET', 'wss://mqe.tuyaeu.com:8285/'),
		);

		(new ReflectionMethod($client, 'connectionCreated'))->invoke($client, $connection, new Promise\Deferred());

		return $stream;
	}

	/**
	 * What Tuya's message queue delivers: the device data, encrypted with the middle of the access secret, wrapped in
	 * a payload, wrapped in the queue message
	 *
	 * @throws JsonException
	 */
	private function buildMessage(string $data): string
	{
		$encrypted = openssl_encrypt($data, 'AES-128-ECB', substr(self::ACCESS_SECRET, 8, 16), OPENSSL_RAW_DATA);
		self::assertIsString($encrypted);

		$payload = json_encode(
			[
				'data' => base64_encode($encrypted),
				'protocol' => 4,
				'pv' => '2.0',
				'sign' => 'signature',
				't' => 1_700_000_000,
			],
			JSON_THROW_ON_ERROR,
		);

		return json_encode(
			[
				'messageId' => 'msg-1',
				'payload' => base64_encode($payload),
				'properties' => new stdClass(),
				'publishTime' => '2024-01-01T00:00:00Z',
				'redeliveryCount' => 0,
				'key' => 'key',
			],
			JSON_THROW_ON_ERROR,
		);
	}

	private function push(Tests\Support\FakeConnection $stream, string $payload): void
	{
		$stream->emit(
			'data',
			[(new RFC6455\Messaging\Frame($payload, true, RFC6455\Messaging\Frame::OP_TEXT))->getContents()],
		);
	}

	/**
	 * @return array<string>
	 */
	private function decodeWrittenTexts(Tests\Support\FakeConnection $stream): array
	{
		return array_map(
			static function (string $written): string {
				// A client frame is masked: 2 header bytes, 4 mask bytes, then the masked payload
				$length = ord($written[1]) & 0x7F;
				$mask = substr($written, 2, 4);
				$masked = substr($written, 6, $length);
				$text = '';

				for ($i = 0; $i < $length; $i++) {
					$text .= $masked[$i] ^ $mask[$i % 4];
				}

				return $text;
			},
			$stream->getWritten(),
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
