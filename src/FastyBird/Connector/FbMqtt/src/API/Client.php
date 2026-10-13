<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\API;

use BinSoul\Net\Mqtt;
use Closure;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Clients\Flow;
use FastyBird\Connector\FbMqtt\Exceptions as FbMqttExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use InvalidArgumentException;
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
 * The following events are emitted. Every one except open has a matching on<Event>() method that
 * registers a listener, and the listeners are called in the order they were registered:
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
 */
final class Client
{

	/** @var array<Closure(Mqtt\Connection $connection): void> */
	private array $onConnectListeners = [];

	/** @var array<Closure(): void> */
	private array $onDisconnectListeners = [];

	/** @var array<Closure(): void> */
	private array $onCloseListeners = [];

	/** @var array<Closure(Mqtt\Message $message): void> */
	private array $onMessageListeners = [];

	/** @var array<Closure(Mqtt\Message $message): void> */
	private array $onPublishListeners = [];

	/** @var array<Closure(Mqtt\Subscription $subscription): void> */
	private array $onSubscribeListeners = [];

	/** @var array<Closure(array<Mqtt\Subscription> $subscriptions): void> */
	private array $onUnsubscribeListeners = [];

	/** @var array<Closure(Throwable $error): void> */
	private array $onWarningListeners = [];

	/** @var array<Closure(Throwable $error): void> */
	private array $onErrorListeners = [];

	private bool $isConnected = false;

	private bool $isConnecting = false;

	private bool $isDisconnecting = false;

	/** @var Closure(Mqtt\Connection $connection): void|null */
	private Closure|null $disconnectCallback = null;

	/** @var array<EventLoop\TimerInterface> */
	private array $timer = [];

	/** @var array<Flow> */
	private array $receivingFlows = [];

	/** @var array<Flow> */
	private array $sendingFlows = [];

	private Flow|null $writtenFlow = null;

	private Stream\DuplexStreamInterface|null $stream = null;

	private Mqtt\StreamParser $parser;

	private Mqtt\ClientIdentifierGenerator $identifierGenerator;

	private Mqtt\Connection|null $connection = null;

	private Mqtt\FlowFactory $flowFactory;

