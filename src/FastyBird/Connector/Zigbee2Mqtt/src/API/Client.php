<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\API;

use BinSoul\Net\Mqtt;
use Closure;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use React\EventLoop;
use React\Promise;
use React\Socket;
use React\Stream;
use Throwable;
use function array_shift;
use function array_values;
use function assert;
use function call_user_func;
use function count;
use function floor;
use function is_array;
use function sprintf;

/**
 * MQTT client service
 *
 * The following events are emitted:
 *  - open - The network connection to the server is established.
 *  - close - The network connection to the server is closed.
 *  - warning - An event of severity "warning" occurred.
 *  - error - An event of severity "error" occurred.
 *  - connect - The client connected to the broker.
 *  - disconnect - The client disconnected from the broker.
 *  - subscribe - The client subscribed to a topic filter.
 *  - unsubscribe - The client unsubscribed from topic filter.
 *  - publish - A message was published.
 *  - message - A message was received.
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Client
{

	use Nette\SmartObject;

	/** @var array<Closure(Mqtt\Connection $connection): void> */
	public array $onConnect = [];

	/** @var array<Closure(): void> */
	public array $onDisconnect = [];

	/** @var array<Closure(): void> */
	public array $onClose = [];

	/** @var array<Closure(Mqtt\Message $message): void> */
	public array $onMessage = [];

	/** @var array<Closure(Mqtt\Message $message): void> */
	public array $onPublish = [];

	/** @var array<Closure(Mqtt\Subscription $subscription): void> */
	public array $onSubscribe = [];

	/** @var array<Closure(Mqtt\Subscription $subscription): void> */
	public array $onUnsubscribe = [];

	/** @var array<Closure(Throwable $error): void> */
	public array $onWarning = [];

	/** @var array<Closure(Throwable $error): void> */
	public array $onError = [];

	private bool $isConnected = false;

	private bool $isConnecting = false;

	private bool $isDisconnecting = false;

	/** @var Closure(Mqtt\Connection $connection): void|null */
	private Closure|null $onCloseCallback = null;

	/** @var array<EventLoop\TimerInterface> */
	private array $timer = [];

	/** @var array<Clients\Flow> */
	private array $receivingFlows = [];

	/** @var array<Clients\Flow> */
	private array $sendingFlows = [];

	private Clients\Flow|null $writtenFlow = null;

	private Stream\DuplexStreamInterface|null $stream = null;

	private Mqtt\StreamParser $parser;

	private Mqtt\ClientIdentifierGenerator $identifierGenerator;

	private Mqtt\Connection|null $connection = null;

	private Mqtt\FlowFactory $flowFactory;

	public function __construct(
		private readonly string $clientId,
		private readonly string $address,
		private readonly int $port,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly string|null $username = null,
		private readonly string|null $password = null,
		Mqtt\ClientIdentifierGenerator|null $identifierGenerator = null,
		Mqtt\FlowFactory|null $flowFactory = null,
		Mqtt\StreamParser|null $parser = null,
	)
	{
		$this->parser = $parser ?? new Mqtt\StreamParser(new Mqtt\DefaultPacketFactory());

		$this->parser->onError(function (Throwable $ex): void {
			$this->handleWarning($ex);
		});

		$this->identifierGenerator = $identifierGenerator ?? new Mqtt\DefaultIdentifierGenerator();

		$this->flowFactory = $flowFactory ?? new Mqtt\DefaultFlowFactory(
			$this->identifierGenerator,
			new Mqtt\DefaultIdentifierGenerator(),
			new Mqtt\DefaultPacketFactory(),
		);
	}

	public function isConnected(): bool
	{
		return $this->isConnected;
	}

	/**
	 * Connects to a broker
	 *
	 * @return Promise\PromiseInterface<mixed>
	 *
	 * @throws InvalidArgumentException
	 * @throws CoreExceptions\InvalidArgument
	 */
	public function connect(int $timeout = 5): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->isConnected || $this->isConnecting) {
			return Promise\reject(new Zigbee2MqttExceptions\Logic('The client is already connected'));
		}

		$this->isConnecting = true;
		$this->isConnected = false;

		$connection = new Mqtt\DefaultConnection(
			$this->username ?? '',
			$this->password ?? '',
			null,
			$this->clientId,
		);

		if ($connection->getClientID() === '') {
			$connection = $connection->withClientID($this->identifierGenerator->generateClientIdentifier());
		}

		$this->establishConnection($this->address, $this->port, $timeout)
			->then(function (Stream\DuplexStreamInterface $stream) use ($connection, $deferred, $timeout): void {
				$this->stream = $stream;

				$this->onOpen($connection);

				$this->registerClient($connection, $timeout)
					->then(function ($result) use ($connection, $deferred): void {
						$this->isConnecting = false;
						$this->isConnected = true;
						$this->connection = $connection;

						// Broker connected
						$this->logger->info(
							sprintf('Connected to MQTT broker with client id %s', $connection->getClientID()),
							[
								'source' => Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'mqtt-api',
								'credentials' => [
									'username' => $connection->getUsername(),
									'client_id' => $connection->getClientID(),
								],
							],
						);

						Utils\Arrays::invoke($this->onConnect);

						$deferred->resolve($result ?? $connection);
					})
					->catch(function (Throwable $ex) use ($deferred): void {
						$this->isConnecting = false;

						$this->handleError($ex);

						$deferred->reject($ex);

						$this->stream?->close();
					});
			})
			->catch(function (Throwable $ex) use ($deferred): void {
				$this->isConnecting = false;

				$this->handleError($ex);

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * Disconnects from a broker
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function disconnect(int $timeout = 5): Promise\PromiseInterface
	{
		if (!$this->isConnected || $this->isDisconnecting || $this->connection === null) {
			return Promise\reject(new Zigbee2MqttExceptions\Logic('The client is not connected'));
		}

		$this->isDisconnecting = true;

		$deferred = new Promise\Deferred();

		$isResolved = false;

		/** @var mixed $flowResult */
		$flowResult = null;

		$this->onCloseCallback = function ($connection) use ($deferred, &$isResolved, &$flowResult): void {
			if (!$isResolved) {
				$isResolved = true;

				if ($connection) {
					// Broker disconnected
					$this->logger->info(
						sprintf('Disconnected from MQTT broker with client id %s', $connection->getClientID()),
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'mqtt-api',
							'credentials' => [
								'username' => $connection->getUsername(),
								'client_id' => $connection->getClientID(),
							],
						],
					);

					Utils\Arrays::invoke($this->onDisconnect);
				}

				$deferred->resolve($flowResult ?? $connection);
			}
		};

		$this->startFlow($this->flowFactory->buildOutgoingDisconnectFlow($this->connection), true)
			->then(function ($result) use ($timeout, &$flowResult): void {
				$flowResult = $result;

				$this->timer[] = $this->eventLoop->addTimer(
					$timeout,
					function (): void {
						$this->stream?->close();
					},
				);
			})
			->catch(function ($exception) use ($deferred, &$isResolved): void {
				if (!$isResolved) {
					$isResolved = true;
					$this->isDisconnecting = false;

					$deferred->reject($exception);
				}
			});

		return $deferred->promise();
	}

	/**
	 * Subscribes to a topic filter
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function subscribe(Mqtt\Subscription $subscription): Promise\PromiseInterface
	{
		if (!$this->isConnected) {
			return Promise\reject(new Zigbee2MqttExceptions\Logic('The client is not connected'));
		}

		return $this->startFlow($this->flowFactory->buildOutgoingSubscribeFlow([$subscription]));
	}

	/**
	 * Unsubscribes from a topic filter
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function unsubscribe(Mqtt\Subscription $subscription): Promise\PromiseInterface
	{
		if (!$this->isConnected) {
			return Promise\reject(new Zigbee2MqttExceptions\Logic('The client is not connected'));
		}

		$deferred = new Promise\Deferred();

		$this->startFlow($this->flowFactory->buildOutgoingUnsubscribeFlow([$subscription]))
			->then(static function (mixed $subscriptions) use ($deferred): void {
				assert(is_array($subscriptions));
				$deferred->resolve(array_shift($subscriptions));
			})
			->catch(static function ($exception) use ($deferred): void {
				$deferred->reject($exception);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function publish(
		string $topic,
		string|null $payload = null,
		int $qos = Zigbee2Mqtt\Constants::MQTT_API_QOS_0,
		bool $retain = false,
	): Promise\PromiseInterface
	{
		$message = new Mqtt\DefaultMessage($topic, ($payload ?? ''), $qos, $retain);

		if (!$this->isConnected) {
			return Promise\reject(new Zigbee2MqttExceptions\Logic('The client is not connected'));
		}

		return $this->startFlow($this->flowFactory->buildOutgoingPublishFlow($message));
	}

	private function onOpen(Mqtt\Connection $connection): void
	{
		// Network connection established
		$this->logger->info(
			'Established connection to MQTT broker',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'mqtt-api',
				'credentials' => [
					'username' => $connection->getUsername(),
					'client_id' => $connection->getClientID(),
				],
			],
		);
	}

	/**
	 * Establishes a network connection to a server
	 *
	 * @return Promise\PromiseInterface<Stream\DuplexStreamInterface>
	 *
	 * @throws InvalidArgumentException
	 */
	private function establishConnection(string $host, int $port, int $timeout): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$future = null;

		$timer = $this->eventLoop->addTimer(
			$timeout,
			static function () use ($deferred, $timeout, &$future): void {
				$exception = new Zigbee2MqttExceptions\Runtime(
					sprintf('Connection timed out after %d seconds', $timeout),
				);
				$deferred->reject($exception);

				// @phpstan-ignore-next-line
				if ($future instanceof Promise\PromiseInterface) {
					$future->cancel();
				}

				$future = null;
			},
		);

		$future = $this->getConnector()->connect($host . ':' . $port);

		$future
			->finally(function () use ($timer): void {
				$this->eventLoop->cancelTimer($timer);
			})
			->then(function (Stream\DuplexStreamInterface $stream) use ($deferred): void {
				$stream->on('data', function ($data): void {
					$this->handleReceive($data);
				});

				$stream->on('close', function (): void {
					$this->handleClose();
				});

				$stream->on('error', function (Throwable $ex): void {
					$this->handleError($ex);
				});

				$deferred->resolve($stream);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * Registers a new client with the broker
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	private function registerClient(Mqtt\Connection $connection, int $timeout): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$responseTimer = $this->eventLoop->addTimer(
			$timeout,
			static function () use ($deferred, $timeout): void {
				$exception = new Zigbee2MqttExceptions\Runtime(sprintf('No response after %d seconds', $timeout));
				$deferred->reject($exception);
			},
		);

		$this->startFlow($this->flowFactory->buildOutgoingConnectFlow($connection), true)
			->finally(function () use ($responseTimer): void {
				$this->eventLoop->cancelTimer($responseTimer);
			})
			->then(function ($result) use ($connection, $deferred): void {
				$this->timer[] = $this->eventLoop->addPeriodicTimer(
					floor($connection->getKeepAlive() * 0.75),
					function (): void {
						$this->startFlow($this->flowFactory->buildOutgoingPingFlow());
					},
				);

				$deferred->resolve($result ?? $connection);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * Handles incoming data
	 *
	 * @throws Zigbee2MqttExceptions\Runtime
	 */
	private function handleReceive(string $data): void
	{
		if (!$this->isConnected && !$this->isConnecting) {
			return;
		}

		$flowCount = count($this->receivingFlows);

		$packets = $this->parser->push($data);

		foreach ($packets as $packet) {
			$this->handlePacket($packet);
		}

		if ($flowCount > count($this->receivingFlows)) {
			$this->receivingFlows = array_values($this->receivingFlows);
		}

		$this->handleSend();
	}

	/**
	 * Handles an incoming packet
	 *
	 * @throws Zigbee2MqttExceptions\Runtime
	 */
	private function handlePacket(Mqtt\Packet $packet): void
	{
		switch ($packet->getPacketType()) {
			case Mqtt\Packet::TYPE_PUBLISH:
				if (!($packet instanceof Mqtt\Packet\PublishRequestPacket)) {
					throw new Zigbee2MqttExceptions\Runtime(
						sprintf('Expected %s but got %s', Mqtt\Packet\PublishRequestPacket::class, $packet::class),
					);
				}

				$message = new Mqtt\DefaultMessage(
					$packet->getTopic(),
					$packet->getPayload(),
					$packet->getQosLevel(),
					$packet->isRetained(),
					$packet->isDuplicate(),
				);

				$this->startFlow($this->flowFactory->buildIncomingPublishFlow($message, $packet->getIdentifier()));

				break;
			case Mqtt\Packet::TYPE_CONNACK:
			case Mqtt\Packet::TYPE_PINGRESP:
			case Mqtt\Packet::TYPE_SUBACK:
			case Mqtt\Packet::TYPE_UNSUBACK:
			case Mqtt\Packet::TYPE_PUBREL:
			case Mqtt\Packet::TYPE_PUBACK:
			case Mqtt\Packet::TYPE_PUBREC:
			case Mqtt\Packet::TYPE_PUBCOMP:
				$flowFound = false;

				foreach ($this->receivingFlows as $index => $flow) {
					if ($flow->accept($packet)) {
						$flowFound = true;

						unset($this->receivingFlows[$index]);
						$this->continueFlow($flow, $packet);

						break;
					}
				}

				if (!$flowFound) {
					$this->handleWarning(
						new Zigbee2MqttExceptions\Logic(
							sprintf('Received unexpected packet of type %d', $packet->getPacketType()),
						),
					);
				}

				break;
			default:
				$this->handleWarning(
					new Zigbee2MqttExceptions\Logic(
						sprintf('Cannot handle packet of type %d', $packet->getPacketType()),
					),
				);
		}
	}

	/**
	 * Handles outgoing packets
	 */
	private function handleSend(): void
	{
		$flow = null;

		if ($this->writtenFlow !== null) {
			$flow = $this->writtenFlow;
			$this->writtenFlow = null;
		}

		if (count($this->sendingFlows) > 0 && $this->stream !== null) {
			$this->writtenFlow = array_shift($this->sendingFlows);

			if ($this->writtenFlow !== null) {
				$this->stream->write($this->writtenFlow->getPacket());
			}
		}

		if ($flow !== null) {
			if ($flow->isFinished()) {
				$this->eventLoop->futureTick(function () use ($flow): void {
					$this->finishFlow($flow);
				});

			} else {
				$this->receivingFlows[] = $flow;
			}
		}
	}

	/**
	 * Handles closing of the stream
	 */
	private function handleClose(): void
	{
		foreach ($this->timer as $timer) {
			$this->eventLoop->cancelTimer($timer);
		}

		$connection = $this->connection;

		$this->isConnecting = false;
		$this->isDisconnecting = false;
		$this->isConnected = false;
		$this->connection = null;
		$this->stream = null;

		if ($this->onCloseCallback !== null) {
			call_user_func($this->onCloseCallback, $connection);

			$this->onCloseCallback = null;
		}

		if ($connection !== null) {
			// Network connection closed
			$this->logger->info(
				'Connection to MQTT broker has been closed',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'mqtt-api',
					'credentials' => [
						'username' => $connection->getUsername(),
						'client_id' => $connection->getClientID(),
					],
				],
			);

			Utils\Arrays::invoke($this->onClose);
		}
	}

	/**
	 * Handles warnings of the stream
	 */
	private function handleWarning(Throwable $error): void
	{
		// Broker warning occur
		$this->logger->warning(
			sprintf('There was an error %s', $error->getMessage()),
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'mqtt-api',
				'error' => [
					'message' => $error->getMessage(),
					'code' => $error->getCode(),
				],
				'credentials' => [
					'client_id' => $this->clientId,
				],
			],
		);

		Utils\Arrays::invoke($this->onWarning, $error);
	}

	/**
	 * Handles errors of the stream
	 */
	private function handleError(Throwable $error): void
	{
		// Broker error occur
		$this->logger->error(
			sprintf('There was an error %s', $error->getMessage()),
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'mqtt-api',
				'error' => [
					'message' => $error->getMessage(),
					'code' => $error->getCode(),
				],
				'credentials' => [
					'client_id' => $this->clientId,
				],
			],
		);

		Utils\Arrays::invoke($this->onError, $error);
	}

	/**
	 * Starts the given flow
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	private function startFlow(Mqtt\Flow $flow, bool $isSilent = false): Promise\PromiseInterface
	{
		try {
			$packet = $flow->start();

		} catch (Throwable $ex) {
			$this->handleError($ex);

			return Promise\reject($ex);
		}

		$deferred = new Promise\Deferred();
		$internalFlow = new Clients\Flow($flow, $deferred, $packet, $isSilent);

		if ($packet !== null) {
			if ($this->writtenFlow !== null) {
				$this->sendingFlows[] = $internalFlow;

			} elseif ($this->stream !== null) {
				$this->stream->write($packet);
				$this->writtenFlow = $internalFlow;
				$this->handleSend();
			}
		} else {
			$this->eventLoop->futureTick(function () use ($internalFlow): void {
				$this->finishFlow($internalFlow);
			});
		}

		return $deferred->promise();
	}

	/**
	 * Continues the given flow
	 */
	private function continueFlow(Clients\Flow $flow, Mqtt\Packet $packet): void
	{
		try {
			$response = $flow->next($packet);

		} catch (Throwable $ex) {
			$this->handleError($ex);

			return;
		}

		if ($response !== null) {
			if ($this->writtenFlow !== null) {
				$this->sendingFlows[] = $flow;

			} elseif ($this->stream !== null) {
				$this->stream->write($response);
				$this->writtenFlow = $flow;
				$this->handleSend();
			}
		} elseif ($flow->isFinished()) {
			$this->eventLoop->futureTick(function () use ($flow): void {
				$this->finishFlow($flow);
			});
		}
	}

	/**
	 * Finishes the given flow
	 */
	private function finishFlow(Clients\Flow $flow): void
	{
		if ($flow->isSuccess()) {
			if (!$flow->isSilent()) {
				switch ($flow->getCode()) {
					case 'pong':
						break;
					case 'connect':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Connection);

						// Broker connected
						$this->logger->info(
							sprintf('Connected to MQTT broker with client id %s', $result->getClientID()),
							[
								'source' => Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'mqtt-api',
								'credentials' => [
									'username' => $result->getUsername(),
									'client_id' => $result->getClientID(),
								],
							],
						);

						Utils\Arrays::invoke($this->onConnect);

						break;
					case 'disconnect':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Connection);

						// Broker disconnected
						$this->logger->info(
							sprintf('Disconnected from MQTT broker with client id %s', $result->getClientID()),
							[
								'source' => Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'mqtt-api',
								'credentials' => [
									'username' => $result->getUsername(),
									'client_id' => $result->getClientID(),
								],
							],
						);

						Utils\Arrays::invoke($this->onDisconnect);

						break;
					case 'message':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Message);

						// Broker send message
						$this->logger->debug(
							sprintf(
								'Received message in topic: %s with payload %s',
								$result->getTopic(),
								$result->getPayload(),
							),
							[
								'source' => Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'mqtt-api',
								'message' => [
									'topic' => $result->getTopic(),
									'payload' => $result->getPayload(),
									'isRetained' => $result->isRetained(),
									'qos' => $result->getQosLevel(),
								],
								'credentials' => [
									'client_id' => $this->clientId,
								],
							],
						);

						Utils\Arrays::invoke($this->onMessage, $result);

						break;
					case 'publish':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Message);

						Utils\Arrays::invoke($this->onPublish, $result);

						break;
					case 'subscribe':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Subscription);

						Utils\Arrays::invoke($this->onSubscribe, $result);

						break;
					case 'unsubscribe':
						/** @var array<Mqtt\Subscription> $result */
						$result = $flow->getResult();

						Utils\Arrays::invoke($this->onUnsubscribe, $result);

						break;
				}
			}

			$flow->getDeferred()->resolve($flow->getResult());

		} else {
			$ex = new Zigbee2MqttExceptions\Runtime($flow->getErrorMessage());

			$flow->getDeferred()->reject($ex);

			$this->handleWarning($ex);
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function getConnector(): Socket\ConnectorInterface
	{
		return new Socket\Connector($this->eventLoop);
	}

}
