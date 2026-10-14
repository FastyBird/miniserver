<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Clients;

use BadMethodCallException;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\API;
use FastyBird\Connector\Sonoff\Documents;
use FastyBird\Connector\Sonoff\Exceptions as SonoffExceptions;
use FastyBird\Connector\Sonoff\Helpers;
use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use stdClass;
use Throwable;
use TypeError;
use ValueError;
use function array_key_exists;
use function array_map;
use function assert;
use function count;
use function get_object_vars;
use function in_array;
use function intval;
use function is_array;
use function method_exists;
use function property_exists;
use function React\Async\async;
use function sprintf;
use function strtolower;
use const DIRECTORY_SEPARATOR;

/**
 * Devices discovery client
 */
final class Discovery
{

	private const int LAN_SEARCH_TIMEOUT = 60;

	private const float STORING_CHECK_INTERVAL = 0.1;

	// 60 seconds of checks
	private const int STORING_CHECK_ATTEMPTS = 600;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private EventLoop\TimerInterface|null $storingTimer = null;

	private int $storingChecks = 0;

	/** @var array<string, array<string, string|int>> */
	private array $foundLocalDevices = [];

	/**
	 * The ids of the devices the cloud gave a key for
	 *
	 * @var array<string, true>
	 */
	private array $keyedDevices = [];

	/**
	 * The devices the cloud returned, stored once the search of the network is over
	 */
	private API\Messages\Response\Cloud\Things|null $cloudDevices = null;

	private API\LanApi|null $lanApiConnection = null;

