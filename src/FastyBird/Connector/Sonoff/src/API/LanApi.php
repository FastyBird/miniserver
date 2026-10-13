<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API;

use BadMethodCallException;
use Closure;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Services;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Exceptions as ValuesExceptions;
use FastyBird\Core\Values\Schemas;
use FastyBird\Core\Values\Types\Sources;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Clock\ClockInterface;
use Psr\Http\Message;
use React\Datagram;
use React\Dns;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use stdClass;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function assert;
use function base64_encode;
use function count;
use function explode;
use function http_build_query;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_numeric;
use function is_string;
use function md5;
use function preg_match;
use function property_exists;
use function random_bytes;
use function sprintf;
use function strtolower;
use function strval;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * Local LAN API interface
 */
final class LanApi
{

	public const int DEVICE_PORT = 8_081;

	public const int ERROR_INVALID_JSON = 400;

	public const int ERROR_UNAUTHORIZED = 401;

	public const int ERROR_DEVICE_ID_INVALID = 404;

	public const int ERROR_INVALID_PARAMETER = 422;

	private const string MDNS_ADDRESS = '224.0.0.251';

	private const int MDNS_PORT = 5_353;

	private const string MATCH_NAME = '/^(?:[a-zA-Z]+)_(?P<id>[0-9A-Za-z]+)._ewelink._tcp.local$/';

	private const string MATCH_DOMAIN = '/^(?:[a-zA-Z]+)_(?P<id>[0-9A-Za-z]+).local$/';
    // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const string MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	// A device id is ten lowercase hexadecimal characters. Nothing else is looked up by id, in the registered keys or in
	// the devices the event is handed to: the lookup of the devices matches an identifier case-insensitively and as a
	// pattern, so an id that is anything but plain characters could name one device to the key check and another
	// to the device lookup
	private const string MATCH_DEVICE_ID = '/^[0-9a-f]{10}\z/';

	// The values of the `encrypt` text record that announce plain data. A device that sends plain data leaves the record
	// out; whatever else the record holds, a value included that nobody knows, is taken as encrypted
	private const array PLAIN_DATA_FLAGS = ['false', '0'];

	private const string GET_DEVICE_INFO_MESSAGE_SCHEMA_FILENAME = 'lan_api_get_device_info.json';

	private const string SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'lan_api_set_device_state.json';

	/** @var array<Closure(Messages\Message $message): void> */
	private array $onMessageListeners = [];

	/** @var array<string, string> */
	private array $encodeKeys = [];

	/** @var array<string, string> */
	private array $validationSchemas = [];

	private Dns\Protocol\Parser $parser;

	private Dns\Protocol\BinaryDumper $dumper;

	private Datagram\SocketInterface|null $server = null;

	public function __construct(
		private readonly Services\HttpClientFactory $httpClientFactory,
		private readonly Services\MulticastFactory $multicastFactory,
		private readonly Helpers\MessageBuilder $entityHelper,
		private readonly Sonoff\Logger $logger,
		private readonly ClockInterface $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Schemas\Validator $schemaValidator,
	)
	{
		$this->parser = new Dns\Protocol\Parser();
		$this->dumper = new Dns\Protocol\BinaryDumper();
	}

	/**
	 * Registers a listener. Called when a message was received.
	 *
	 * @param Closure(Messages\Message $message): void $listener
	 */
	public function onMessage(Closure $listener): void
	{
		$this->onMessageListeners[] = $listener;
	}