	public function __construct(
		private readonly string $clientId,
		private readonly string $address,
		private readonly int $port,
		private readonly FbMqtt\Logger $logger,
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

	/**
	 * Registers a listener. Called when the client connected to the broker.
	 *
	 * @param Closure(Mqtt\Connection $connection): void $listener
	 */
	public function onConnect(Closure $listener): void
	{
		$this->onConnectListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when the client disconnected from the broker.
	 *
	 * @param Closure(): void $listener
	 */
	public function onDisconnect(Closure $listener): void
	{
		$this->onDisconnectListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when the network connection to the server was closed.
	 *
	 * @param Closure(): void $listener
	 */
	public function onClose(Closure $listener): void
	{
		$this->onCloseListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when a message was received.
	 *
	 * @param Closure(Mqtt\Message $message): void $listener
	 */
	public function onMessage(Closure $listener): void
	{
		$this->onMessageListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when a message was published.
	 *
	 * @param Closure(Mqtt\Message $message): void $listener
	 */
	public function onPublish(Closure $listener): void
	{
		$this->onPublishListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when the client subscribed to a topic filter.
	 *
	 * @param Closure(Mqtt\Subscription $subscription): void $listener
	 */
	public function onSubscribe(Closure $listener): void
	{
		$this->onSubscribeListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when the client unsubscribed from a topic filter.
	 *
	 * @param Closure(array<Mqtt\Subscription> $subscriptions): void $listener
	 */
	public function onUnsubscribe(Closure $listener): void
	{
		$this->onUnsubscribeListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when an event of severity "warning" occurred.
	 *
	 * @param Closure(Throwable $error): void $listener
	 */
	public function onWarning(Closure $listener): void
	{
		$this->onWarningListeners[] = $listener;
	}

	/**
	 * Registers a listener. Called when an event of severity "error" occurred.
	 *
	 * @param Closure(Throwable $error): void $listener
	 */
	public function onError(Closure $listener): void
	{
		$this->onErrorListeners[] = $listener;
	}

	/**
	 * Connects to a broker
	 *
	 * @return Promise\PromiseInterface<mixed>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 */
	public function connect(int $timeout = 5): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->isConnected || $this->isConnecting) {
			return Promise\reject(new FbMqttExceptions\Logic('The client is already connected'));
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
								'source' => Sources\Connector::FB_MQTT->value,
								'type' => 'api-client',
								'credentials' => [
									'username' => $connection->getUsername(),
									'client_id' => $connection->getClientID(),
								],
							],
						);

						Utils\Arrays::invoke($this->onConnectListeners, $connection);

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
			return Promise\reject(new FbMqttExceptions\Logic('The client is not connected'));
		}

		$this->isDisconnecting = true;

		$deferred = new Promise\Deferred();

		$isResolved = false;

		/** @var mixed $flowResult */
		$flowResult = null;

		$this->disconnectCallback = function ($connection) use ($deferred, &$isResolved, &$flowResult): void {
			if (!$isResolved) {
				$isResolved = true;

				if ($connection) {
					// Broker disconnected
					$this->logger->info(
						sprintf('Disconnected from MQTT broker with client id %s', $connection->getClientID()),
						[
							'source' => Sources\Connector::FB_MQTT->value,
							'type' => 'api-client',
							'credentials' => [
								'username' => $connection->getUsername(),
								'client_id' => $connection->getClientID(),
							],
						],
					);

					Utils\Arrays::invoke($this->onDisconnectListeners);
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
			return Promise\reject(new FbMqttExceptions\Logic('The client is not connected'));
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
			return Promise\reject(new FbMqttExceptions\Logic('The client is not connected'));
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
		int $qos = FbMqtt\Constants::MQTT_API_QOS_0,
		bool $retain = false,
	): Promise\PromiseInterface
	{
		$message = new Mqtt\DefaultMessage($topic, ($payload ?? ''), $qos, $retain);

		if (!$this->isConnected) {
			return Promise\reject(new FbMqttExceptions\Logic('The client is not connected'));
		}

		return $this->startFlow($this->flowFactory->buildOutgoingPublishFlow($message));
	}

	private function onOpen(Mqtt\Connection $connection): void
	{
		// Network connection established
		$this->logger->info(
			'Established connection to MQTT broker',
			[
				'source' => Sources\Connector::FB_MQTT->value,
				'type' => 'api-client',
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

		/** @var Promise\PromiseInterface<Socket\ConnectionInterface>|null $future */
		$future = null;

		$timer = $this->eventLoop->addTimer(
			$timeout,
			static function () use ($deferred, $timeout, &$future): void {
				$exception = new FbMqttExceptions\Runtime(sprintf('Connection timed out after %d seconds', $timeout));
				$deferred->reject($exception);

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
				$exception = new FbMqttExceptions\Runtime(sprintf('No response after %d seconds', $timeout));
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
	 * @throws FbMqttExceptions\Runtime
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
	 * @throws FbMqttExceptions\Runtime
	 */
	private function handlePacket(Mqtt\Packet $packet): void
	{
		switch ($packet->getPacketType()) {
			case Mqtt\Packet::TYPE_PUBLISH:
				if (!($packet instanceof Mqtt\Packet\PublishRequestPacket)) {
					throw new FbMqttExceptions\Runtime(
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
						new FbMqttExceptions\Logic(
							sprintf('Received unexpected packet of type %d', $packet->getPacketType()),
						),
					);
				}

				break;
			default:
				$this->handleWarning(
					new FbMqttExceptions\Logic(sprintf('Cannot handle packet of type %d', $packet->getPacketType())),
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

		if ($this->disconnectCallback !== null) {
			call_user_func($this->disconnectCallback, $connection);

			$this->disconnectCallback = null;
		}

		if ($connection !== null) {
			// Network connection closed
			$this->logger->info(
				'Connection to MQTT broker has been closed',
				[
					'source' => Sources\Connector::FB_MQTT->value,
					'type' => 'api-client',
					'credentials' => [
						'username' => $connection->getUsername(),
						'client_id' => $connection->getClientID(),
					],
				],
			);

			Utils\Arrays::invoke($this->onCloseListeners);
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
				'source' => Sources\Connector::FB_MQTT->value,
				'type' => 'api-client',
				'error' => [
					'message' => $error->getMessage(),
					'code' => $error->getCode(),
				],
				'credentials' => [
					'client_id' => $this->clientId,
				],
			],
		);

		Utils\Arrays::invoke($this->onWarningListeners, $error);
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
				'source' => Sources\Connector::FB_MQTT->value,
				'type' => 'api-client',
				'error' => [
					'message' => $error->getMessage(),
					'code' => $error->getCode(),
				],
				'credentials' => [
					'client_id' => $this->clientId,
				],
			],
		);

		Utils\Arrays::invoke($this->onErrorListeners, $error);
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
		$internalFlow = new Flow($flow, $deferred, $packet, $isSilent);

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
	private function continueFlow(Flow $flow, Mqtt\Packet $packet): void
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
	private function finishFlow(Flow $flow): void
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
								'source' => Sources\Connector::FB_MQTT->value,
								'type' => 'api-client',
								'credentials' => [
									'username' => $result->getUsername(),
									'client_id' => $result->getClientID(),
								],
							],
						);

						Utils\Arrays::invoke($this->onConnectListeners, $result);

						break;
					case 'disconnect':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Connection);

						// Broker disconnected
						$this->logger->info(
							sprintf('Disconnected from MQTT broker with client id %s', $result->getClientID()),
							[
								'source' => Sources\Connector::FB_MQTT->value,
								'type' => 'api-client',
								'credentials' => [
									'username' => $result->getUsername(),
									'client_id' => $result->getClientID(),
								],
							],
						);

						Utils\Arrays::invoke($this->onDisconnectListeners);

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
								'source' => Sources\Connector::FB_MQTT->value,
								'type' => 'api-client',
								'message' => [
									'topic' => $result->getTopic(),
									'payload' => $result->getPayload(),
									'is_retained' => $result->isRetained(),
									'qos' => $result->getQosLevel(),
								],
								'credentials' => [
									'client_id' => $this->clientId,
								],
							],
						);

						Utils\Arrays::invoke($this->onMessageListeners, $result);

						break;
					case 'publish':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Message);

						Utils\Arrays::invoke($this->onPublishListeners, $result);

						break;
					case 'subscribe':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Subscription);

						Utils\Arrays::invoke($this->onSubscribeListeners, $result);

						break;
					case 'unsubscribe':
						/** @var array<Mqtt\Subscription> $result */
						$result = $flow->getResult();

						Utils\Arrays::invoke($this->onUnsubscribeListeners, $result);

						break;
				}
			}

			$flow->getDeferred()->resolve($flow->getResult());

		} else {
			$ex = new FbMqttExceptions\Runtime($flow->getErrorMessage());

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
