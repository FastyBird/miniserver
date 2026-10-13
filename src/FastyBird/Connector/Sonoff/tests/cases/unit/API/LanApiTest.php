<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\API;

use BadMethodCallException;
use Closure;
use Error;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use Nette;
use Override;
use React\Datagram;
use React\Dns;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use function array_map;
use function assert;
use function func_get_args;

final class LanApiTest extends Tests\Cases\Unit\BaseTestCase
{

	private const string DEVICE_ID = '1000a1b2c3';

	private API\LanApi|null $client = null;

	public function testHooksAreNotPublicProperties(): void
	{
		$properties = (new ReflectionClass(API\LanApi::class))->getProperties(ReflectionProperty::IS_PUBLIC);

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
	public function testAnnouncedEncryptedDeviceWithoutKeyNotifiesMessageListenersInOrderWithoutData(): void
	{
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1=cipher', 'encrypt=true', 'iv=aXY='],
			),
			'10.0.0.7:5353',
		]);

		self::assertSame(['first', 'second'], array_map(static fn (array $event): string => $event[0], $events));

		foreach ($events as $event) {
			self::assertCount(1, $event[1]);
			self::assertInstanceOf(API\Messages\Response\Lan\DeviceEvent::class, $event[1][0]);
			self::assertSame(self::DEVICE_ID, $event[1][0]->getId());
			self::assertSame('10.0.0.7', $event[1][0]->getIpAddress());
			self::assertSame(8_081, $event[1][0]->getPort());
			self::assertTrue($event[1][0]->isEncrypted());
			self::assertNull($event[1][0]->getData());
		}
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
	 * `connect()` joins the mDNS multicast group on the real socket; the test then raises what the socket would
	 *
	 * @return array{API\LanApi, Datagram\SocketInterface}
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
		$client = $this->container->getByType(API\LanApiFactory::class)->create();

		$client->connect();

		$this->client = $client;

		$socket = (new ReflectionProperty($client, 'server'))->getValue($client);
		assert($socket instanceof Datagram\SocketInterface);

		return [$client, $socket];
	}

	/**
	 * The mDNS answer a Sonoff device gives to the `_ewelink._tcp` question: its address, its service and its data
	 *
	 * @param array<string> $text
	 */
	private function buildAnnouncement(array $text): string
	{
		$name = 'eWeLink_' . self::DEVICE_ID;

		$message = new Dns\Model\Message();
		$message->qr = true;
		$message->answers = [
			new Dns\Model\Record(
				$name . '.local',
				Dns\Model\Message::TYPE_A,
				Dns\Model\Message::CLASS_IN,
				120,
				'10.0.0.7',
			),
			new Dns\Model\Record(
				$name . '._ewelink._tcp.local',
				Dns\Model\Message::TYPE_SRV,
				Dns\Model\Message::CLASS_IN,
				120,
				['priority' => '0', 'weight' => '0', 'port' => '8081', 'target' => $name . '.local'],
			),
			new Dns\Model\Record(
				$name . '._ewelink._tcp.local',
				Dns\Model\Message::TYPE_TXT,
				Dns\Model\Message::CLASS_IN,
				120,
				$text,
			),
		];

		return (new Dns\Protocol\BinaryDumper())->toBinary($message);
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