	/**
	 * @throws BadMethodCallException
	 * @throws SonoffExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$this->server = $this->multicastFactory->create(self::MDNS_ADDRESS, self::MDNS_PORT);

		$this->server->on('message', function ($message): void {
			// A packet that cannot be read, or an announcement that cannot be decrypted or mapped, is that one
			// announcement lost: the client keeps listening for the next one. Only reading the announcement is
			// guarded, what a listener throws is the listener's and is not taken for a malformed announcement
			try {
				$event = $this->readAnnouncement($message);
			} catch (SonoffExceptions\InvalidState | SonoffExceptions\LanApiError | Utils\JsonException $ex) {
				$this->logger->warning(
					'An announcement could not be handled, it was dropped',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'lan-api',
						'exception' => [
							'type' => $ex::class,
							'message' => $ex->getMessage(),
						],
					],
				);

				return;
			}

			if ($event !== null) {
				Utils\Arrays::invoke($this->onMessageListeners, $event);
			}
		});

		$this->eventLoop->futureTick(function (): void {
			$query = new Dns\Query\Query(
				'_ewelink._tcp.local',
				Dns\Model\Message::TYPE_PTR,
				Dns\Model\Message::CLASS_IN,
			);

			$request = $this->dumper->toBinary(Dns\Model\Message::createRequestForQuery($query));

			$this->server?->send($request, self::MDNS_ADDRESS . ':' . self::MDNS_PORT);
		});
	}

	/**
	 * @throws SonoffExceptions\InvalidState
	 * @throws SonoffExceptions\LanApiError
	 * @throws Utils\JsonException
	 */
	private function readAnnouncement(mixed $message): Messages\Response\Lan\DeviceEvent|null
	{
		try {
			$response = $this->parser->parseMessage($message);

		} catch (InvalidArgumentException $ex) {
			throw new SonoffExceptions\InvalidState('Invalid mDNS question response received', $ex->getCode(), $ex);
		}

		if ($response->tc) {
			throw new SonoffExceptions\InvalidState(
				'The server set the truncated bit although we issued a TCP request',
			);
		}

		$deviceIpAddress = null;
		$deviceDomain = null;
		$devicePort = self::DEVICE_PORT;
		$deviceData = [];
		$conflictingKeys = [];

		foreach ($response->answers as $answer) {
			if (
				$answer->type === Dns\Model\Message::TYPE_A
				&& preg_match(self::MATCH_DOMAIN, $answer->name) === 1
				&& is_string($answer->data)
				&& preg_match(self::MATCH_IP_ADDRESS, $answer->data) === 1
				&& $deviceIpAddress === null
			) {
				$deviceIpAddress = $answer->data;
			}

			if (
				$answer->type === Dns\Model\Message::TYPE_SRV
				&& preg_match(self::MATCH_NAME, $answer->name) === 1
				&& is_array($answer->data)
			) {
				if (array_key_exists('target', $answer->data)) {
					$deviceDomain = $answer->data['target'];
				}

				if (array_key_exists('port', $answer->data)) {
					$devicePort = intval($answer->data['port']);
				}
			}

			if (
				$answer->type === Dns\Model\Message::TYPE_TXT
				&& preg_match(self::MATCH_NAME, $answer->name) === 1
				&& is_array($answer->data)
			) {
				foreach ($answer->data as $dataRow) {
					$parts = explode('=', strval($dataRow), 2);
					$key = $parts[0];
					$value = $parts[1] ?? null;

					// The same text record twice with two values leaves no way to tell which one the device
					// meant, and which one wins is for the sender to choose
					if (array_key_exists($key, $deviceData) && $deviceData[$key] !== $value) {
						$conflictingKeys[$key] = true;
					}

					$deviceData[$key] = $value;
				}
			}
		}

		if (
			$deviceIpAddress !== null
			&& $deviceDomain !== null
			&& $deviceData !== []
			&& array_key_exists('id', $deviceData)
			&& is_string($deviceData['id'])
			&& array_key_exists('type', $deviceData)
			&& is_string($deviceData['type'])
			&& array_key_exists('seq', $deviceData)
			&& is_string($deviceData['seq'])
			&& array_key_exists('data1', $deviceData)
		) {
			if ($conflictingKeys !== []) {
				$this->logger->warning(
					'An announcement repeated a text record with another value, the announcement was dropped',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'lan-api',
						'records' => array_map(strval(...), array_keys($conflictingKeys)),
					],
				);

				return null;
			}

			if (preg_match(self::MATCH_DEVICE_ID, $deviceData['id']) !== 1) {
				$this->logger->warning(
					'An announcement came with an id that is not a device id, the announcement was dropped',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'lan-api',
						'device' => [
							'id' => substr($deviceData['id'], 0, 32),
						],
					],
				);

				return null;
			}

