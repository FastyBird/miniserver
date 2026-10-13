<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Tests\Cases\Unit\API;

use ArrayObject;
use BadMethodCallException;
use Closure;
use Error;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\Connector\Sonoff\Tests;
use FastyBird\Core\Exceptions as CoreExceptions;
use GuzzleHttp;
use Nette;
use Override;
use Psr\Http;
use Psr\Log;
use React\Datagram;
use React\Dns;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Stringable;
use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function base64_encode;
use function count;
use function func_get_args;
use function is_array;
use function is_string;
use function restore_error_handler;
use function serialize;
use function set_error_handler;

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
		/** @var array<int, array{string, array<mixed>}> $events */
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
		/** @var array<int, array{string, array<mixed>}> $events */
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
		/** @var array<int, array{string, array<mixed>}> $events */
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
		/** @var array<int, array{string, array<mixed>}> $events */
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
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
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

		self::assertNoEvents($events);
		self::assertCount(1, $logs);

		$log = $logs->getArrayCopy()[0];

		self::assertSame(Log\LogLevel::WARNING, $log['level']);
		self::assertSame(['id' => self::DEVICE_ID], $log['context']['device'] ?? null);
		self::assertStringNotContainsString('device-key', serialize($logs->getArrayCopy()));
		self::assertStringNotContainsString('"switch":"on"', serialize($logs->getArrayCopy()));
	}

	/**
	 * What reaches the key check and what reaches the device lookup must be the same id. The lookup of the devices
	 * matches an identifier case-insensitively and as a pattern, so an id that is only like the id of a device with
	 * a key is dropped as a whole, not looked up as a device that has no key
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testAnnouncedPlainDataUnderAVariantOfTheIdOfADeviceWithKeyIsDropped(): void
	{
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		[$client, $socket] = $this->connectClient($logs);

		// The device is configured with an upper case identifier, and announces itself with a lower case one
		$client->registerDeviceKey('1000A1B2C3', 'device-key');
		$client->onMessage($this->record('message', $events));

		$variants = [
			'1000A1B2C3',
			' 1000a1b2c3',
			'1000a1b2c3 ',
			"1000a1b2c3\n",
			"1000a1b2c3\0",
			'1000a1b2c',
			'1000a1b2c33',
			'1000a1b2c.',
			'.*',
		];

		foreach ($variants as $variant) {
			$socket->emit('message', [
				$this->buildAnnouncement(
					['id=' . $variant, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
				),
				'10.0.0.7:5353',
			]);
		}

		self::assertNoEvents($events);
		self::assertCount(count($variants), $logs);

		// The id the announcement is checked against is the one the key was registered for, in lower case
		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
			),
			'10.0.0.7:5353',
		]);

		self::assertNoEvents($events);
		self::assertCount(count($variants) + 1, $logs);
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
	public function testAnnouncementRepeatingATextRecordWithAnotherValueIsDropped(): void
	{
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		[$client, $socket] = $this->connectClient($logs);

		$client->onMessage($this->record('message', $events));

		$announcements = [
			// The id twice, in one text and in two
			['id=' . self::DEVICE_ID, 'id=1000a1b2c4', 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
			// The flag twice, the last one saying the data is plain
			['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}', 'encrypt=true', 'encrypt=false'],
		];

		foreach ($announcements as $text) {
			$socket->emit('message', [$this->buildAnnouncement($text), '10.0.0.7:5353']);
		}

		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
				['id=1000a1b2c4'],
			),
			'10.0.0.7:5353',
		]);

		self::assertNoEvents($events);
		self::assertCount(3, $logs);

		// The same record twice with the same value says nothing different
		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
				['id=' . self::DEVICE_ID],
			),
			'10.0.0.7:5353',
		]);

		self::assertEventCount(1, $events);
		self::assertCount(3, $logs);
	}

	/**
	 * Only `false` and `0` announce plain data, and what is not known is taken as encrypted, never as plain
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testEncryptFlagOfAnAnnouncementOnlyAnnouncesPlainDataAsFalseOrZero(): void
	{
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->onMessage($this->record('message', $events));

		$flags = ['false' => false, 'FALSE' => false, '0' => false, 'true' => true, '1' => true, 'no' => true, '' => true];

		foreach ($flags as $flag => $encrypted) {
			$socket->emit('message', [
				$this->buildAnnouncement(
					['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}', 'encrypt=' . $flag],
				),
				'10.0.0.7:5353',
			]);
		}

		self::assertSame(
			array_values($flags),
			array_map(static fn (array $event): bool => self::deviceEvent($event)->isEncrypted(), $events),
		);

		// Data that is plain is read, data that is said to be encrypted is not, there is no key to read it with
		self::assertSame(
			[true, true, true, false, false, false, false],
			array_map(static fn (array $event): bool => self::deviceEvent($event)->getData() !== null, $events),
		);
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
	public function testPlainDataOfADeviceWithKeyIsDroppedWhateverTheEncryptFlagSaysFalse(): void
	{
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		[$client, $socket] = $this->connectClient($logs);

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');
		$client->onMessage($this->record('message', $events));

		foreach (['false', '0'] as $flag) {
			$socket->emit('message', [
				$this->buildAnnouncement(
					['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}', 'encrypt=' . $flag],
				),
				'10.0.0.7:5353',
			]);
		}

		self::assertNoEvents($events);
		self::assertCount(2, $logs);
	}

	/**
	 * A text record is a key and a value split at the first `=`, and a value can hold more of them, as the padding
	 * of base64 does
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testAnnouncedValueMayContainEqualSigns(): void
	{
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];

		[$client, $socket] = $this->connectClient();

		$client->onMessage($this->record('message', $events));

		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"ssid":"a=b=c","switch":"on","fwVersion":"3.5.0"}'],
			),
			'10.0.0.7:5353',
		]);

		self::assertCount(1, $events);
		self::assertSame('a=b=c', self::deviceEvent($events[0])->getData()?->getSsid());
	}

	/**
	 * Keys are kept under one form of the id, so the requests for a device configured with an upper case identifier
	 * are encrypted like the ones for any other
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testRequestsForADeviceConfiguredWithAnUpperCaseIdentifierAreEncrypted(): void
	{
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		[$client] = $this->connectClient($logs);

		$client->registerDeviceKey('1000A1B2C3', 'device-key');

		// The device is at a loopback address nothing listens on, so no packet leaves the machine
		foreach (['info', 'state'] as $call) {
			try {
				if ($call === 'info') {
					$client->getDeviceInfo('1000A1B2C3', '127.0.0.1', 1, false);
				} else {
					$client->setDeviceState('1000A1B2C3', '127.0.0.1', 1, 'switch', 'on', null, null, false);
				}
			} catch (SonoffExceptions\LanApiCall) {
				// The request was refused, which is what the test wants: it was logged before it was sent
			}
		}

		$bodies = [];

		foreach ($logs as $log) {
			$request = $log['context']['request'] ?? null;

			if (is_array($request) && is_string($request['body'] ?? null)) {
				$bodies[] = $request['body'];
			}
		}

		self::assertCount(2, $bodies);

		foreach ($bodies as $body) {
			self::assertStringContainsString('"encrypt":true', $body);
			self::assertStringContainsString('"iv":', $body);
			self::assertStringNotContainsString('"switch"', $body);
		}
	}

	/**
	 * A packet that cannot be read, or an announcement that cannot be decrypted or mapped, is that one announcement
	 * lost: the client keeps listening for the next one
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testMalformedAnnouncementsAreDroppedAndTheClientKeepsListening(): void
	{
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		[$client, $socket] = $this->connectClient($logs);

		$otherId = '1000a1b2c4';

		$client->registerDeviceKey($otherId, 'device-key');
		$client->onMessage($this->record('message', $events));

		$truncated = new Dns\Model\Message();
		$truncated->qr = true;
		$truncated->tc = true;

		$malformed = [
			'a packet that is no message' => "\x00\x01",
			'a truncated message' => (new Dns\Protocol\BinaryDumper())->toBinary($truncated),
			'garbage encrypted with the key' => $this->buildAnnouncement(
				['id=' . $otherId, 'type=plug', 'seq=7', 'data1=Z2FyYmFnZQ==', 'encrypt=true', 'iv=MDEyMzQ1Njc4OWFiY2RlZg=='],
			),
			'data that is not JSON' => $this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch"'],
			),
			'data that is not an object' => $this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1=5'],
			),
			'data that does not map' => $this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"maybe","fwVersion":"3.5.0"}'],
			),
		];

		$dropped = 0;

		foreach ($malformed as $packet) {
			$socket->emit('message', [$packet, '10.0.0.7:5353']);

			$dropped++;

			self::assertCount($dropped, $logs);
		}

		self::assertEventCount(0, $events);

		foreach ($logs as $log) {
			self::assertSame(Log\LogLevel::WARNING, $log['level']);
			self::assertStringNotContainsString('device-key', serialize($log));
			self::assertStringNotContainsString('Z2FyYmFnZQ', serialize($log));
		}

		// The next valid announcement is delivered
		$socket->emit('message', [
			$this->buildAnnouncement(
				['id=' . self::DEVICE_ID, 'type=plug', 'seq=8', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
			),
			'10.0.0.7:5353',
		]);

		self::assertEventCount(1, $events);
		self::assertSame(self::DEVICE_ID, self::deviceEvent($events[0])->getId());
	}

	/**
	 * What a listener throws is the listener's: only reading the announcement is guarded, so the exception is not
	 * taken for a malformed announcement and logged away
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testExceptionOfAListenerIsNotTakenForAMalformedAnnouncement(): void
	{
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		[$client, $socket] = $this->connectClient($logs);

		$client->onMessage(static function (): void {
			throw new SonoffExceptions\LanApiError('Thrown by the listener');
		});

		try {
			$socket->emit('message', [
				$this->buildAnnouncement(
					['id=' . self::DEVICE_ID, 'type=plug', 'seq=7', 'data1={"switch":"on","fwVersion":"3.5.0"}'],
				),
				'10.0.0.7:5353',
			]);

			self::fail('The exception of the listener was swallowed');
		} catch (SonoffExceptions\LanApiError $ex) {
			self::assertSame('Thrown by the listener', $ex->getMessage());
		}

		self::assertCount(0, $logs);
	}

	/**
	 * An announcement whose vector is not sixteen bytes cannot be decrypted, and is dropped without PHP warning
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws BadMethodCallException
	 * @throws ReflectionException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testAnnouncementWithAVectorOfAnotherLengthIsDropped(): void
	{
		/** @var array<int, array{string, array<mixed>}> $events */
		$events = [];
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		[$client, $socket] = $this->connectClient($logs);

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');
		$client->onMessage($this->record('message', $events));

		$vector = '0123456789abcdef';

		$cipher = Helpers\Transformer::encryptMessage(
			'{"switch":"on","fwVersion":"3.5.0"}',
			'device-key',
			base64_encode($vector),
		);
		assert(is_string($cipher));

		$warnings = [];

		set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
			$warnings[] = $message;

			return true;
		});

		try {
			// The vector the data was encrypted with, and more: OpenSSL would cut it to the first sixteen bytes
			$socket->emit('message', [
				$this->buildAnnouncement(
					[
						'id=' . self::DEVICE_ID,
						'type=plug',
						'seq=7',
						'data1=' . $cipher,
						'encrypt=true',
						'iv=' . base64_encode($vector . 'and-then-more'),
					],
				),
				'10.0.0.7:5353',
			]);
		} finally {
			restore_error_handler();
		}

		self::assertSame([], $warnings);
		self::assertNoEvents($events);
		self::assertCount(1, $logs);
		self::assertSame(Log\LogLevel::WARNING, $logs->getArrayCopy()[0]['level']);
	}

	/**
	 * A device with a key replies with its data encrypted with that key, and the data is decrypted before it is read
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 * @throws Error
	 */
	public function testEncryptedReplyOfADeviceWithKeyIsDecrypted(): void
	{
		$client = $this->createReplyingClient(self::encryptedReply(self::deviceInfo(), 'device-key'));

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');

		$info = $client->getDeviceInfo(self::DEVICE_ID, '10.0.0.7', 8_081, false);

		self::assertSame(self::DEVICE_ID, $info->getId());
		self::assertTrue($info->getSwitch());
		self::assertSame('3.5.0', $info->getFirmwareVersion());
	}

	/**
	 * A reply is not authenticated other than by the key, so plain data in the reply of a device with a key is
	 * refused, whatever it says
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\LanApiCall
	 * @throws Error
	 */
	public function testPlainReplyOfADeviceWithKeyIsRefused(): void
	{
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		$client = $this->createReplyingClient(
			Nette\Utils\Json::encode(['seq' => 1, 'error' => 0, 'data' => self::deviceInfo()]),
			$logs,
		);

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');

		try {
			$client->getDeviceInfo(self::DEVICE_ID, '10.0.0.7', 8_081, false);

			self::fail('Plain data of a device with a key was read');
		} catch (SonoffExceptions\LanApiError) {
			// The reply is refused
		}

		self::assertRefusalWasLogged($logs);
	}

	/**
	 * Data that does not decrypt with the key of the device is not the device's: another key, a vector of another
	 * length, or no vector at all
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\LanApiCall
	 * @throws Error
	 */
	public function testReplyThatDoesNotDecryptWithTheKeyIsRefused(): void
	{
		$withAnotherKey = self::encryptedReply(self::deviceInfo(), 'another-key');

		$withALongerVector = Nette\Utils\Json::decode(self::encryptedReply(self::deviceInfo(), 'device-key'));
		assert($withALongerVector instanceof stdClass);
		$withALongerVector->iv = base64_encode('0123456789abcdefand-then-more');

		$withoutVector = Nette\Utils\Json::decode(self::encryptedReply(self::deviceInfo(), 'device-key'));
		assert($withoutVector instanceof stdClass);
		unset($withoutVector->iv);

		foreach (
			[
				$withAnotherKey,
				Nette\Utils\Json::encode($withALongerVector),
				Nette\Utils\Json::encode($withoutVector),
			] as $reply
		) {
			/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
			$logs = new ArrayObject();

			$client = $this->createReplyingClient($reply, $logs);

			$client->registerDeviceKey(self::DEVICE_ID, 'device-key');

			try {
				$client->getDeviceInfo(self::DEVICE_ID, '10.0.0.7', 8_081, false);

				self::fail('Data that does not decrypt with the key was read');
			} catch (SonoffExceptions\LanApiError) {
				// The reply is refused
			}

			self::assertRefusalWasLogged($logs);
		}
	}

	/**
	 * Setting a state reads no state from the reply, but plain data in the reply of a device with a key is still not
	 * the device's, and encrypted data is
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 * @throws Error
	 */
	public function testReplyToSettingTheStateOfADeviceWithKeyIsCheckedLikeAnyOther(): void
	{
		/** @var ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs */
		$logs = new ArrayObject();

		$client = $this->createReplyingClient(
			Nette\Utils\Json::encode(['seq' => 1, 'error' => 0, 'data' => ['switch' => 'on']]),
			$logs,
		);

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');

		try {
			$client->setDeviceState(self::DEVICE_ID, '10.0.0.7', 8_081, 'switch', 'on', null, null, false);

			self::fail('Plain data of a device with a key was read');
		} catch (SonoffExceptions\LanApiError) {
			// The reply is refused
		}

		self::assertRefusalWasLogged($logs);

		$client = $this->createReplyingClient(
			self::encryptedReply(['switch' => 'on'], 'device-key'),
		);

		$client->registerDeviceKey(self::DEVICE_ID, 'device-key');

		self::assertTrue(
			$client->setDeviceState(self::DEVICE_ID, '10.0.0.7', 8_081, 'switch', 'on', null, null, false),
		);
	}

	/**
	 * A device without a key replies in plain, and its reply is read as it always was
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 * @throws Error
	 */
	public function testPlainReplyOfADeviceWithoutKeyIsRead(): void
	{
		$client = $this->createReplyingClient(
			Nette\Utils\Json::encode(['seq' => 1, 'error' => 0, 'data' => self::deviceInfo()]),
		);

		$info = $client->getDeviceInfo(self::DEVICE_ID, '10.0.0.7', 8_081, false);

		self::assertTrue($info->getSwitch());

		self::assertTrue(
			$this->createReplyingClient(
				Nette\Utils\Json::encode(['seq' => 1, 'error' => 0, 'data' => ['switch' => 'on']]),
			)
				->setDeviceState(self::DEVICE_ID, '10.0.0.7', 8_081, 'switch', 'on', null, null, false),
		);
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
	 * @param ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs
	 */
	private static function assertRefusalWasLogged(ArrayObject $logs): void
	{
		// The request and the reply are logged at debug level, the refusal is the one warning
		$warnings = array_values(array_filter(
			$logs->getArrayCopy(),
			static fn (array $log): bool => $log['level'] !== Log\LogLevel::DEBUG,
		));

		self::assertCount(1, $warnings);

		$log = $warnings[0];

		self::assertSame(Log\LogLevel::WARNING, $log['level']);
		self::assertSame(['id' => self::DEVICE_ID], $log['context']['device'] ?? null);
		self::assertStringNotContainsString('device-key', serialize($warnings));
		self::assertStringNotContainsString('3.5.0', serialize($warnings));
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function deviceInfo(): array
	{
		return [
			'deviceid' => self::DEVICE_ID,
			'switch' => 'on',
			'ssid' => 'home',
			'otaUnlock' => false,
			'fwVersion' => '3.5.0',
			'bssid' => 'ec:aa:a0:12:34:56',
			'signalStrength' => -60,
		];
	}

	/**
	 * The reply of a device with a key: its data encrypted with the key, and the vector it was encrypted with
	 *
	 * @param array<string, mixed> $data
	 *
	 * @throws Nette\Utils\JsonException
	 */
	private static function encryptedReply(array $data, string $key): string
	{
		$vector = base64_encode('0123456789abcdef');

		$cipher = Helpers\Transformer::encryptMessage(Nette\Utils\Json::encode($data), $key, $vector);
		assert(is_string($cipher));

		return Nette\Utils\Json::encode(
			['seq' => 1, 'sequence' => '1', 'error' => 0, 'encrypt' => true, 'iv' => $vector, 'data' => $cipher],
		);
	}

	/**
	 * @param array<mixed> $events
	 */
	private static function assertNoEvents(array $events): void
	{
		self::assertSame([], $events);
	}

	/**
	 * @param array<mixed> $events
	 */
	private static function assertEventCount(int $count, array $events): void
	{
		self::assertCount($count, $events);
	}

	/**
	 * @param array{string, array<mixed>} $event
	 */
	private static function deviceEvent(array $event): API\Messages\Response\Lan\DeviceEvent
	{
		self::assertInstanceOf(API\Messages\Response\Lan\DeviceEvent::class, $event[1][0]);

		return $event[1][0];
	}

	/**
	 * @param ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}> $logs where what the
	 *        connector logs is collected
	 */
	private static function collectingLogger(ArrayObject $logs): Sonoff\Logger
	{
		return new Sonoff\Logger(new class ($logs) extends Log\AbstractLogger {

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
	}

	/**
	 * `connect()` joins the mDNS multicast group on the real socket; the test then raises what the socket would
	 *
	 * @param ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}>|null $logs where what the
	 *        connector logs is collected
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
			$this->mockContainerService(Sonoff\Logger::class, self::collectingLogger($logs));
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
	 * @param array<string>|null $moreText a second text record in the same answer
	 */
	private function buildAnnouncement(array $text, array|null $moreText = null): string
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

		if ($moreText !== null) {
			$message->answers[] = new Dns\Model\Record(
				$name . '._ewelink._tcp.local',
				Dns\Model\Message::TYPE_TXT,
				Dns\Model\Message::CLASS_IN,
				120,
				$moreText,
			);
		}

		return (new Dns\Protocol\BinaryDumper())->toBinary($message);
	}

	/**
	 * A client whose every request the device answers with the given reply; it is not connected
	 *
	 * @param ArrayObject<int, array{level: mixed, message: string, context: array<mixed>}>|null $logs where what the
	 *        connector logs is collected
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws Error
	 */
	private function createReplyingClient(string $reply, ArrayObject|null $logs = null): API\LanApi
	{
		// Every client is made from a container of its own, so that the services replaced for it are its own
		$this->container = $this->createContainer();

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(function () use ($reply): Http\Message\ResponseInterface {
				$body = $this->createMock(Http\Message\StreamInterface::class);
				$body->method('getContents')->willReturn($reply);

				$response = $this->createMock(Http\Message\ResponseInterface::class);
				$response->method('getBody')->willReturn($body);

				return $response;
			});

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory->method('create')->willReturn($httpClient);

		$this->mockContainerService(Services\HttpClientFactory::class, $httpClientFactory);

		if ($logs !== null) {
			$this->mockContainerService(Sonoff\Logger::class, self::collectingLogger($logs));
		}

		return $this->container->getByType(API\LanApiFactory::class)->create();
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
