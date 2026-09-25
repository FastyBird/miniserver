<?php declare(strict_types = 1);

/**
 * Local.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use DateTimeInterface;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Documents;
use FastyBird\Connector\Shelly\Exceptions as ShellyExceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use RuntimeException;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function assert;
use function count;
use function in_array;
use function preg_match;
use function React\Async\async;

/**
 * Local devices client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Local implements Client
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 2.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const RECONNECT_COOL_DOWN_TIME = 300.0;

	private const DEVICE_RECONNECT_COOL_DOWN_TIME = 300.0;

	private const COMPONENT_KEY = '/^(?P<component>[a-zA-Z]+)(:(?P<channel>[0-9_]+))?$/';

	private const CMD_STATE = 'state';

	/** @var array<string, Documents\Devices\Device>  */
	private array $devices = [];

	/** @var array<string, API\Gen2WsApi> */
	private array $gen2DevicesWsClients = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	private array $processedDevicesCommands = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Queue\Queue $queue,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Device $deviceHelper,
		private readonly Shelly\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly Clock\Clock $clock,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedDevicesCommands = [];

		$this->handlerTimer = null;

		try {
			$gen1CoapClient = $this->connectionManager->getGen1CoapApiConnection();

			$gen1CoapClient->connect();

			$gen1CoapClient->onMessage[] = function (API\Messages\Message $message): void {
				if ($message instanceof API\Messages\Response\Gen1\ReportDeviceState) {
					$this->processGen1DeviceReportedStatus($message);
				}
			};

			$gen1CoapClient->onError[] = function (Throwable $ex): void {
				$this->logger->error(
					'An error occur in CoAP connection',
					[
						'source' => Sources\Connector::SHELLY->value,
						'type' => 'local-client',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				if (!$ex instanceof ShellyExceptions\CoapError) {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							Sources\Connector::SHELLY,
							'CoAP client triggered an error',
						),
					);
				}
			};
		} catch (Throwable $ex) {
			$this->logger->error(
				'CoAP client could not be started',
				[
					'source' => Sources\Connector::SHELLY->value,
					'type' => 'local-client',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$this->dispatcher?->dispatch(
				new DevicesEvents\TerminateConnector(
					Sources\Connector::SHELLY,
					'CoAP client could not be started',
				),
			);
		}

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Device::class,
		);

		foreach ($devices as $device) {
			$this->devices[$device->getId()->toString()] = $device;

			$findDevicePropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
			$findDevicePropertyQuery->forDevice($device);
			$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::GENERATION);

			$generationProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesDocuments\Devices\Properties\Variable::class,
			);

			if (
				$generationProperty !== null
				&& $generationProperty->getValue() === Types\DeviceGeneration::GENERATION_2->value
			) {
				try {
					$client = $this->createGen2DeviceWsClient($device);

					$client->connect()
						->then(function () use ($device): void {
							$this->logger->debug(
								'Connection with device through websocket was created',
								[
									'source' => Sources\Connector::SHELLY->value,
									'type' => 'local-client',
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
								],
							);
						})
						->catch(function (Throwable $ex) use ($device): void {
							$this->queue->append(
								$this->messageBuilder->create(
									Queue\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector(),
										'identifier' => $device->getIdentifier(),
										'state' => DevicesTypes\ConnectionState::DISCONNECTED,
									],
								),
							);

							unset($this->gen2DevicesWsClients[$device->getId()->toString()]);

							$this->logger->error(
								'Connection with device through websocket could not be created',
								[
									'source' => Sources\Connector::SHELLY->value,
									'type' => 'local-client',
									'exception' => Logging\Logger::buildException($ex),
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
								],
							);
						});
				} catch (Throwable $ex) {
					$this->logger->error(
						'Device websocket connection could not be created',
						[
							'source' => Sources\Connector::SHELLY->value,
							'type' => 'local-client',
							'exception' => Logging\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);

					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							Sources\Connector::SHELLY,
							'Websockets api client could not be started',
						),
					);
				}
			}
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
		);
	}

	public function disconnect(): void
	{
		$this->connectionManager->getGen1CoapApiConnection()->disconnect();

		foreach ($this->gen2DevicesWsClients as $client) {
			$client->disconnect();
		}

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleCommunication(): void
	{
		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->readDeviceState($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function readDeviceState(Documents\Devices\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->clock->getNow()->getTimestamp() - $cmdResult->getTimestamp()
						< $this->deviceHelper->getStateReadingDelay($device)
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

		$deviceState = $this->deviceConnectionManager->getState($device);

		if ($deviceState === DevicesTypes\ConnectionState::ALERT) {
			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		if (
			$deviceState === DevicesTypes\ConnectionState::LOST
			|| $deviceState === DevicesTypes\ConnectionState::DISCONNECTED
		) {
			$deviceStateTime = $this->deviceConnectionManager->getStateTime($device);
			assert($deviceStateTime instanceof DateTimeInterface);

			if ($this->clock->getNow()->getTimestamp() - $deviceStateTime->getTimestamp() < self::DEVICE_RECONNECT_COOL_DOWN_TIME) {
				return false;
			}
		}

		if ($this->deviceHelper->getGeneration($device) === Types\DeviceGeneration::GENERATION_2) {
			$client = $this->getGen2DeviceWsClient($device);

			if ($client === null) {
				try {
					$client = $this->createGen2DeviceWsClient($device);
				} catch (Throwable $ex) {
					$this->logger->error(
						'Device websocket connection could not be created',
						[
							'source' => Sources\Connector::SHELLY->value,
							'type' => 'local-client',
							'exception' => Logging\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);

					return false;
				}
			}

			if (!$client->isConnected()) {
				if (!$client->isConnecting()) {
					if (
						$client->getLastConnectAttempt() === null
						|| (
							// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
							$this->clock->getNow()->getTimestamp() - $client->getLastConnectAttempt()->getTimestamp() >= self::RECONNECT_COOL_DOWN_TIME
						)
					) {
						$client->connect()
							->then(function () use ($device): void {
								$this->logger->debug(
									'Connection with device through websocket was created',
									[
										'source' => Sources\Connector::SHELLY->value,
										'type' => 'local-client',
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
										'device' => [
											'id' => $device->getId()->toString(),
										],
									],
								);
							})
							->catch(function (Throwable $ex) use ($device): void {
								$this->logger->error(
									'Device websocket connection could not be created',
									[
										'source' => Sources\Connector::SHELLY->value,
										'type' => 'local-client',
										'exception' => Logging\Logger::buildException($ex),
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
										'device' => [
											'id' => $device->getId()->toString(),
										],
									],
								);
							});

					} else {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::DISCONNECTED,
								],
							),
						);
					}
				}

				return false;
			}

			$client->readStates()
				->then(function (API\Messages\Response\Gen2\GetDeviceState $response) use ($device): void {
					$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

					$this->processGen2DeviceGetState($device, $response);
				})
				->catch(function (Throwable $ex) use ($device): void {
					$renderException = true;

					if ($ex instanceof ShellyExceptions\HttpApiCall) {
						$renderException = false;
					}

					$this->logger->error(
						'Could not read device state',
						[
							'source' => Sources\Connector::SHELLY->value,
							'type' => 'local-client',
							'exception' => Logging\Logger::buildException($ex, $renderException),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);
				});

		} elseif ($this->deviceHelper->getGeneration($device) === Types\DeviceGeneration::GENERATION_1) {
			$address = $this->deviceHelper->getLocalAddress($device);

			if ($address === null) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $device->getConnector(),
							'identifier' => $device->getIdentifier(),
							'state' => DevicesTypes\ConnectionState::ALERT,
						],
					),
				);

				return true;
			}

			$this->connectionManager->getGen1HttpApiConnection()->getDeviceState(
				$address,
				$this->deviceHelper->getUsername($device),
				$this->deviceHelper->getPassword($device),
			)
				->then(function (API\Messages\Response\Gen1\GetDeviceState $response) use ($device): void {
					$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->clock->getNow();

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::CONNECTED,
							],
						),
					);

					$this->processGen1DeviceGetState($device, $response);
				})
				->catch(function (Throwable $ex) use ($device): void {
					$renderException = true;

					if ($ex instanceof ShellyExceptions\HttpApiError) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => DevicesTypes\ConnectionState::ALERT,
								],
							),
						);
					} elseif ($ex instanceof ShellyExceptions\HttpApiCall) {
						if (
							$ex->getResponse() !== null
							&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
							&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
						) {
							$this->queue->append(
								$this->messageBuilder->create(
									Queue\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector(),
										'identifier' => $device->getIdentifier(),
										'state' => DevicesTypes\ConnectionState::ALERT,
									],
								),
							);

						} elseif (
							$ex->getResponse() !== null
							&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
							&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
						) {
							$this->queue->append(
								$this->messageBuilder->create(
									Queue\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector(),
										'identifier' => $device->getIdentifier(),
										'state' => DevicesTypes\ConnectionState::LOST,
									],
								),
							);

						} else {
							$this->queue->append(
								$this->messageBuilder->create(
									Queue\Messages\StoreDeviceConnectionState::class,
									[
										'connector' => $device->getConnector(),
										'identifier' => $device->getIdentifier(),
										'state' => DevicesTypes\ConnectionState::DISCONNECTED,
									],
								),
							);
						}

						$renderException = false;
					}

					$this->logger->error(
						'Could not read device state',
						[
							'source' => Sources\Connector::SHELLY->value,
							'type' => 'local-client',
							'exception' => Logging\Logger::buildException($ex, $renderException),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);
				});
		}

		return false;
	}

	/**
	 * @throws ShellyExceptions\InvalidState
	 */
	private function createGen2DeviceWsClient(Documents\Devices\Device $device): API\Gen2WsApi
	{
		if (array_key_exists($device->getId()->toString(), $this->gen2DevicesWsClients)) {
			throw new ShellyExceptions\InvalidState('Gen 2 device WS client is already created');
		}

		unset($this->processedDevicesCommands[$device->getId()->toString()]);

		try {
			$client = $this->connectionManager->getGen2WsApiConnection($device);
		} catch (Throwable $ex) {
			throw new ShellyExceptions\InvalidState('Gen 2 device WS client is already created', $ex->getCode(), $ex);
		}

		$client->onMessage[] = function (API\Messages\Message $message) use ($device): void {
			try {
				if ($message instanceof API\Messages\Response\Gen2\GetDeviceState) {
					$this->processGen2DeviceGetState($device, $message);
				} elseif ($message instanceof API\Messages\Response\Gen2\DeviceEvent) {
					$this->processGen2DeviceEvent($device, $message);
				}
			} catch (Throwable $ex) {
				$this->logger->error(
					'Received message could not be handled',
					[
						'source' => Sources\Connector::SHELLY->value,
						'type' => 'local-client',
						'exception' => Logging\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
					],
				);
			}
		};

		$client->onError[] = function (Throwable $ex) use ($device): void {
			$this->logger->warning(
				'Connection with Gen 2 device failed',
				[
					'source' => Sources\Connector::SHELLY->value,
					'type' => 'local-client',
					'exception' => Logging\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
				],
			);

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::DISCONNECTED,
					],
				),
			);
		};

		$client->onConnected[] = function () use ($client, $device): void {
			$this->logger->debug(
				'Connected to Gen 2 device',
				[
					'source' => Sources\Connector::SHELLY->value,
					'type' => 'local-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
				],
			);

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::CONNECTED,
					],
				),
			);

			$client->readStates()
				->then(function (API\Messages\Response\Gen2\GetDeviceState $state) use ($device): void {
					$this->processGen2DeviceGetState($device, $state);
				})
				->catch(function (Throwable $ex) use ($device): void {
					$renderException = true;

					if ($ex instanceof ShellyExceptions\HttpApiCall) {
						$renderException = false;
					}

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::DISCONNECTED,
							],
						),
					);

					$this->logger->error(
						'An error occurred on Gen 2 device state reading',
						[
							'source' => Sources\Connector::SHELLY->value,
							'type' => 'local-client',
							'exception' => Logging\Logger::buildException($ex, $renderException),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
						],
					);
				});
		};

		$client->onDisconnected[] = function () use ($device): void {
			$this->logger->debug(
				'Disconnected from Gen 2 device',
				[
					'source' => Sources\Connector::SHELLY->value,
					'type' => 'local-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
				],
			);

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::DISCONNECTED,
					],
				),
			);
		};

		$this->gen2DevicesWsClients[$device->getId()->toString()] = $client;

		return $this->gen2DevicesWsClients[$device->getId()->toString()];
	}

	private function getGen2DeviceWsClient(Documents\Devices\Device $device): API\Gen2WsApi|null
	{
		return array_key_exists(
			$device->getId()->toString(),
			$this->gen2DevicesWsClients,
		)
			? $this->gen2DevicesWsClients[$device->getId()->toString()]
			: null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processGen1DeviceGetState(
		Documents\Devices\Device $device,
		API\Messages\Response\Gen1\GetDeviceState $state,
	): void
	{
		$states = [];

		if ($state->getRelays() !== []) {
			foreach ($state->getRelays() as $index => $relay) {
				$states[] = [
					'identifier' => '_' . Types\BlockDescription::RELAY->value . '_' . $index,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::OUTPUT->value,
							'value' => $relay->getState(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::OVERPOWER->value,
							'value' => $relay->hasOverpower(),
						],
					],
				];

				$states[] = [
					'identifier' => '_' . Types\BlockDescription::DEVICE->value,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::OVERTEMPERATURE->value,
							'value' => $relay->hasOvertemperature(),
						],
					],
				];
			}
		}

		if ($state->getRollers() !== []) {
			foreach ($state->getRollers() as $index => $roller) {
				$states[] = [
					'identifier' => '_' . Types\BlockDescription::ROLLER->value . '_' . $index,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::ROLLER->value,
							'value' => $roller->getState(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::ROLLER_POSITION->value,
							'value' => $roller->getCurrentPosition(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::ROLLER_STOP_REASON->value,
							'value' => $roller->getStopReason(),
						],
					],
				];

				$states[] = [
					'identifier' => '_' . Types\BlockDescription::DEVICE->value,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::OVERTEMPERATURE->value,
							'value' => $roller->hasOvertemperature(),
						],
					],
				];
			}
		}

		if ($state->getLights() !== []) {
			foreach ($state->getLights() as $index => $light) {
				$states[] = [
					'identifier' => '_' . Types\BlockDescription::LIGHT->value . '_' . $index,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::RED->value,
							'value' => $light->getGreen(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::GREEN->value,
							'value' => $light->getGreen(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::BLUE->value,
							'value' => $light->getBlue(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::GAIN->value,
							'value' => $light->getGain(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::WHITE->value,
							'value' => $light->getWhite(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::WHITE_LEVEL->value,
							'value' => $light->getWhite(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::EFFECT->value,
							'value' => $light->getEffect(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::BRIGHTNESS->value,
							'value' => $light->getBrightness(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::OUTPUT->value,
							'value' => $light->getState(),
						],
					],
				];
			}
		}

		if ($state->getEmeters() !== []) {
			foreach ($state->getEmeters() as $index => $emeter) {
				$states[] = [
					'identifier' => '_' . Types\BlockDescription::EMETER->value . '_' . $index,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::ACTIVE_POWER->value,
							'value' => $emeter->getActivePower(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::REACTIVE_POWER->value,
							'value' => $emeter->getReactivePower(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::POWER_FACTOR->value,
							'value' => $emeter->getPowerFactor(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::CURRENT->value,
							'value' => $emeter->getCurrent(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::VOLTAGE->value,
							'value' => $emeter->getVoltage(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::ENERGY->value,
							'value' => $emeter->getTotal(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::ENERGY_RETURNED->value,
							'value' => $emeter->getTotalReturned(),
						],
					],
				];
			}
		}

		/**
		 * INPUT in /status API endpoint are separated, but in CoIoT are in relay/roller group
		 */
		if ($state->getInputs() !== []) {
			foreach ($state->getInputs() as $index => $input) {
				$states[] = [
					'identifier' => '_' . Types\BlockDescription::RELAY->value . '_' . $index,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::INPUT->value,
							'value' => $input->getInput(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::INPUT_EVENT->value,
							'value' => $input->getEvent(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::INPUT_EVENT_COUNT->value,
							'value' => $input->getEventCnt(),
						],
					],
				];
			}
		}

		/**
		 * METERS in /status API endpoint are separated, but in CoIoT are in relay/roller group
		 */
		if ($state->getMeters() !== []) {
			foreach ($state->getMeters() as $index => $meter) {
				$states[] = [
					'identifier' => '_' . Types\BlockDescription::RELAY->value . '_' . $index,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::ACTIVE_POWER->value,
							'value' => $meter->getPower(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::ENERGY->value,
							'value' => $meter->getTotal(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::OVERPOWER->value,
							'value' => $meter->getOverpower(),
						],
					],
				];

				$states[] = [
					'identifier' => '_' . Types\BlockDescription::ROLLER->value . '_' . $index,
					'sensors' => [
						[
							'identifier' => '_' . Types\SensorDescription::ROLLER_POWER->value,
							'value' => $meter->getPower(),
						],
						[
							'identifier' => '_' . Types\SensorDescription::ROLLER_ENERGY->value,
							'value' => $meter->getTotal(),
						],
					],
				];
			}
		}

		if (count($states) > 0) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'ip_address' => $state->getWifi()?->getIp() ?? $this->deviceHelper->getIpAddress($device),
						'states' => $states,
					],
				),
			);
		}
	}

	/**
	 * @throws ShellyExceptions\Runtime
	 */
	public function processGen1DeviceReportedStatus(
		API\Messages\Response\Gen1\ReportDeviceState $state,
	): void
	{
		$states = [];

		foreach ($state->getStates() as $blockState) {
			$states[] = [
				'identifier' => $blockState->getSensor() . '_',
				'value' => $blockState->getValue(),
			];
		}

		$this->queue->append(
			$this->messageBuilder->create(
				Queue\Messages\StoreDeviceConnectionState::class,
				[
					'connector' => $this->connector->getId(),
					'identifier' => $state->getIdentifier(),
					'state' => DevicesTypes\ConnectionState::CONNECTED,
				],
			),
		);

		if (count($states) > 0) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceState::class,
					[
						'connector' => $this->connector->getId(),
						'identifier' => $state->getIdentifier(),
						'ip_address' => $state->getIpAddress(),
						'states' => $states,
					],
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\Runtime
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processGen2DeviceGetState(
		Documents\Devices\Device $device,
		API\Messages\Response\Gen2\GetDeviceState $state,
	): void
	{
		$states = [];

		foreach ($state->getComponents() as $component) {
			foreach ($component->toState() as $key => $value) {
				$states[] = [
					'identifier' => (
						$component->getType()->value
						. '_'
						. $component->getId()
						. '_'
						. $key
					),
					'value' => $value,
				];
			}
		}

		if (count($states) > 0) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'ip_address' => $state->getEthernet()?->getIp()
							?? ($state->getWifi()?->getStaIp() ?? $this->deviceHelper->getIpAddress($device)),
						'states' => $states,
					],
				),
			);
		}
	}

	/**
	 * @throws ShellyExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processGen2DeviceEvent(
		Documents\Devices\Device $device,
		API\Messages\Response\Gen2\DeviceEvent $notification,
	): void
	{
		foreach ($notification->getEvents() as $event) {
			if (
				preg_match(self::COMPONENT_KEY, $event->getComponent(), $componentMatches) === 1
				&& Types\ComponentType::tryFrom($componentMatches['component']) !== null
				&& array_key_exists('channel', $componentMatches)
			) {
				$component = Types\ComponentType::from($componentMatches['component']);

				if (
					$component === Types\ComponentType::SCRIPT
					&& $event->getEvent() === Types\ComponentEvent::RESULT->value
					&& $event->getData() !== null
				) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'ip_address' => null,
								'states' => [
									[
										'identifier' => (
											$component->value
											. '_'
											. $event->getId()
											. '_'
											. Types\ComponentAttributeType::RESULT->value
										),
										'value' => $event->getData(),
									],
								],
							],
						),
					);
				}
			}
		}
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ShellyExceptions\InvalidArgument
	 * @throws ShellyExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			async(function (): void {
				$this->handleCommunication();
			}),
		);
	}

}
