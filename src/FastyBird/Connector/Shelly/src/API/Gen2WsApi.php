<?php declare(strict_types = 1);

/**
 * Gen2WsApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           08.01.23
 */

namespace FastyBird\Connector\Shelly\API;

use Closure;
use DateTimeInterface;
use DomainException;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Connector\Shelly\ValueObjects;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Core\Tools\Helpers as ToolsHelpers;
use FastyBird\Core\Tools\Schemas as ToolsSchemas;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7 as gPsr;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\Http\Message;
use Ramsey\Uuid;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop;
use React\Promise;
use React\Socket;
use RuntimeException;
use stdClass;
use Throwable;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_reduce;
use function gethostbyname;
use function hash;
use function implode;
use function intval;
use function is_bool;
use function md5;
use function preg_match;
use function property_exists;
use function React\Async\async;
use function str_replace;
use function strpos;
use function strval;
use function time;
use function uniqid;
use const DIRECTORY_SEPARATOR;

/**
 * Generation 2 device websockets API interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen2WsApi
{

	use Nette\SmartObject;

	private const REQUEST_SRC = 'fb_ws_client';

	private const DEVICE_STATUS_METHOD = 'Shelly.GetStatus';

	private const SWITCH_SET_METHOD = 'Switch.Set';

	private const COVER_GO_TO_POSITION_METHOD = 'Cover.GoToPosition';

	private const LIGHT_SET_METHOD = 'Light.Set';

	private const SCRIPT_SET_ENABLED_METHOD = 'Script.Start';

	private const SCRIPT_SET_DISABLED_METHOD = 'Script.Stop';

	private const SMOKE_SET_METHOD = 'Smoke.Mute';

	private const NOTIFY_STATUS_METHOD = 'NotifyStatus';

	private const NOTIFY_FULL_STATUS_METHOD = 'NotifyFullStatus';

	private const NOTIFY_EVENT_METHOD = 'NotifyEvent';

	private const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'gen2_ws_state.json';

	private const DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME = 'gen2_ws_event.json';

	private const WAIT_FOR_REPLY_TIMEOUT = 10.0;

	private const PROPERTY_COMPONENT = '/^(?P<component>[a-zA-Z]+)_(?P<identifier>[0-9]+)(_(?P<attribute>[a-zA-Z0-9]+))?$/';

	private const COMPONENT_KEY = '/^(?P<component>[a-zA-Z]+)(:(?P<channel>[0-9_]+))?$/';

	/** @var array<Closure(): void> */
	public array $onConnected = [];

	/** @var array<Closure(): void> */
	public array $onDisconnected = [];

	/** @var array<Closure(Messages\Message $message): void> */
	public array $onMessage = [];

	/** @var array<Closure(): void> */
	public array $onLost = [];

	/** @var array<Closure(Throwable $error): void> */
	public array $onError = [];

	private bool $connecting = false;

	private bool $connected = false;

	/** @var array<string, ValueObjects\WsMessage> */
	private array $messages = [];

	/** @var array<string, string> */
	private array $validationSchemas = [];

	private string|null $clientIdentifier = null;

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	private Ratchet\Client\WebSocket|null $connection = null;

	private ValueObjects\WsSession|null $session = null;

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly string|null $ipAddress,
		private readonly string|null $domain,
		private readonly string|null $username,
		private readonly string|null $password,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Shelly\Logger $logger,
		private readonly DateTimeFactory\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly ToolsSchemas\Validator $schemaValidator,
		private readonly ObjectMapper\Processing\Processor $objectMapper,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function connect(): Promise\PromiseInterface
	{
		$this->messages = [];

		$this->connection = null;
		$this->connecting = true;
		$this->connected = false;

		$this->session = null;

		$this->lastConnectAttempt = $this->clock->getNow();
		$this->lost = null;
		$this->disconnected = null;

		$address = null;

		$deferred = new Promise\Deferred();

		if ($this->domain !== null) {
			$address = gethostbyname($this->domain);
		} elseif ($this->ipAddress !== null) {
			$address = $this->ipAddress;
		}

		if ($address === null) {
			return Promise\reject(new Exceptions\InvalidState('Device ip address or domain is not configured'));
		}

		try {
			$connector = new Socket\Connector(
				[
					'timeout' => 20,
				],
				$this->eventLoop,
			);
		} catch (InvalidArgumentException $ex) {
			return Promise\reject(
				new Exceptions\InvalidState('Socket connector could not be created', $ex->getCode(), $ex),
			);
		}

		// PRE-EXISTING DEFECT, tracked for a follow-up fix: ratchet/rfc6455 v0.4.1 requires a
		// RequestFactoryInterface here, so this call throws ArgumentCountError at runtime.
		// The frozen code targets rfc6455 0.3.x, whose constructor had no required argument;
		// ratchet/pawl permits ^0.3.1 || ^0.4.0 and no lock file was committed until now, so a
		// fresh resolve selected 0.4.1. Not fixed here: this phase must not change behaviour or
		// dependency constraints.
		// @phpstan-ignore arguments.count (see the note above)
		$negotiator = new RFC6455\Handshake\ClientNegotiator();

		$url = 'ws://' . $address . '/rpc';

		$headers = [
			'Connection' => 'Upgrade',
		];

		try {
			$uri = gPsr\Utils::uriFor($url);
			$uri = $uri->withScheme('HTTP');

			$headers += ['User-Agent' => 'Ratchet-Pawl/0.4.1'];

			$request = array_reduce(
				array_keys($headers),
				static fn (Message\RequestInterface $request, $header) => $request->withHeader(
					$header,
					$headers[$header],
				),
				$negotiator->generateRequest($uri),
			);

			if (!$request->hasHeader('Origin')) {
				$request = $request->withHeader(
					'Origin',
					str_replace('ws', 'http', $uri->getScheme()) . '://' . $uri->getHost(),
				);
			}
		} catch (Throwable $ex) {
			return Promise\reject(
				new Exceptions\InvalidState(
					'Device address to create WS connection could not be parsed',
					$ex->getCode(),
					$ex,
				),
			);
		}

		$port = $uri->getPort() ?? 80;

		$uriString = 'tcp://' . $uri->getHost() . ':' . $port;

		$connecting = $connector->connect($uriString);

		$connecting
			->then(
				function (Socket\ConnectionInterface $conn) use ($request, $negotiator, $connecting, $deferred): void {
					$earlyClose = static function () use ($deferred): void {
						$deferred->reject(new RuntimeException('Connection closed before handshake'));
					};

					$stream = $conn;

					$stream->on('close', $earlyClose);

					$buffer = '';

					$headerParser = function ($data) use (
						$stream,
						&$headerParser,
						&$buffer,
						$request,
						$negotiator,
						$connecting,
						$earlyClose,
						$deferred,
					): void {
						$buffer .= $data;

						if (strpos($buffer, "\r\n\r\n") === false) {
							return;
						}

						$stream->removeListener('data', $headerParser);

						$response = gPsr\Message::parseResponse($buffer);

						if (!$negotiator->validateResponse($request, $response)) {
							$connecting->then(static function (Socket\ConnectionInterface $connection): void {
								$connection->close();
							});
							$connecting->cancel();

							$this->logger->error(
								'Connection to device failed',
								[
									'source' => MetadataTypes\Sources\Connector::SHELLY->value,
									'type' => 'gen2-ws-api',
									'device' => [
										'id' => $this->id->toString(),
									],
								],
							);

							$this->connection = null;

							$this->connecting = false;
							$this->connected = false;

							$deferred->reject(new DomainException(gPsr\Message::toString($response)));

							$stream->close();

							return;
						}

						$stream->removeListener('close', $earlyClose);

						$connection = new Ratchet\Client\WebSocket($stream, $response, $request);

						$stream->emit('data', [$connection->response->getBody(), $stream]);

						$this->connectionCreated($connection);
					};

					$stream->on('data', $headerParser);
					$stream->write(gPsr\Message::toString($request));
				},
			)
			->catch(function (Throwable $ex) use ($connecting, $deferred): void {
				$this->connection = null;

				$this->connecting = false;
				$this->connected = false;

				$connecting->then(static function (Socket\ConnectionInterface $connection): void {
					$connection->close();
				});
				$connecting->cancel();

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	public function disconnect(): void
	{
		$this->connection?->close();
		$this->connection = null;

		$this->connecting = false;
		$this->connected = false;

		$this->disconnected = $this->clock->getNow();

		$this->session = null;

		foreach ($this->messages as $message) {
			$message->getDeferred()?->reject(new Exceptions\WsCall('Closing connection to device'));

			if ($message->getTimer() !== null) {
				$this->eventLoop->cancelTimer($message->getTimer());
			}
		}

		$this->messages = [];
	}

	public function isConnecting(): bool
	{
		return $this->connecting;
	}

	public function isConnected(): bool
	{
		return $this->connection !== null && !$this->connecting && $this->connected;
	}

	public function getLastConnectAttempt(): DateTimeInterface|null
	{
		return $this->lastConnectAttempt;
	}

	public function getDisconnected(): DateTimeInterface|null
	{
		return $this->disconnected;
	}

	public function getLost(): DateTimeInterface|null
	{
		return $this->lost;
	}

	/**
	 * @return Promise\PromiseInterface<Messages\Response\Gen2\GetDeviceState>
	 */
	public function readStates(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		try {
			$messageFrame = $this->objectMapper->process(
				[
					'id' => uniqid(),
					'src' => $this->getClientIdentifier(),
					'method' => self::DEVICE_STATUS_METHOD,
					'params' => null,
					'auth' => $this->session?->toArray(),
				],
				ValueObjects\WsFrame::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			return Promise\reject(
				new Exceptions\WsError('Message frame could not be created: ' . $errorPrinter->printError($ex)),
			);
		}

		try {
			$this->sendRequest($messageFrame, $deferred);
		} catch (Exceptions\WsError $ex) {
			return Promise\reject($ex);
		}

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<Messages\Response\Gen2\GetDeviceState>
	 */
	public function writeState(
		string $component,
		int|float|string|bool $value,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connection === null) {
			return Promise\reject(
				new Exceptions\InvalidState('Connection with device is not established'),
			);
		}

		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $propertyMatches) !== 1
			|| !array_key_exists('attribute', $propertyMatches)
		) {
			return Promise\reject(new Exceptions\InvalidState('Property identifier is not in expected format'));
		}

		try {
			$componentMethod = $this->buildComponentMethod($component, $value);

		} catch (Exceptions\InvalidState) {
			return Promise\reject(new Exceptions\InvalidState('Component method could not be created'));
		}

		try {
			$componentAttribute = $this->buildComponentAttribute($component);

		} catch (Exceptions\InvalidState) {
			return Promise\reject(new Exceptions\InvalidState('Component attribute could not be created'));
		}

		try {
			$messageFrame = $componentAttribute !== null ? $this->objectMapper->process(
				[
					'id' => uniqid(),
					'src' => $this->getClientIdentifier(),
					'method' => $componentMethod,
					'params' => [
						'id' => intval($propertyMatches['identifier']),
						$componentAttribute->value => $value,
					],
					'auth' => $this->session?->toArray(),
				],
				ValueObjects\WsFrame::class,
			) : $this->objectMapper->process(
				[
					'id' => uniqid(),
					'src' => $this->getClientIdentifier(),
					'method' => $componentMethod,
					'params' => [
						'id' => intval($propertyMatches['identifier']),
					],
					'auth' => $this->session?->toArray(),
				],
				ValueObjects\WsFrame::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			return Promise\reject(
				new Exceptions\WsError('Message frame could not be created: ' . $errorPrinter->printError($ex)),
			);
		}

		try {
			$this->sendRequest($messageFrame, $deferred);
		} catch (Exceptions\WsError $ex) {
			return Promise\reject($ex);
		}

		return $deferred->promise();
	}

	private function connectionCreated(Ratchet\Client\WebSocket $connection): void
	{
		$this->connection = $connection;
		$this->connecting = false;
		$this->connected = true;

		$this->lost = null;
		$this->disconnected = null;

		$this->logger->debug(
			'Connected to device sockets server',
			[
				'source' => MetadataTypes\Sources\Connector::SHELLY->value,
				'type' => 'gen2-ws-api',
				'device' => [
					'id' => $this->id->toString(),
				],
			],
		);

		$connection->on(
			'message',
			function (RFC6455\Messaging\MessageInterface $socketMessage): void {
				try {
					$payload = Utils\Json::decode($socketMessage->getPayload());

				} catch (Utils\JsonException $ex) {
					$this->logger->debug(
						'Received message from device could not be decoded',
						[
							'source' => MetadataTypes\Sources\Connector::SHELLY->value,
							'type' => 'gen2-ws-api',
							'exception' => ToolsHelpers\Logger::buildException($ex),
							'device' => [
								'id' => $this->id->toString(),
							],
						],
					);

					Utils\Arrays::invoke($this->onError, $ex);

					return;
				}

				if (!$payload instanceof stdClass) {
					return;
				}

				if (
					property_exists($payload, 'method')
					&& property_exists($payload, 'params')
				) {
					if (
						$payload->method === self::NOTIFY_STATUS_METHOD
						|| $payload->method === self::NOTIFY_FULL_STATUS_METHOD
					) {
						try {
							$message = $this->parseDeviceStatusResponse(
								Utils\Json::encode($payload->params),
							);

							Utils\Arrays::invoke($this->onMessage, $message);
						} catch (Exceptions\WsCall | Exceptions\WsError $ex) {
							$this->logger->error(
								'Could not handle received device status message',
								[
									'source' => MetadataTypes\Sources\Connector::SHELLY->value,
									'type' => 'gen2-ws-api',
									'exception' => ToolsHelpers\Logger::buildException(
										$ex,
										$ex instanceof Exceptions\WsError,
									),
									'device' => [
										'id' => $this->id->toString(),
									],
									'response' => [
										'body' => Utils\Json::encode($payload->params),
										'schema' => self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
									],
								],
							);

							Utils\Arrays::invoke($this->onError, $ex);
						}
					} elseif ($payload->method === self::NOTIFY_EVENT_METHOD) {
						try {
							$message = $this->parseDeviceEventsResponse(
								Utils\Json::encode($payload->params),
							);

							Utils\Arrays::invoke($this->onMessage, $message);
						} catch (Exceptions\WsCall | Exceptions\WsError $ex) {
							$this->logger->error(
								'Could not handle received event message',
								[
									'source' => MetadataTypes\Sources\Connector::SHELLY->value,
									'type' => 'gen2-ws-api',
									'exception' => ToolsHelpers\Logger::buildException(
										$ex,
										$ex instanceof Exceptions\WsError,
									),
									'device' => [
										'id' => $this->id->toString(),
									],
									'response' => [
										'body' => Utils\Json::encode($payload->params),
										'schema' => self::DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME,
									],
								],
							);

							Utils\Arrays::invoke($this->onError, $ex);
						}
					} else {
						$this->logger->warning(
							'Device respond with unsupported method',
							[
								'source' => MetadataTypes\Sources\Connector::SHELLY->value,
								'type' => 'gen2-ws-api',
								'device' => [
									'id' => $this->id->toString(),
								],
								'response' => [
									'method' => $payload->method,
									'payload' => $socketMessage->getPayload(),
								],
							],
						);
					}
				}

				if (
					!property_exists($payload, 'id')
					|| !array_key_exists($payload->id, $this->messages)
				) {
					return;
				}

				if (property_exists($payload, 'result')) {
					if ($this->messages[$payload->id]->getFrame()->getMethod() === self::DEVICE_STATUS_METHOD) {
						try {
							$message = $this->parseDeviceStatusResponse(
								Utils\Json::encode($payload->result),
							);

							$this->messages[$payload->id]->getDeferred()?->resolve($message);
						} catch (Exceptions\WsCall | Exceptions\WsError $ex) {
							$this->logger->error(
								'Could not handle received response device status message',
								[
									'source' => MetadataTypes\Sources\Connector::SHELLY->value,
									'type' => 'gen2-ws-api',
									'exception' => ToolsHelpers\Logger::buildException(
										$ex,
										$ex instanceof Exceptions\WsError,
									),
									'device' => [
										'id' => $this->id->toString(),
									],
									'response' => [
										'body' => Utils\Json::encode($payload->result),
										'schema' => self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
									],
								],
							);

							$this->messages[$payload->id]->getDeferred()?->reject(
								new Exceptions\WsError('Could not decode received payload'),
							);
						}
					} elseif (
						$this->messages[$payload->id]->getFrame()->getMethod() === self::SWITCH_SET_METHOD
						|| $this->messages[$payload->id]->getFrame()->getMethod() === self::COVER_GO_TO_POSITION_METHOD
						|| $this->messages[$payload->id]->getFrame()->getMethod() === self::LIGHT_SET_METHOD
					) {
						$this->messages[$payload->id]->getDeferred()?->resolve(true);
					} else {
						$this->messages[$payload->id]->getDeferred()?->reject(
							new Exceptions\WsCall('Received response could not be processed'),
						);
					}

					if ($this->messages[$payload->id]->getTimer() !== null) {
						$this->eventLoop->cancelTimer($this->messages[$payload->id]->getTimer());
					}

					unset($this->messages[$payload->id]);

					return;
				} elseif (property_exists($payload, 'error')) {
					if (
						property_exists($payload->error, 'code')
						&& property_exists($payload->error, 'message')
						&& $payload->error->code === StatusCodeInterface::STATUS_UNAUTHORIZED
						&& $this->session === null
					) {
						$errorMessage = Utils\Json::decode(strval($payload->error->message));

						if (
							$errorMessage instanceof stdClass
							&& property_exists($errorMessage, 'realm')
							&& property_exists($errorMessage, 'nonce')
						) {
							$nc = 1;
							$clientNonce = time();

							$ha1 = hash(
								'sha256',
								implode(
									':',
									[
										$this->username,
										$errorMessage->realm,
										$this->password,
									],
								),
							);
							$ha2 = hash('sha256', 'dummy_method:dummy_uri');
							$response = hash(
								'sha256',
								implode(
									':',
									[
										$ha1,
										$errorMessage->nonce,
										$nc,
										$clientNonce,
										'auth',
										$ha2,
									],
								),
							);

							try {
								$this->session = $this->objectMapper->process(
									[
										'realm' => $errorMessage->realm,
										'username' => strval($this->username),
										'nonce' => $errorMessage->nonce,
										'cnonce' => $clientNonce,
										'response' => $response,
										'nc' => $nc,
										'algorithm' => 'SHA-256',
									],
									ValueObjects\WsSession::class,
								);
							} catch (ObjectMapper\Exception\InvalidData $ex) {
								$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
									new ObjectMapper\Printers\TypeToStringConverter(),
								);

								throw new Exceptions\WsError(
									'Connection session could not be created: ' . $errorPrinter->printError(
										$ex,
									),
								);
							}

							try {
								$messageFrame = $this->objectMapper->process(
									[
										'id' => $this->messages[$payload->id]->getFrame()->getId(),
										'src' => $this->messages[$payload->id]->getFrame()->getSrc(),
										'method' => $this->messages[$payload->id]->getFrame()->getMethod(),
										'params' => $this->messages[$payload->id]->getFrame()->getParams(),
										'auth' => $this->session->toArray(),
									],
									ValueObjects\WsFrame::class,
								);
							} catch (ObjectMapper\Exception\InvalidData $ex) {
								$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
									new ObjectMapper\Printers\TypeToStringConverter(),
								);

								throw new Exceptions\WsError(
									'Message frame could not be created: ' . $errorPrinter->printError($ex),
								);
							}

							$this->sendRequest(
								$messageFrame,
								$this->messages[$payload->id]->getDeferred(),
							);

							return;
						}
					}

					$this->logger->warning(
						'Device respond with error',
						[
							'source' => MetadataTypes\Sources\Connector::SHELLY->value,
							'type' => 'gen2-ws-api',
							'device' => [
								'id' => $this->id->toString(),
							],
							'error' => [
								'code' => property_exists(
									$payload->error,
									'message',
								) ? $payload->code : null,
								'message' => property_exists(
									$payload->error,
									'message',
								) ? $payload->message : null,
							],
						],
					);
				}

				$this->messages[$payload->id]->getDeferred()?->reject(
					new Exceptions\WsError('Received device response could not be processed'),
				);

				if ($this->messages[$payload->id]->getTimer() !== null) {
					$this->eventLoop->cancelTimer($this->messages[$payload->id]->getTimer());
				}

				unset($this->messages[$payload->id]);
			},
		);

		$connection->on('error', function (Throwable $ex): void {
			$this->lost();

			Utils\Arrays::invoke($this->onError, $ex);
		});

		$connection->on('close', function ($code = null, $reason = null): void {
			$this->logger->debug(
				'Connection with device was closed',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY->value,
					'type' => 'gen2-ws-api',
					'connection' => [
						'code' => $code,
						'reason' => $reason,
					],
					'device' => [
						'id' => $this->id->toString(),
					],
				],
			);

			$this->disconnect();

			Utils\Arrays::invoke($this->onDisconnected);
		});

		Utils\Arrays::invoke($this->onConnected);
	}

	private function lost(): void
	{
		$this->lost = $this->clock->getNow();

		Utils\Arrays::invoke($this->onLost);

		$this->disconnect();
	}

	/**
	 * @param Promise\Deferred<Messages\Response\Gen2\GetDeviceState|bool>|null $deferred
	 *
	 * @throws Exceptions\WsError
	 */
	private function sendRequest(ValueObjects\WsFrame $frame, Promise\Deferred|null $deferred = null): void
	{
		$timeout = $this->eventLoop->addTimer(
			self::WAIT_FOR_REPLY_TIMEOUT,
			async(function () use ($deferred, $frame): void {
				$deferred?->reject(new Exceptions\WsCallTimeout('Sending command to device failed'));

				if (array_key_exists($frame->getId(), $this->messages)) {
					if ($this->messages[$frame->getId()]->getTimer() !== null) {
						$this->eventLoop->cancelTimer($this->messages[$frame->getId()]->getTimer());
					}

					unset($this->messages[$frame->getId()]);
				}
			}),
		);

		try {
			$this->messages[$frame->getId()] = $this->objectMapper->process(
				[
					'frame' => $frame,
					'deferred' => $deferred,
					'timer' => $timeout,
				],
				ValueObjects\WsMessage::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\WsError('Message could not be created: ' . $errorPrinter->printError($ex));
		}

		$this->connection?->send(strval($frame));
	}

	/**
	 * @throws Exceptions\WsError
	 */
	private function parseDeviceStatusResponse(
		string $payload,
	): Messages\Response\Gen2\GetDeviceState
	{
		$data = $this->validatePayload(
			$payload,
			self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = $devicePower = $scripts = $smoke = $voltmeters = [];
		$ethernet = $wifi = null;

		foreach ($data as $key => $state) {
			if (
				$state instanceof Utils\ArrayHash
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& Types\ComponentType::tryFrom($componentMatches['component']) !== null
			) {
				if ($componentMatches['component'] === Types\ComponentType::SWITCH->value) {
					$switches[] = array_merge(
						(array) $state,
						[
							'aenergy' => $state->offsetGet('aenergy') instanceof Utils\ArrayHash
								? (array) $state->offsetGet('aenergy')
								: $state->offsetGet('aenergy'),
							'temperature' => $state->offsetGet('temperature') instanceof Utils\ArrayHash
								? (array) $state->offsetGet('temperature')
								: $state->offsetGet('temperature'),
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER->value) {
					$covers[] = array_merge(
						(array) $state,
						[
							'aenergy' => $state->offsetGet('aenergy') instanceof Utils\ArrayHash
								? (array) $state->offsetGet('aenergy')
								: $state->offsetGet('aenergy'),
							'temperature' => $state->offsetGet('temperature') instanceof Utils\ArrayHash
								? (array) $state->offsetGet('temperature')
								: $state->offsetGet('temperature'),
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT->value) {
					$lights[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT->value) {
					$inputs[] = array_merge(
						(array) $state,
						[
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE->value) {
					$temperature[] = array_merge(
						(array) $state,
						[
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY->value) {
					$humidity[] = array_merge(
						(array) $state,
						[
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::DEVICE_POWER->value) {
					$devicePower[] = array_merge(
						(array) $state,
						[
							'battery' => $state->offsetGet('battery') instanceof Utils\ArrayHash
								? (array) $state->offsetGet('battery')
								: $state->offsetGet('battery'),
							'external' => $state->offsetGet('external') instanceof Utils\ArrayHash
								? (array) $state->offsetGet('external')
								: $state->offsetGet('external'),
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::SCRIPT->value) {
					$scripts[] = array_merge(
						(array) $state,
						[
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::SMOKE->value) {
					$smoke[] = array_merge(
						(array) $state,
						[
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::VOLTMETER->value) {
					$voltmeters[] = array_merge(
						(array) $state,
						[
							'errors' => $state->offsetExists('errors')
								? (array) $state->offsetGet('errors')
								: [],
						],
					);
				} elseif ($componentMatches['component'] === Types\ComponentType::ETHERNET->value) {
					$ethernet = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::WIFI->value) {
					$wifi = (array) $state;
				}
			}
		}

		return $this->createMessage(Messages\Response\Gen2\GetDeviceState::class, Utils\ArrayHash::from([
			Types\ComponentType::SWITCH->value => $switches,
			Types\ComponentType::COVER->value => $covers,
			Types\ComponentType::INPUT->value => $inputs,
			Types\ComponentType::LIGHT->value => $lights,
			Types\ComponentType::TEMPERATURE->value => $temperature,
			Types\ComponentType::HUMIDITY->value => $humidity,
			Types\ComponentType::DEVICE_POWER->value => $devicePower,
			Types\ComponentType::SCRIPT->value => $scripts,
			Types\ComponentType::SMOKE->value => $smoke,
			Types\ComponentType::VOLTMETER->value => $voltmeters,
			Types\ComponentType::ETHERNET->value => $ethernet,
			Types\ComponentType::WIFI->value => $wifi,
		]));
	}

	/**
	 * @throws Exceptions\WsError
	 */
	private function parseDeviceEventsResponse(
		string $payload,
	): Messages\Response\Gen2\DeviceEvent
	{
		$data = $this->validatePayload($payload, self::DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME);

		$events = [];

		foreach ((array) $data->offsetGet('events') as $event) {
			if ($event instanceof Utils\ArrayHash) {
				$events[] = [
					'component' => $event->offsetGet('component'),
					'id' => $event->offsetGet('id'),
					'event' => $event->offsetGet('event'),
					'data' => $event->offsetExists('data')
						? (
							$event->offsetGet('data') instanceof Utils\ArrayHash
								? (array) $event->offsetGet('data')
								: $event->offsetGet('data')
						)
						: null,
					'timestamp' => $event->offsetGet('ts'),
				];
			}
		}

		return $this->createMessage(
			Messages\Response\Gen2\DeviceEvent::class,
			Utils\ArrayHash::from([
				'events' => $events,
			]),
		);
	}

	/**
	 * @template T of Messages\Message
	 *
	 * @param class-string<T> $message
	 *
	 * @return T
	 *
	 * @throws Exceptions\WsError
	 */
	protected function createMessage(string $message, Utils\ArrayHash $data): Messages\Message
	{
		try {
			return $this->messageBuilder->create(
				$message,
				(array) Utils\Json::decode(Utils\Json::encode($data), forceArrays: true),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\WsError('Could not map payload to message', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\WsError('Could not create message from payload', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\WsError
	 */
	protected function validatePayload(
		string $payload,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		try {
			return $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (ToolsExceptions\Logic | ToolsExceptions\MalformedInput | ToolsExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\WsError(
					'Could not validate received payload',
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\WsError
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'response' . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new Exceptions\WsError('Validation schema for payload could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildComponentMethod(string $component, int|float|string|bool $value): string
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $componentMatches) !== 1
			|| !array_key_exists('attribute', $componentMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not in expected format');
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SWITCH->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
		) {
			return self::SWITCH_SET_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::COVER->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::TARGET_POSITION->value
		) {
			return self::COVER_GO_TO_POSITION_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT->value
			&& (
				$componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
				|| $componentMatches['attribute'] === Types\ComponentAttributeType::BRIGHTNESS->value
			)
		) {
			return self::LIGHT_SET_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SCRIPT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::RUNNING->value
			&& is_bool($value)
		) {
			return $value ? self::SCRIPT_SET_ENABLED_METHOD : self::SCRIPT_SET_DISABLED_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SMOKE->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::MUTE->value
		) {
			return self::SMOKE_SET_METHOD;
		}

		throw new Exceptions\InvalidState('Property method could not be build');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildComponentAttribute(string $component): Types\ComponentActionAttribute|null
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $componentMatches) !== 1
			|| !array_key_exists('attribute', $componentMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not in expected format');
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SWITCH->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
		) {
			return Types\ComponentActionAttribute::ON;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::COVER->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::TARGET_POSITION->value
		) {
			return Types\ComponentActionAttribute::POSITION;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::OUTPUT->value
		) {
			return Types\ComponentActionAttribute::ON;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::BRIGHTNESS->value
		) {
			return Types\ComponentActionAttribute::BRIGHTNESS;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SCRIPT->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::RUNNING->value
		) {
			return null;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SMOKE->value
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::MUTE->value
		) {
			return null;
		}

		throw new Exceptions\InvalidState('Property attribute could not be build');
	}

	private function getClientIdentifier(): string
	{
		if ($this->clientIdentifier === null) {
			$this->clientIdentifier = self::REQUEST_SRC . '_' . uniqid();
		}

		return $this->clientIdentifier;
	}

}
