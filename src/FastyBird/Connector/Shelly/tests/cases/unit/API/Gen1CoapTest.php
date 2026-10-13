<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\API;

use BadMethodCallException;
use Closure;
use Error;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use Nette;
use Override;
use React\Datagram;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Throwable;
use function array_map;
use function assert;
use function func_get_args;
use function pack;
use function strlen;

final class Gen1CoapTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string STATE_PAYLOAD = '{"G":[[0,112,1],[0,111,"on"]]}';

	private API\Gen1Coap|null $client = null;

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\Gen1Coap::class))->getProperties(ReflectionProperty::IS_PUBLIC);

		self::assertSame([], $properties);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testStateMessageNotifiesListenersInOrderWithTheReportedState(): void
	{
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		$socket->emit('message', [
			$this->buildStatePacket('SHSW-1', 'AB12CD', self::STATE_PAYLOAD),
			'10.0.0.5:5683',
		]);

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(API\Messages\Response\Gen1\ReportDeviceState::class, $event[1][0]);
			self::assertSame('AB12CD', $event[1][0]->getIdentifier());
			self::assertSame('10.0.0.5', $event[1][0]->getIpAddress());
			self::assertCount(2, $event[1][0]->getStates());
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testSocketErrorNotifiesListenersWithTheError(): void
	{
		$events = [];
		$error = new RuntimeException('Socket failed');

		[$client, $socket] = $this->connectClient();

		$client->onError($this->record('first', $events));
		$client->onError($this->record('second', $events));

		$socket->emit('error', [$error, $socket]);

		self::assertSame([['first', [$error]], ['second', [$error]]], $events);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testStateMessageThatCannotBeMappedNotifiesErrorListeners(): void
	{
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->onError($this->record('error', $events));

		// The schema accepts any number as a block identifier, but the state needs an integer one
		$socket->emit('message', [
			$this->buildStatePacket('SHSW-1', 'AB12CD', '{"G":[[0.5,112,1]]}'),
			'10.0.0.5:5683',
		]);

		self::assertCount(1, $events);
		self::assertSame('error', $events[0][0]);
		self::assertCount(1, $events[0][1]);
		self::assertInstanceOf(Throwable::class, $events[0][1][0]);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testClosingTheSocketNotifiesClosedListenersInOrder(): void
	{
		$events = [];

		[$client] = $this->connectClient();

		$client->onClosed($this->record('first', $events));
		$client->onClosed($this->record('second', $events));

		$client->disconnect();

		self::assertSame([['first', []], ['second', []]], $events);
	}

	#[Override]
	protected function tearDown(): void
	{
		// Closes the multicast socket, which the container's loop would otherwise keep watching for ever
		$this->client?->disconnect();
		$this->client = null;

		parent::tearDown();
	}

	/**
	 * `connect()` joins the CoAP multicast group on the real socket; the test then raises what the socket would
	 *
	 * @return array{API\Gen1Coap, Datagram\SocketInterface}
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	private function connectClient(): array
	{
		$client = $this->container->getByType(API\Gen1CoapFactory::class)->create();

		$client->connect();

		$this->client = $client;

		$socket = (new ReflectionProperty($client, 'server'))->getValue($client);
		assert($socket instanceof Datagram\SocketInterface);

		return [$client, $socket];
	}

	/**
	 * A CoAP packet carrying a status report: non-confirmable message, code 30, and the extended option 3332
	 * with `<type>#<identifier>#<generation>` that the device announces itself with
	 */
	private function buildStatePacket(string $type, string $identifier, string $payload): string
	{
		$value = $type . '#' . $identifier . '#2';

		return "\x50\x1E\x00\x01"
			. pack('C', (14 << 4) | strlen($value))
			. pack('n', 3_332 - 269)
			. $value
			. "\xFF"
			. $payload;
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