			$dataEncrypted = !array_key_exists('encrypt', $deviceData)
				? false
				: !in_array(
					is_string($deviceData['encrypt']) ? strtolower($deviceData['encrypt']) : null,
					self::PLAIN_DATA_FLAGS,
					true,
				);

			$data = array_filter(
				[
					$deviceData['data1'],
					array_key_exists('data2', $deviceData) ? $deviceData['data2'] : null,
					array_key_exists('data3', $deviceData) ? $deviceData['data3'] : null,
					array_key_exists('data4', $deviceData) ? $deviceData['data4'] : null,
				],
				static fn ($value) => $value !== null,
			);

			$deviceKey = $this->getDeviceKey($deviceData['id']);
			$keyKnown = $deviceKey !== null;

			// The text record is not authenticated, and its `encrypt` flag is whatever the sender wrote. A device
			// whose key is known is an encrypted device, so plain data announced for it is not taken as its state:
			// that would let anyone on the network skip the key
			if (!$dataEncrypted && $keyKnown) {
				$this->logger->warning(
					'Plain data was announced for a device with an encryption key, the announcement was dropped',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'lan-api',
						'device' => [
							'id' => $deviceData['id'],
						],
					],
				);

				return null;
			}

			if ($keyKnown || !$dataEncrypted) {
				if ($dataEncrypted) {
					foreach ($data as $index => $row) {
						$decrypted = Helpers\Transformer::decryptMessage(
							$row,
							$deviceKey,
							array_key_exists('iv', $deviceData) ? strval($deviceData['iv']) : '',
						);

						// A part that does not decrypt with the key is not the device's, even when the parts
						// around it do
						if ($decrypted === false) {
							throw new SonoffExceptions\LanApiError('The data of the announcement could not be decrypted');
						}

						$data[$index] = $decrypted;
					}
				}

				$data = Utils\Json::decode(implode($data), forceArrays: true);

				if (!is_array($data)) {
					throw new Utils\JsonException('The data of the announcement is not a list of values');
				}

				return $this->createEntity(
					Messages\Response\Lan\DeviceEvent::class,
					Utils\ArrayHash::from([
						'id' => $deviceData['id'],
						'ip_address' => $deviceIpAddress,
						'domain' => $deviceDomain,
						'port' => $devicePort,
						'type' => $deviceData['type'],
						'seq' => $deviceData['seq'],
						'iv' => array_key_exists('iv', $deviceData) ? $deviceData['iv'] : null,
						'encrypt' => $dataEncrypted,
						// The data is mapped together with the event, not before it: an entity made first would be
						// encoded to JSON as an object without properties, and the event could never be mapped
						'data' => Utils\ArrayHash::from($data),
					]),
				);
			}

			return $this->createEntity(
				Messages\Response\Lan\DeviceEvent::class,
				Utils\ArrayHash::from([
					'id' => $deviceData['id'],
					'ip_address' => $deviceIpAddress,
					'domain' => $deviceDomain,
					'port' => $devicePort,
					'type' => $deviceData['type'],
					'seq' => $deviceData['seq'],
					'iv' => array_key_exists('iv', $deviceData) ? $deviceData['iv'] : null,
					'encrypt' => true,
					'data' => null,
				]),
			);
		}

		return null;
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

	public function registerDeviceKey(string $deviceId, string $deviceKey): void
	{
		$this->encodeKeys[$this->canonicalDeviceId($deviceId)] = $deviceKey;
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Messages\Response\Lan\DeviceInfo> : Messages\Response\Lan\DeviceInfo)
	 *
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 */
	public function getDeviceInfo(
		string $id,
		string $ipAddress,
		int $port,
		bool $async = true,
	): Promise\PromiseInterface|Messages\Response\Lan\DeviceInfo
	{
		$deferred = new Promise\Deferred();

		$payload = new stdClass();
		$payload->sequence = strval(intval($this->clock->now()->format('Uv')));
		$payload->deviceid = $id;
		$payload->selfApikey = '123';
		$payload->data = new stdClass();

		$deviceKey = $this->getDeviceKey($id);

		try {
			if ($deviceKey !== null) {
				$iv = random_bytes(16);

				$encrypted = Helpers\Transformer::encryptMessage(
					Utils\Json::encode($payload->data),
					$deviceKey,
					base64_encode($iv),
				);

				if ($encrypted === false) {
					if ($async) {
						return Promise\reject(new SonoffExceptions\LanApiCall('Could encode data for request'));
					}

					throw new SonoffExceptions\LanApiCall('Could encode data for request');
				}

				$payload->encrypt = true;
				$payload->data = $encrypted;
				$payload->iv = base64_encode($iv);
			}

			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException) {
			if ($async) {
				return Promise\reject(new SonoffExceptions\LanApiCall('Could prepare data for request'));
			}

			throw new SonoffExceptions\LanApiCall('Could prepare data for request');
		} catch (Throwable) {
			if ($async) {
				return Promise\reject(new SonoffExceptions\LanApiCall('Could encode data for request'));
			}

			throw new SonoffExceptions\LanApiCall('Could encode data for request');
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			sprintf('http://%s:%d/zeroconf/info', $ipAddress, $port),
			[],
			[],
			$body,
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request, $id, $deviceKey): void {
					try {
						$deferred->resolve($this->parseGetDeviceInfo($request, $response, $id, $deviceKey));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceInfo($request, $result, $id, $deviceKey);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<bool> : bool)
	 *
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 */
	public function setDeviceState(
		string $id,
		string $ipAddress,
		int $port,
		string $parameter,
		string|int|float|bool $value,
		Types\ChannelGroup|null $group = null,
		int|null $outlet = null,
		bool $async = true,
	): Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$params = new stdClass();

		if ($group !== null && $outlet !== null) {
			$item = new stdClass();
			$item->{$parameter} = $value;
			$item->outlet = $outlet;

			$params->{$group->value} = [
				$item,
			];

		} else {
			$params->{$parameter} = $value;
		}

		$payload = new stdClass();
		$payload->sequence = strval(intval($this->clock->now()->format('Uv')));
		$payload->deviceid = $id;
		$payload->selfApikey = '123';
		$payload->data = $params;

		$deviceKey = $this->getDeviceKey($id);

		try {
			if ($deviceKey !== null) {
				$iv = random_bytes(16);

				$encrypted = Helpers\Transformer::encryptMessage(
					Utils\Json::encode($payload->data),
					$deviceKey,
					base64_encode($iv),
				);

				if ($encrypted === false) {
					if ($async) {
						return Promise\reject(new SonoffExceptions\LanApiCall('Could encode data for request'));
					}

					throw new SonoffExceptions\LanApiCall('Could encode data for request');
				}

				$payload->encrypt = true;
				$payload->data = $encrypted;
				$payload->iv = base64_encode($iv);
			}

			$body = Utils\Json::encode($payload);
		} catch (Utils\JsonException) {
			if ($async) {
				return Promise\reject(new SonoffExceptions\LanApiCall('Could prepare data for request'));
			}

			throw new SonoffExceptions\LanApiCall('Could prepare data for request');
		} catch (Throwable) {
			if ($async) {
				return Promise\reject(new SonoffExceptions\LanApiCall('Could encode data for request'));
			}

			throw new SonoffExceptions\LanApiCall('Could encode data for request');
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			sprintf('http://%s:%d/zeroconf/%s', $ipAddress, $port, ($group?->value ?? $parameter)),
			[
				'Connection' => 'close',
			],
			[],
			$body,
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request, $id, $deviceKey): void {
					try {
						$deferred->resolve($this->parseSetDeviceState($request, $response, $id, $deviceKey));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseSetDeviceState($request, $result, $id, $deviceKey);
	}

	/**
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 */
	private function parseGetDeviceInfo(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
		string $deviceId,
		string|null $deviceKey,
	): Messages\Response\Lan\DeviceInfo
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_INFO_MESSAGE_SCHEMA_FILENAME,
			$deviceId,
			$deviceKey,
		);

		$error = $body->offsetGet('error');
		assert(is_numeric($error));

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			throw new SonoffExceptions\LanApiCall(
				sprintf('Reading device info failed: %s', strval($body->offsetGet('message'))),
				$request,
				$response,
				intval($error),
			);
		}

		return $this->createEntity(Messages\Response\Lan\DeviceInfo::class, $data);
	}

	/**
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 */
	private function parseSetDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
		string $deviceId,
		string|null $deviceKey,
	): bool
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME,
			$deviceId,
			$deviceKey,
		);

		$error = $body->offsetGet('error');
		assert(is_numeric($error));

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			throw new SonoffExceptions\LanApiCall(
				sprintf('Setting device state failed: %s', strval($body->offsetGet('message'))),
				$request,
				$response,
				intval($error),
			);
		}

		return true;
	}

	/**
	 * Keys are kept, and looked up, under one form of the device id, whatever case the device is configured with
	 */
	private function canonicalDeviceId(string $deviceId): string
	{
		return strtolower($deviceId);
	}

	private function getDeviceKey(string $deviceId): string|null
	{
		return $this->encodeKeys[$this->canonicalDeviceId($deviceId)] ?? null;
	}

	/**
	 * @template T of Messages\Message
	 *
	 * @param class-string<T> $entity
	 *
	 * @return T
	 *
	 * @throws SonoffExceptions\LanApiError
	 */
	private function createEntity(string $entity, Utils\ArrayHash $data): Messages\Message
	{
		try {
			return $this->entityHelper->create(
				$entity,
				(array) Utils\Json::decode(Utils\Json::encode($data), forceArrays: true),
			);
		} catch (SonoffExceptions\Runtime $ex) {
			throw new SonoffExceptions\LanApiError('Could not map data to entity', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new SonoffExceptions\LanApiError(
				'Could not create entity from response',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 */
	private function validateResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
		string $schemaFilename,
		string $deviceId,
		string|null $deviceKey,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		$body = $this->getResponseBody($request, $response);

		if ($deviceKey !== null) {
			$body = $this->decryptResponseBody($request, $response, $body, $deviceId, $deviceKey);
		}

		try {
			return $this->schemaValidator->validate(
				$body,
				$this->getSchema($schemaFilename),
			);
		} catch (CoreExceptions\Logic | DocumentsExceptions\MalformedInput | ValuesExceptions\InvalidData $ex) {
			if ($throw) {
				throw new SonoffExceptions\LanApiCall(
					'Could not validate received response payload',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * A device with a key replies with its data encrypted with that key. Nothing else in the reply says it came from
	 * the device, so data that comes in plain, or that does not decrypt with the key, is refused like an announcement
	 * of it would be. A reply that carries no data at all has nothing to refuse.
	 *
	 * @throws SonoffExceptions\LanApiCall
	 * @throws SonoffExceptions\LanApiError
	 */
	private function decryptResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
		string $body,
		string $deviceId,
		string $deviceKey,
	): string
	{
		try {
			$reply = Utils\Json::decode($body);
		} catch (Utils\JsonException $ex) {
			throw new SonoffExceptions\LanApiCall(
				'Could not validate received response payload',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		}

		// The data of a reply that reports an error is never read, the error is
		if (
			!$reply instanceof stdClass
			|| !property_exists($reply, 'data')
			|| (property_exists($reply, 'error') && $reply->error !== 0)
		) {
			return $body;
		}

		if (!is_string($reply->data)) {
			$this->logger->warning(
				'A device with an encryption key replied with plain data, the reply was refused',
				[
					'source' => Sources\Connector::SONOFF->value,
					'type' => 'lan-api',
					'device' => [
						'id' => $deviceId,
					],
				],
			);

			throw new SonoffExceptions\LanApiError('Device with an encryption key replied with plain data');
		}

		$decrypted = property_exists($reply, 'iv') && is_string($reply->iv)
			? Helpers\Transformer::decryptMessage($reply->data, $deviceKey, $reply->iv)
			: false;

		try {
			$data = $decrypted !== false ? Utils\Json::decode($decrypted) : null;
		} catch (Utils\JsonException) {
			$data = null;
		}

		if (!$data instanceof stdClass) {
			$this->logger->warning(
				'The data a device with an encryption key replied with could not be decrypted, the reply was refused',
				[
					'source' => Sources\Connector::SONOFF->value,
					'type' => 'lan-api',
					'device' => [
						'id' => $deviceId,
					],
				],
			);

			throw new SonoffExceptions\LanApiError('Data of the reply could not be decrypted');
		}

		$reply->data = $data;

		try {
			return Utils\Json::encode($reply);
		} catch (Utils\JsonException $ex) {
			throw new SonoffExceptions\LanApiError('Decrypted reply could not be prepared', $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws SonoffExceptions\LanApiCall
	 */
	private function getResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): string
	{
		try {
			$response->getBody()->rewind();

			return $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new SonoffExceptions\LanApiCall(
				'Could not get content from response body',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Message\ResponseInterface> : Message\ResponseInterface)
	 *
	 * @throws SonoffExceptions\LanApiCall
	 */
	private function callRequest(
		Request $request,
		bool $async = true,
	): Promise\PromiseInterface|Message\ResponseInterface
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(
			sprintf(
				'Request: method = %s url = %s',
				$request->getMethod(),
				$request->getUri(),
			),
			[
				'source' => Sources\Connector::SONOFF->value,
				'type' => 'lan-api',
				'request' => [
					'method' => $request->getMethod(),
					'url' => strval($request->getUri()),
					'headers' => $request->getHeaders(),
					'body' => $request->getContent(),
				],
			],
		);

		if ($async) {
			try {
				$this->httpClientFactory
					->create()
					->send($request)
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $request): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								$deferred->reject(
									new SonoffExceptions\LanApiCall(
										'Could not get content from response body',
										$request,
										$response,
										$ex->getCode(),
										$ex,
									),
								);

								return;
							}

							$this->logger->debug(
								'Received response',
								[
									'source' => Sources\Connector::SONOFF->value,
									'type' => 'lan-api',
									'request' => [
										'method' => $request->getMethod(),
										'url' => strval($request->getUri()),
										'headers' => $request->getHeaders(),
										'body' => $request->getContent(),
									],
									'response' => [
										'code' => $response->getStatusCode(),
										'body' => $responseBody,
									],
								],
							);

							$deferred->resolve($response);
						},
						static function (Throwable $ex) use ($deferred, $request): void {
							$deferred->reject(
								new SonoffExceptions\LanApiCall(
									'Calling api endpoint failed',
									$request,
									null,
									$ex->getCode(),
									$ex,
								),
							);
						},
					);
			} catch (Throwable $ex) {
				return Promise\reject($ex);
			}

			return $deferred->promise();
		}

		try {
			$response = $this->httpClientFactory
				->create(false)
				->send($request);

			try {
				$responseBody = $response->getBody()->getContents();

				$response->getBody()->rewind();
			} catch (RuntimeException $ex) {
				throw new SonoffExceptions\LanApiCall(
					'Could not get content from response body',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			$this->logger->debug(
				'Received response',
				[
					'source' => Sources\Connector::SONOFF->value,
					'type' => 'lan-api',
					'request' => [
						'method' => $request->getMethod(),
						'url' => strval($request->getUri()),
						'headers' => $request->getHeaders(),
						'body' => $request->getContent(),
					],
					'response' => [
						'code' => $response->getStatusCode(),
						'body' => $responseBody,
					],
				],
			);

			return $response;
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			throw new SonoffExceptions\LanApiCall(
				'Calling api endpoint failed',
				$request,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws SonoffExceptions\LanApiError
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Sonoff\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new SonoffExceptions\LanApiError('Validation schema for response could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

	/**
	 * @param array<string, string|array<string>>|null $headers
	 * @param array<string, mixed> $params
	 *
	 * @throws SonoffExceptions\LanApiError
	 */
	private function createRequest(
		string $method,
		string $url,
		array|null $headers = null,
		array $params = [],
		string|null $body = null,
	): Request
	{
		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		try {
			return new Request($method, $url, $headers, $body);
		} catch (SonoffExceptions\InvalidArgument $ex) {
			throw new SonoffExceptions\LanApiError('Could not create request instance', $ex->getCode(), $ex);
		}
	}

}