	private API\CloudApi|null $cloudApiConnection = null;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly API\CloudApiFactory $cloudApiFactory,
		private readonly Helpers\MessageBuilder $entityHelper,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Queue\Queue $queue,
		private readonly Sonoff\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws SonoffExceptions\InvalidState
	 * @throws Throwable
	 */
	public function discover(): void
	{
		$this->discoverCloudDevices()
			->then(function (): Promise\PromiseInterface {
				$mode = $this->connectorHelper->getClientMode($this->connector);

				if (
					$mode === Types\ClientMode::LAN
					|| $mode === Types\ClientMode::AUTO
				) {
					return $this->discoverLanDevices();
				}

				return Promise\resolve(true);
			})
			->then(function (): void {
				// The devices are stored once the search of the network is over, so that each takes the address it
				// was found at
				if ($this->cloudDevices !== null) {
					$this->handleFoundCloudDevices($this->cloudDevices);
				}

				$this->finishWhenStored();
			})
			->catch(function (): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						Sources\Connector::SONOFF,
						'Devices discovery failed',
					),
				);
			});
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
			$this->handlerTimer = null;
		}

		if ($this->storingTimer !== null) {
			$this->eventLoop->cancelTimer($this->storingTimer);
			$this->storingTimer = null;
		}

		$this->getCloudApiConnection()->disconnect();
		$this->getLanConnection()->disconnect();
	}

	/**
	 * Discovery is finished once the devices it found are stored. The connector is stopped a few seconds after it is
	 * asked to terminate and does not wait for its queue, so the queue is checked until it is empty, for a minute at
	 * most
	 */
	private function finishWhenStored(): void
	{
		if ($this->queue->isEmpty()) {
			$this->finish();

			return;
		}

		$this->storingChecks = 0;

		$this->storingTimer = $this->eventLoop->addPeriodicTimer(
			self::STORING_CHECK_INTERVAL,
			function (): void {
				$this->storingChecks++;

				$stored = $this->queue->isEmpty();

				if (!$stored && $this->storingChecks < self::STORING_CHECK_ATTEMPTS) {
					return;
				}

				if ($this->storingTimer !== null) {
					$this->eventLoop->cancelTimer($this->storingTimer);
					$this->storingTimer = null;
				}

				if (!$stored) {
					$this->logger->warning(
						'Found devices were not all stored in time, devices discovery is finished anyway',
						[
							'source' => Sources\Connector::SONOFF->value,
							'type' => 'discovery-client',
						],
					);
				}

				$this->finish();
			},
		);
	}

	private function finish(): void
	{
		$this->dispatcher?->dispatch(
			new DevicesEvents\TerminateConnector(
				Sources\Connector::SONOFF,
				'Devices discovery finished',
			),
		);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws SonoffExceptions\CloudApiCall
	 * @throws SonoffExceptions\CloudApiError
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function discoverCloudDevices(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(
			'Starting cloud devices discovery',
			[
				'source' => Sources\Connector::SONOFF->value,
				'type' => 'discovery-client',
			],
		);

		$apiClient = $this->getCloudApiConnection();

		try {
			$apiClient->connect();

		} catch (SonoffExceptions\CloudApiCall $ex) {
			$this->logger->error(
				'Log into eWelink account failed',
				[
					'source' => Sources\Connector::SONOFF->value,
					'type' => 'discovery-client',
					'exception' => Logging\Logger::buildException($ex),
				],
			);

			return Promise\reject($ex);
		}

		$apiClient->getFamily()
			->then(function (API\Messages\Response\Cloud\Family $family) use ($deferred, $apiClient): void {
				$apiClient->getFamilyThings($family->getFamilyId())
					->then(function (API\Messages\Response\Cloud\Things $things) use ($deferred): void {
						$this->registerDeviceKeys($things);

						$this->cloudDevices = $things;

						$deferred->resolve(true);
					})
					->catch(function (Throwable $ex) use ($deferred): void {
						$this->logger->error(
							'Loading devices from cloud failed',
							[
								'source' => Sources\Connector::SONOFF->value,
								'type' => 'discovery-client',
								'exception' => Logging\Logger::buildException($ex),
							],
						);

						$deferred->reject($ex);
					});
			})
			->catch(function (Throwable $ex) use ($deferred): void {
				$this->logger->error(
					'Loading homes from cloud failed',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
					],
				);

				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws BadMethodCallException
	 * @throws SonoffExceptions\InvalidState
	 * @throws RuntimeException
	 */
	private function discoverLanDevices(): Promise\PromiseInterface
	{
		$this->logger->debug(
			'Starting lan devices discovery',
			[
				'source' => Sources\Connector::SONOFF->value,
				'type' => 'discovery-client',
			],
		);

		$deferred = new Promise\Deferred();

		$apiClient = $this->getLanConnection();

		$apiClient->onMessage(function (API\Messages\Message $message): void {
			if ($message instanceof API\Messages\Response\Lan\DeviceEvent) {
				// For a device with a key, only an announcement whose data decrypted with that key may set its address;
				// the API decrypts the data of a device whose key it holds. The address itself is not part of the
				// encrypted data. A device without a key has nothing to check its announcement against.
				if (
					array_key_exists(strtolower($message->getId()), $this->keyedDevices)
					&& (!$message->isEncrypted() || $message->getData() === null)
				) {
					$this->logger->warning(
						'An announcement not decrypted with the device key was not taken as its address',
						[
							'source' => Sources\Connector::SONOFF->value,
							'type' => 'discovery-client',
							'device' => [
								'id' => $message->getId(),
							],
						],
					);

					return;
				}

				$this->foundLocalDevices[strtolower($message->getId())] = [
					'ipAddress' => $message->getIpAddress(),
					'domain' => $message->getDomain(),
					'port' => $message->getPort(),
				];
			}
		});

		// Searching timeout
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::LAN_SEARCH_TIMEOUT,
			async(static function () use ($deferred, $apiClient): void {
				$apiClient->disconnect();

				$deferred->resolve(true);
			}),
		);

		$apiClient->connect();

		return $deferred->promise();
	}

	private function registerDeviceKeys(API\Messages\Response\Cloud\Things $things): void
	{
		// The keys are known to the LAN API before it hears of any device, so that the announcements of a device with
		// a key are decrypted, and only those are taken as the device's
		foreach ($things->getDevices() as $device) {
			if ($device->getDeviceKey() !== '') {
				$this->keyedDevices[strtolower($device->getDeviceId())] = true;

				$this->getLanConnection()->registerDeviceKey($device->getDeviceId(), $device->getDeviceKey());
			}
		}
	}

	private function handleFoundCloudDevices(API\Messages\Response\Cloud\Things $things): void
	{
		foreach ($things->getDevices() as $device) {
			try {
				$mappingConfiguration = Utils\Json::decode($this->getUiidMapping($device->getExtra()->getUiid()));
				assert($mappingConfiguration instanceof stdClass);
			} catch (SonoffExceptions\InvalidState $ex) {
				$this->logger->error(
					'Params mapping for device UIID could not be loaded',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
						'device' => [
							'id' => $device->getDeviceId(),
							'uiid' => $device->getExtra()->getUiid(),
						],
					],
				);

				continue;
			} catch (Utils\JsonException $ex) {
				$this->logger->error(
					'Device params mapping could not be prepared for mapping',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
						'device' => [
							'id' => $device->getDeviceId(),
							'uiid' => $device->getExtra()->getUiid(),
							'state' => $device->getState()?->toArray(),
						],
					],
				);

				continue;
			}

			$parameters = [];

			foreach (get_object_vars($mappingConfiguration) as $identifier => $configuration) {
				assert($configuration instanceof stdClass);

				if ($identifier === Types\ChannelGroup::RF_LIST->value) {
					// TODO: Not supported now
				} else {
					if (property_exists($configuration, 'properties') && property_exists($configuration, 'length')) {
						if (in_array($identifier, $device->getDenyFeatures(), true)) {
							continue;
						}

						$count = intval($configuration->length);

						if ($identifier === Types\ChannelGroup::SWITCHES->value) {
							$count = intval($configuration->length);

							if ($device->getState() !== null) {
								if (
									method_exists($device->getState(), 'getSwitches')
									&& is_array($device->getState()->getSwitches())
								) {
									$count = count($device->getState()->getSwitches());
								} elseif (
									method_exists($device->getState(), 'getConfiguration')
									&& is_array($device->getState()->getConfiguration())
								) {
									$count = count($device->getState()->getConfiguration());
								} elseif (
									method_exists($device->getState(), 'getPulses')
									&& is_array($device->getState()->getPulses())
								) {
									$count = count($device->getState()->getPulses());
								}
							}
						}

						for ($i = 0; $i < $count; $i++) {
							foreach (get_object_vars(
								$configuration->properties,
							) as $subIdentifier => $subConfiguration) {
								assert($subConfiguration instanceof stdClass);

								$parameters[] = [
									'group' => (
										property_exists($subConfiguration, 'group')
											? $subConfiguration->group
											: $subIdentifier
										) . '_' . $i,
									'identifier' => $subIdentifier,
									'name' => $subConfiguration->name,
									'type' => $subConfiguration->type,
									'dataType' => $subConfiguration->data_type,
									'format' => property_exists($subConfiguration, 'format')
										? $subConfiguration->format
										: null,
									'settable' => $subConfiguration->settable,
									'queryable' => $subConfiguration->queryable,
									'scale' => property_exists($subConfiguration, 'scale')
										? $subConfiguration->scale
										: null,
								];
							}
						}
					} else {
						if (
							in_array($identifier, $device->getDenyFeatures(), true)
							|| (
								$identifier === Types\Parameter::STATUS_LED->value
								&& in_array('sled', $device->getDenyFeatures(), true)
							)
						) {
							continue;
						}

						$parameters[] = [
							'group' => property_exists($configuration, 'group') ? $configuration->group : $identifier,
							'identifier' => $identifier,
							'name' => $configuration->name,
							'type' => $configuration->type,
							'dataType' => $configuration->data_type,
							'format' => property_exists($configuration, 'format') ? $configuration->format : null,
							'settable' => $configuration->settable,
							'queryable' => $configuration->queryable,
							'scale' => property_exists($configuration, 'scale')
								? $configuration->scale
								: null,
						];
					}
				}
			}

			// The address the search of the network found the device at; a device it did not find is stored without
			// one, which leaves the address it was stored with before as it is
			$localConfiguration = $this->foundLocalDevices[strtolower($device->getDeviceId())] ?? null;

			try {
				$this->queue->append(
					$this->entityHelper->create(
						Queue\Messages\StoreDevice::class,
						[
							'connector' => $this->connector->getId(),
							'id' => $device->getDeviceId(),
							'apiKey' => $device->getApiKey(),
							'deviceKey' => $device->getDeviceKey(),
							'uiid' => $device->getExtra()->getUiid(),
							'name' => $device->getName(),
							'description' => $device->getExtra()->getDescription(),
							'brandName' => $device->getBrandName(),
							'brandLogo' => $device->getBrandLogo(),
							'productModel' => $device->getProductModel(),
							'model' => $device->getExtra()->getModel(),
							'mac' => $device->getExtra()->getMac(),
							'ipAddress' => $localConfiguration !== null ? $localConfiguration['ipAddress'] : null,
							'domain' => $localConfiguration !== null ? $localConfiguration['domain'] : null,
							'port' => $localConfiguration !== null ? $localConfiguration['port'] : null,
							'parameters' => array_map(
								static fn (array $parameter): array => [
									'group' => $parameter['group'],
									'identifier' => $parameter['identifier'],
									'name' => $parameter['name'],
									'type' => $parameter['type'],
									'dataType' => $parameter['dataType'],
									'format' => $parameter['format'],
									'settable' => $parameter['settable'],
									'queryable' => $parameter['queryable'],
									'scale' => $parameter['scale'],
								],
								$parameters,
							),
						],
					),
				);
			} catch (SonoffExceptions\Runtime $ex) {
				$this->logger->error(
					'Found device could not be attached to processing queue',
					[
						'source' => Sources\Connector::SONOFF->value,
						'type' => 'discovery-client',
						'exception' => Logging\Logger::buildException($ex),
						'device' => $device->toArray(),
					],
				);
			}
		}
	}

	/**
	 * @throws SonoffExceptions\InvalidState
	 */
	private function getUiidMapping(int $uiid): string
	{
		try {
			$mapping = Utils\FileSystem::read(
				Sonoff\DI\SonoffExtension::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'uiid' . DIRECTORY_SEPARATOR . sprintf(
					'uiid%d_mapping.json',
					$uiid,
				),
			);

		} catch (Nette\IOException) {
			throw new SonoffExceptions\InvalidState('Validation schema for response could not be loaded');
		}

		return $mapping;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws SonoffExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getCloudApiConnection(): API\CloudApi
	{
		if ($this->cloudApiConnection === null) {
			$this->cloudApiConnection = $this->cloudApiFactory->create(
				$this->connectorHelper->getUsername($this->connector),
				$this->connectorHelper->getPassword($this->connector),
				$this->connectorHelper->getAppId($this->connector),
				$this->connectorHelper->getAppSecret($this->connector),
				$this->connectorHelper->getRegion($this->connector),
			);
		}

		return $this->cloudApiConnection;
	}

	private function getLanConnection(): API\LanApi
	{
		if ($this->lanApiConnection === null) {
			$this->lanApiConnection = $this->lanApiFactory->create();
		}

		return $this->lanApiConnection;
	}

}
