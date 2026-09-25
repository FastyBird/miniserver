<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Types as TuyaTypes;
use FastyBird\Connector\Tuya\ValueObjects;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Datagram;
use React\EventLoop;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function assert;
use function boolval;
use function count;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use function is_float;
use function is_numeric;
use function is_string;
use function md5;
use function openssl_decrypt;
use function React\Async\async;
use function React\Async\await;
use function strval;
use function substr;
use const OPENSSL_RAW_DATA;

/**
 * Devices discovery client
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery
{

	use Nette\SmartObject;

	private const UDP_BIND_IP = '0.0.0.0';

	private const UDP_PORT = [
		TuyaTypes\DeviceProtocolVersion::V31->value => 6_666,
		TuyaTypes\DeviceProtocolVersion::V32_PLUS->value => 6_667,
	];

	private const UDP_TIMEOUT = 5;

	private const GATEWAY_CATEGORIES = [
		'bywg',
		'zigbee',
		'wg2',
		'xnwg',
		'alexa_yywg',
		'gywg',
	];

	/** @var array<ValueObjects\LocalDevice> */
	private array $discoveredLocalDevicesInfo = [];

	private API\OpenApi|null $cloudApiConnection = null;

	/** @var array<EventLoop\TimerInterface> */
	private array $handlerTimer = [];

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\OpenApiFactory $openApiFactory,
		private readonly API\LocalApiFactory $localApiFactory,
		private readonly Services\DatagramFactory $datagramFactory,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Queue\Queue $queue,
		private readonly Tuya\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly ObjectMapper\Processing\Processor $objectMapper,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidState
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws Throwable
	 */
	public function discover(): void
	{
		$mode = $this->connectorHelper->getClientMode($this->connector);

		if ($mode === TuyaTypes\ClientMode::CLOUD) {
			$this->discoverCloudDevices();

		} elseif ($mode === TuyaTypes\ClientMode::LOCAL) {
			$this->discoverLocalDevices();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): void
	{
		foreach ($this->handlerTimer as $index => $timer) {
			$this->eventLoop->cancelTimer($timer);

			unset($this->handlerTimer[$index]);
		}

		$this->getCloudApiConnection()->disconnect();
	}

	private function discoverLocalDevices(): void
	{
		$promises = [];

		$knownProtocolsVersions = [
			TuyaTypes\DeviceProtocolVersion::V31,
			TuyaTypes\DeviceProtocolVersion::V32_PLUS,
		];

		// Process all known protocols
		foreach ($knownProtocolsVersions as $protocolVersion) {
			$this->logger->debug(
				'Starting local devices discovery',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'protocol' => $protocolVersion->value,
				],
			);

			$deferred = new Promise\Deferred();

			$server = $this->datagramFactory->create(self::UDP_BIND_IP, self::UDP_PORT[$protocolVersion->value]);

			$server
				->then(function (Datagram\Socket $client) use ($deferred, $protocolVersion): void {
					$client->on('message', function (string $packet): void {
						$decryptedPacket = openssl_decrypt(
							substr($packet, 20, -8),
							'AES-128-ECB',
							md5('yGAdlopoPVldABfn', true),
							OPENSSL_RAW_DATA,
						);

						if ($decryptedPacket === false) {
							$this->logger->error(
								'Received invalid packet. Received data could not be decrypted',
								[
									'source' => Sources\Connector::TUYA->value,
									'type' => 'discovery-client',
								],
							);

							return;
						}

						try {
							$decoded = Utils\Json::decode($decryptedPacket, forceArrays: true);

							if (!is_array($decoded)) {
								$this->logger->error(
									'Decoded discovered local message has invalid format',
									[
										'source' => Sources\Connector::TUYA->value,
										'type' => 'discovery-client',
									],
								);

								return;
							}

							$deviceInfo = Utils\ArrayHash::from($decoded);

							if (
								!$deviceInfo->offsetExists('gwId')
								|| !$deviceInfo->offsetExists('ip')
								|| !$deviceInfo->offsetExists('productKey')
								|| !$deviceInfo->offsetExists('encrypt')
								|| !$deviceInfo->offsetExists('version')
							) {
								$this->logger->error(
									'Decoded discovered local message has invalid format',
									[
										'source' => Sources\Connector::TUYA->value,
										'type' => 'discovery-client',
									],
								);

								return;
							}

							$this->discoveredLocalDevicesInfo[] = $this->objectMapper->process(
								[
									'id' => strval($deviceInfo->offsetGet('gwId')),
									'ip_address' => strval($deviceInfo->offsetGet('ip')),
									'encrypted' => boolval($deviceInfo->offsetGet('encrypt')),
									'version' => strval($deviceInfo->offsetGet('version')),
								],
								ValueObjects\LocalDevice::class,
							);

						} catch (Throwable $ex) {
							$this->logger->error(
								'Received data could not be transformed to message',
								[
									'source' => Sources\Connector::TUYA->value,
									'type' => 'discovery-client',
									'exception' => Logging\Logger::buildException($ex),
								],
							);

							return;
						}
					});

					// Searching timeout
					$this->handlerTimer[$protocolVersion->value] = $this->eventLoop->addTimer(
						self::UDP_TIMEOUT,
						async(function () use ($deferred, $protocolVersion): void {
							if (array_key_exists($protocolVersion->value, $this->handlerTimer)) {
								$this->eventLoop->cancelTimer($this->handlerTimer[$protocolVersion->value]);

								unset($this->handlerTimer[$protocolVersion->value]);
							}

							$deferred->resolve(true);
						}),
					);
				})
				->catch(function (Throwable $ex) use ($deferred, $protocolVersion): void {
					$this->logger->error(
						'Could not create local discovery server',
						[
							'source' => Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => Logging\Logger::buildException($ex),
							'protocol' => $protocolVersion->value,
						],
					);

					$deferred->reject($ex);
				});

			$promises[] = $deferred->promise();
		}

		Promise\all($promises)
			->then(async(function (): void {
				foreach ($this->discoveredLocalDevicesInfo as $localDeviceInfo) {
					try {
						await($this->handleFoundLocalDevice(
							$localDeviceInfo->getId(),
							$localDeviceInfo->getIpAddress(),
							$localDeviceInfo->isEncrypted(),
							$localDeviceInfo->getVersion(),
						));
					} catch (Throwable $ex) {
						$this->logger->error(
							'Discovered local device could not be assigned to system',
							[
								'source' => Sources\Connector::TUYA->value,
								'type' => 'discovery-client',
								'exception' => Logging\Logger::buildException($ex),
								'device' => [
									'identifier' => $localDeviceInfo->getId(),
									'ip_address' => $localDeviceInfo->getIpAddress(),
								],
							],
						);
					}
				}

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						Sources\Connector::TUYA,
						'Devices discovery finished',
					),
				);
			}))
			->catch(function (): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						Sources\Connector::TUYA,
						'Devices discovery failed',
					),
				);
			})
			->finally(function (): void {
				foreach ($this->handlerTimer as $index => $timer) {
					$this->eventLoop->cancelTimer($timer);

					unset($this->handlerTimer[$index]);
				}

				$this->getCloudApiConnection()->disconnect();
			});
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws Throwable
	 */
	private function discoverCloudDevices(): void
	{
		$this->logger->debug(
			'Starting cloud devices discovery',
			[
				'source' => Sources\Connector::TUYA->value,
				'type' => 'discovery-client',
			],
		);

		$this->getCloudApiConnection()
			->connect()
			->then(async(function (): void {
				try {
					$result = await(
						$this->getCloudApiConnection()
							->getDevices(
								[
									'source_id' => $this->connectorHelper->getUid($this->connector),
									'source_type' => 'tuyaUser',
								],
							),
					);

					$devices = $result->getResult()->getList();

				} catch (Throwable $ex) {
					$this->logger->error(
						'Could not load devices from Tuya cloud',
						[
							'source' => Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => Logging\Logger::buildException($ex),
						],
					);

					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							Sources\Connector::TUYA,
							'Devices discovery failed',
						),
					);

					return;
				}

				try {
					$result = await($this->getCloudApiConnection()
						->getDevicesFactoryInfos(
							array_map(
								static fn (API\Messages\Response\Device $userDevice): string => $userDevice->getId(),
								$devices,
							),
						));

				} catch (Throwable $ex) {
					$this->logger->error(
						'Could not load device factory infos from Tuya cloud',
						[
							'source' => Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => Logging\Logger::buildException($ex),
						],
					);

					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							Sources\Connector::TUYA,
							'Devices discovery failed',
						),
					);

					return;
				}

				foreach ($devices as $device) {
					try {
						await($this->handleFoundCloudDevice($device, $result->getResult()));
					} catch (Throwable $ex) {
						$this->logger->error(
							'Discovered cloud device could not be assigned to system',
							[
								'source' => Sources\Connector::TUYA->value,
								'type' => 'discovery-client',
								'exception' => Logging\Logger::buildException($ex),
								'device' => [
									'identifier' => $device->getId(),
									'ip_address' => $device->getIp(),
								],
							],
						);
					}
				}

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						Sources\Connector::TUYA,
						'Devices discovery finished',
					),
				);

				$this->getCloudApiConnection()->disconnect();
			}))
			->catch(function (Throwable $ex): void {
				$this->logger->error(
					'Could not connect to cloud api',
					[
						'source' => Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						Sources\Connector::TUYA,
						'Devices discovery failed',
					),
				);

				$this->getCloudApiConnection()->disconnect();
			});
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws TuyaExceptions\OpenApiCall
	 * @throws TuyaExceptions\OpenApiError
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleFoundLocalDevice(
		string $id,
		string $ipAddress,
		bool $encrypted,
		string $version,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->getCloudApiConnection()
			->connect()
			->then(async(function () use ($id, $ipAddress, $encrypted, $version, $deferred): void {
				try {
					$response = await($this->getCloudApiConnection()->getDevicesFactoryInfos([$id]));

					$devicesFactoryInfos = $response->getResult();

				} catch (Throwable $ex) {
					$deferred->reject(
						new TuyaExceptions\InvalidState(
							'Loading device factory infos from cloud failed',
							$ex->getCode(),
							$ex,
						),
					);

					return;
				}

				try {
					$response = await($this->getCloudApiConnection()->getDeviceDetail($id));

					$deviceInformation = $response->getResult();
				} catch (Throwable $ex) {
					$deferred->reject(
						new TuyaExceptions\InvalidState(
							'Could not load device basic information from Tuya cloud',
							$ex->getCode(),
							$ex,
						),
					);

					return;
				}

				$dataPoints = $this->loadLocalDeviceDataPoints(
					$deviceInformation->getId(),
					$deviceInformation->getLocalKey(),
					$ipAddress,
					TuyaTypes\DeviceProtocolVersion::from($version),
				);

				try {
					/** @var array<API\Messages\Response\DeviceFactoryInfos> $deviceFactoryInfosFiltered */
					$deviceFactoryInfosFiltered = array_values(array_filter(
						$devicesFactoryInfos,
						static fn (API\Messages\Response\DeviceFactoryInfos $item): bool => $id === $item->getId(),
					));

					$deviceFactoryInfos = count(
						$deviceFactoryInfosFiltered,
					) > 0 ? $deviceFactoryInfosFiltered[0] : null;

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreLocalDevice::class,
							[
								'connector' => $this->connector->getId(),
								'id' => $id,
								'ip_address' => $ipAddress,
								'local_key' => $deviceInformation->getLocalKey(),
								'encrypted' => $encrypted,
								'version' => $version,
								'gateway' => $deviceInformation->getGatewayId(),
								'node_id' => $deviceInformation->getNodeId(),
								'name' => $deviceInformation->getName(),
								'model' => $deviceInformation->getModel(),
								'icon' => $deviceInformation->getIcon(),
								'category' => $deviceInformation->getCategory(),
								'latitude' => $deviceInformation->getLat(),
								'longitude' => $deviceInformation->getLon(),
								'product_id' => $deviceInformation->getProductId(),
								'product_name' => $deviceInformation->getProductName(),
								'sn' => $deviceFactoryInfos?->getSn(),
								'mac' => $deviceFactoryInfos?->getMac(),
								'data_points' => $dataPoints,
							],
						),
					);

				} catch (Throwable $ex) {
					$deferred->reject(
						new TuyaExceptions\InvalidState(
							'Could not create device description message',
							$ex->getCode(),
							$ex,
						),
					);

					return;
				}

				if (in_array($deviceInformation->getCategory(), self::GATEWAY_CATEGORIES, true)) {
					try {
						$response = await($this->getCloudApiConnection()->getUserDeviceChildren($id));

						$children = $response->getResult();
					} catch (Throwable $ex) {
						$deferred->reject(
							new TuyaExceptions\InvalidState(
								'Could not load device children from Tuya cloud',
								$ex->getCode(),
								$ex,
							),
						);

						return;
					}

					foreach ($children as $child) {
						try {
							$response = await($this->getCloudApiConnection()->getDeviceDetail($child->getId()));

							$childDeviceInformation = $response->getResult();
						} catch (Throwable $ex) {
							$this->logger->error(
								'Could not load child device basic information from Tuya cloud',
								[
									'source' => Sources\Connector::TUYA->value,
									'type' => 'discovery-client',
									'exception' => Logging\Logger::buildException($ex),
									'device' => [
										'identifier' => $child->getId(),
										'ip_address' => $ipAddress,
									],
								],
							);

							continue;
						}

						$dataPoints = $this->loadLocalDeviceDataPoints(
							$child->getId(),
							$deviceInformation->getLocalKey(),
							$ipAddress,
							TuyaTypes\DeviceProtocolVersion::from($version),
							$id,
							$child->getNodeId(),
						);

						try {
							/** @var array<API\Messages\Response\DeviceFactoryInfos> $childDeviceFactoryInfosFiltered */
							$childDeviceFactoryInfosFiltered = array_values(array_filter(
								$devicesFactoryInfos,
								static fn (API\Messages\Response\DeviceFactoryInfos $item): bool => $id === $item->getId(),
							));

							$childDeviceFactoryInfos = count(
								$childDeviceFactoryInfosFiltered,
							) > 0
								? $childDeviceFactoryInfosFiltered[0]
								: null;

							$this->queue->append(
								$this->messageBuilder->create(
									Queue\Messages\StoreLocalDevice::class,
									[
										'connector' => $this->connector->getId(),
										'id' => $child->getId(),
										'ip_address' => null,
										'local_key' => $childDeviceInformation->getLocalKey(),
										'encrypted' => $encrypted,
										'version' => $version,
										'gateway' => $id,
										'node_id' => $child->getNodeId(),
										'name' => $childDeviceInformation->getName(),
										'model' => $childDeviceInformation->getModel(),
										'icon' => $childDeviceInformation->getIcon(),
										'category' => $childDeviceInformation->getCategory(),
										'latitude' => $childDeviceInformation->getLat(),
										'longitude' => $childDeviceInformation->getLon(),
										'product_id' => $childDeviceInformation->getProductId(),
										'product_name' => $childDeviceInformation->getProductName(),
										'sn' => $childDeviceFactoryInfos?->getSn(),
										'mac' => $childDeviceFactoryInfos?->getMac(),
										'data_points' => $dataPoints,
									],
								),
							);
						} catch (Throwable $ex) {
							$this->logger->error(
								'Could not create child device description message',
								[
									'source' => Sources\Connector::TUYA->value,
									'type' => 'discovery-client',
									'exception' => Logging\Logger::buildException($ex),
								],
							);

							continue;
						}
					}
				}

				$deferred->resolve(true);
			}))
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @param array<API\Messages\Response\DeviceFactoryInfos> $devicesFactoryInfos
	 *
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Throwable
	 */
	private function handleFoundCloudDevice(
		API\Messages\Response\Device $device,
		array $devicesFactoryInfos,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->getCloudApiConnection()
			->connect()
			->then(async(function () use ($device, $devicesFactoryInfos, $deferred): void {
				/** @var array<API\Messages\Response\DeviceFactoryInfos> $deviceFactoryInfosFiltered */
				$deviceFactoryInfosFiltered = array_values(array_filter(
					$devicesFactoryInfos,
					static fn (API\Messages\Response\DeviceFactoryInfos $item): bool => $device->getId() === $item->getId(),
				));

				$deviceFactoryInfos = count($deviceFactoryInfosFiltered) > 0 ? $deviceFactoryInfosFiltered[0] : null;

				$dataPoints = [];

				try {
					$response = await($this->getCloudApiConnection()->getDeviceSpecification($device->getId()));

					$deviceSpecifications = $response->getResult();

					$dataPointsInfos = [];

					foreach ($deviceSpecifications->getFunctions() as $function) {
						if (!array_key_exists($function->getCode(), $dataPointsInfos)) {
							$dataPointsInfos[$function->getCode()] = [
								'function' => null,
								'status' => null,
							];
						}

						$dataPointsInfos[$function->getCode()]['function'] = $function;
					}

					foreach ($deviceSpecifications->getStatus() as $status) {
						if (!array_key_exists($status->getCode(), $dataPointsInfos)) {
							$dataPointsInfos[$status->getCode()] = [
								'function' => null,
								'status' => null,
							];
						}

						$dataPointsInfos[$status->getCode()]['status'] = $status;
					}

					foreach ($dataPointsInfos as $dataPointInfos) {
						$dataPointFunction = $dataPointInfos['function'];

						$dataPointStatus = $dataPointInfos['status'];

						if ($dataPointFunction === null && $dataPointStatus === null) {
							continue;
						}

						$dataPointCode = null;
						$dataPointDataType = ValuesTypes\DataType::UNKNOWN;

						if ($dataPointFunction !== null) {
							$dataPointCode = $dataPointFunction->getCode();
							$dataPointType = Utils\Strings::lower($dataPointFunction->getType());
							$dataPointSpecification = $dataPointFunction->getValues();

						} else {
							$dataPointCode = $dataPointStatus->getCode();
							$dataPointType = Utils\Strings::lower($dataPointStatus->getType());
							$dataPointSpecification = $dataPointStatus->getValues();
						}

						if ($dataPointType === 'boolean') {
							$dataPointDataType = ValuesTypes\DataType::BOOLEAN;
						} elseif ($dataPointType === 'integer') {
							$dataPointDataType = ValuesTypes\DataType::INT;
						} elseif ($dataPointType === 'enum') {
							$dataPointDataType = ValuesTypes\DataType::ENUM;
						}

						try {
							$dataPointSpecification = Utils\Json::decode($dataPointSpecification, forceArrays: true);
							$dataPointSpecification = Utils\ArrayHash::from(
								is_array($dataPointSpecification) ? $dataPointSpecification : [],
							);

						} catch (Utils\JsonException) {
							$dataPointSpecification = Utils\ArrayHash::from([]);
						}

						$dataPoints[] = [
							'device' => $device->getId(),

							'code' => $dataPointCode,
							'name' => $dataPointCode,
							'data_type' => $dataPointDataType->value,
							'unit' => $dataPointSpecification->offsetExists('unit')
								? strval($dataPointSpecification->offsetGet('unit'))
								: null,
							'range' => $dataPointSpecification->offsetExists('range')
							&& (
								is_array($dataPointSpecification->offsetGet('range'))
								|| $dataPointSpecification->offsetGet('range') instanceof Utils\ArrayHash
							)
								? array_map(
									static fn ($item): string => strval($item),
									(array) $dataPointSpecification->offsetGet('range'),
								)
								: [],
							'min' => $dataPointSpecification->offsetExists('min')
								? floatval($dataPointSpecification->offsetGet('min'))
								: null,
							'max' => $dataPointSpecification->offsetExists('max')
								? floatval($dataPointSpecification->offsetGet('max'))
								: null,
							'step' => $dataPointSpecification->offsetExists('step')
								? floatval($dataPointSpecification->offsetGet('step'))
								: null,
							'scale' => $dataPointSpecification->offsetExists('scale')
								? intval($dataPointSpecification->offsetGet('scale'))
								: null,
							'queryable' => $dataPointStatus !== null,
							'settable' => $dataPointFunction !== null,
						];
					}
				} catch (Throwable $ex) {
					$deferred->reject(
						new TuyaExceptions\InvalidState(
							'Device specification could not be loaded',
							$ex->getCode(),
							$ex,
						),
					);

					return;
				}

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreCloudDevice::class,
						[
							'connector' => $this->connector->getId(),
							'id' => $device->getId(),
							'local_key' => $device->getLocalKey(),
							'ip_address' => $device->getIp(),
							'name' => $device->getName(),
							'model' => $device->getModel(),
							'icon' => $device->getIcon(),
							'category' => $device->getCategory(),
							'latitude' => $device->getLat(),
							'longitude' => $device->getLon(),
							'product_id' => $device->getProductId(),
							'product_name' => $device->getProductName(),
							'sn' => $deviceFactoryInfos?->getSn(),
							'mac' => $deviceFactoryInfos?->getMac(),
							'data_points' => $dataPoints,
						],
					),
				);

				$deferred->resolve(true);
			}))
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return array<array<string, mixed>>
	 */
	private function loadLocalDeviceDataPoints(
		string $id,
		string $localKey,
		string $ipAddress,
		TuyaTypes\DeviceProtocolVersion $version,
		string|null $gatewayId = null,
		string|null $nodeId = null,
	): array
	{
		$dataPoints = [];

		$localApi = $this->localApiFactory->create(
			$id,
			$gatewayId,
			$nodeId,
			$localKey,
			$ipAddress,
			$version,
		);

		try {
			await($localApi->connect());
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not establish local connection with device',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => Logging\Logger::buildException($ex),
					'device' => [
						'identifier' => $id,
						'ip_address' => $ipAddress,
						'gateway_id' => $gatewayId,
						'node_id' => $nodeId,
					],
				],
			);

			return [];
		}

		try {
			if ($localApi->isConnected()) {
				/** @var array<API\Messages\Response\DeviceDataPointState>|TuyaTypes\LocalDeviceError $deviceStatuses */
				$deviceStatuses = await($localApi->readStates());

				$localApi->disconnect();

				if (is_array($deviceStatuses)) {
					foreach ($deviceStatuses as $status) {
						$dataType = ValuesTypes\DataType::UNKNOWN;

						if (is_bool($status->getValue())) {
							$dataType = ValuesTypes\DataType::BOOLEAN;
						} elseif (is_float($status->getValue())) {
							$dataType = ValuesTypes\DataType::FLOAT;
						} elseif (is_numeric($status->getValue())) {
							$dataType = ValuesTypes\DataType::INT;
						} elseif (is_string($status->getValue())) {
							$dataType = ValuesTypes\DataType::STRING;
						}

						$dataPoints[] = [
							'device' => $id,

							'code' => $status->getCode(),
							'name' => $status->getCode(),
							'data_type' => $dataType->value,
							'unit' => null,
							'format' => null,
							'min' => null,
							'max' => null,
							'step' => null,
							'queryable' => true,
							'settable' => true,
						];
					}
				}
			} else {
				$this->logger->error(
					'Local connection with device failed',
					[
						'source' => Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
						'device' => [
							'identifier' => $id,
							'ip_address' => $ipAddress,
							'gateway_id' => $gatewayId,
							'node_id' => $nodeId,
						],
					],
				);
			}
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not read device data points states',
				[
					'source' => Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => Logging\Logger::buildException($ex),
					'device' => [
						'identifier' => $id,
						'ip_address' => $ipAddress,
						'gateway_id' => $gatewayId,
						'node_id' => $nodeId,
					],
				],
			);
		}

		return $dataPoints;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getCloudApiConnection(): API\OpenApi
	{
		if ($this->cloudApiConnection === null) {
			assert(is_string($this->connectorHelper->getAccessId($this->connector)));
			assert(is_string($this->connectorHelper->getAccessSecret($this->connector)));

			$this->cloudApiConnection = $this->openApiFactory->create(
				$this->connector->getIdentifier(),
				$this->connectorHelper->getAccessId($this->connector),
				$this->connectorHelper->getAccessSecret($this->connector),
				$this->connectorHelper->getOpenApiEndpoint($this->connector),
			);
		}

		return $this->cloudApiConnection;
	}

}
