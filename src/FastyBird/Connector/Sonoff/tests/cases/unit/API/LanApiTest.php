<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\API;

use BadMethodCallException;
use Closure;
use Error;
use ArrayObject;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use Nette;
use Override;
use Psr\Log;
use React\Datagram;
use React\Dns;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use Stringable;
use function array_map;
use function assert;
use function base64_encode;
use function func_get_args;
use function is_string;
use function serialize;

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

	/**
	 * An unencrypted device publishes its state as plain JSON in the `data1` text record, as the eWeLink LAN
	 * documentation describes, so there is nothing to decrypt and no key is needed to read it.
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testAnnouncedUnencryptedDeviceNotifiesMessageListenersInOrderWithData(): void
	{
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->onMessage($this->record('first', $events));
		$client->onMessage($this->record('second', $events));

		$socket->emit('message', [
			$this->buildAnnouncement(
				[
					'txtvers=1',
					'id=' . self::DEVICE_ID,
					'type=plug',
					'apivers=1',
					'seq=15',
					'data1={"switch":"off","startup":"stay","pulse":"off","sledOnline":"on","fwVersion":"3.5.0","pulseWidth":2000,"rssi":-67}',
				],
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
			self::assertSame('15', $event[1][0]->getSeq());
			self::assertFalse($event[1][0]->isEncrypted());
			self::assertNull($event[1][0]->getIv());

			$data = $event[1][0]->getData();

			self::assertInstanceOf(API\Messages\Response\Lan\DeviceEventData::class, $data);
			self::assertSame('off', $data->getSwitch());
			self::assertSame('stay', $data->getStartup());
			self::assertSame('off', $data->getPulse());
			self::assertSame(2_000, $data->getPulseWidth());
			self::assertSame('3.5.0', $data->getFirmwareVersion());
			self::assertSame(-67, $data->getRssi());
			self::assertTrue($data->getStatusLed());
		}
	}

	/**
	 * A multi-channel device lists its outlets in `switches`, and a device whose data does not fit one text record
	 * continues in `data2`
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testAnnouncedUnencryptedMultiChannelDeviceNotifiesMessageListenersWithEveryOutlet(): void
	{
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->onMessage($this->record('message', $events));

		$socket->emit('message', [
			$this->buildAnnouncement(
				[
					'txtvers=1',
					'id=' . self::DEVICE_ID,
					'type=strip',
					'apivers=1',
					'seq=3',
					'data1={"switches":[{"switch":"on","outlet":0},{"switch":"off","outlet":1}],',
					'data2="sledOnline":"off","fwVersion":"3.6.0"}',
				],
			),
			'10.0.0.7:5353',
		]);

		self::assertCount(1, $events);
		self::assertInstanceOf(API\Messages\Response\Lan\DeviceEvent::class, $events[0][1][0]);

		$data = $events[0][1][0]->getData();

		self::assertInstanceOf(API\Messages\Response\Lan\DeviceEventData::class, $data);
		self::assertTrue($data->isSwitches());
		self::assertSame(
			[['on', 0], ['off', 1]],
			array_map(
				static fn (API\Messages\Response\Lan\SwitchState $state): array => [
					$state->getSwitch(),
					$state->getOutlet(),
				],
				$data->getSwitchesStates(),
			),
		);
		self::assertFalse($data->getStatusLed());
		self::assertSame('3.6.0', $data->getFirmwareVersion());
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
	public function testAnnouncedEncryptedDeviceWithKeyNotifiesMessageListenersWithDecryptedData(): void
	{
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');
		$client->onMessage($this->record('message', $events));

		$iv = base64_encode('0123456789abcdef');

		$cipher = Helpers\Transformer::encryptMessage('{"switch":"on","fwVersion":"3.5.0"}', 'device-key', $iv);
		assert(is_string($cipher));

		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1=' . $cipher, 'encrypt=true', 'iv=' . $iv],
			),
			'10.0.0.7:5353',
		]);

		self::assertCount(1, $events);
		self::assertInstanceOf(API\Messages\Response\Lan\DeviceEvent::class, $events[0][1][0]);
		self::assertTrue($events[0][1][0]->isEncrypted());

		$data = $events[0][1][0]->getData();

		self::assertInstanceOf(API\Messages\Response\Lan\DeviceEventData::class, $data);
		self::assertSame('on', $data->getSwitch());
		self::assertSame('3.5.0', $data->getFirmwareVersion());
	}

	/**
	 * The text record is not authenticated, so plain data announced for a device whose key is known is not taken as
	 * its state: anyone on the network could otherwise skip the encryption
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testAnnouncedPlainDataForDeviceWithKeyIsDroppedAndLogged(): void
	{
		$events = [];
		$logs = new ArrayObject();

		[$client, $socket] = $this->connectClient($logs);

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');
		$client->onMessage($this->record('message', $events));

		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
			),
			'10.0.0.7:5353',
		]);

		self::assertSame([], $events);
		self::assertCount(1, $logs);
		self::assertSame(Log\LogLevel::WARNING, $logs[0]['level']);
		self::assertSame(self::DEVICE_ID, $logs[0]['context']['device']['id']);
		self::assertStringNotContainsString('device-key', serialize($logs->getArrayCopy()));
		self::assertStringNotContainsString('"switch":"on"', serialize($logs->getArrayCopy()));
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
	private function connectClient(ArrayObject|null $logs = null): array
	{
		if ($logs !== null) {
			$this->mockContainerService(Sonoff\Logger::class, new Sonoff\Logger(new class ($logs) extends Log\AbstractLogger {

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

			}));
		}

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
