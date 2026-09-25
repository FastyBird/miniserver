<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           31.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use BinSoul\Net\Mqtt as NetMqtt;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\EventLoop;
use React\Promise;
use stdClass;
use Throwable;
use TypeError;
use ValueError;
use function assert;
use function sprintf;

/**
 * Connector sub-devices discovery client
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery
{

	use Nette\SmartObject;

	private const DISCOVERY_TIMEOUT = 100;

	public const DISCOVERY_TOPIC = '%s/bridge/request/permit_join';

	private Documents\Devices\Bridge|null $onlyBridge = null;

	private Clients\Subscribers\Bridge $bridgeSubscriber;

	private bool $subscribed = false;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Clients\Subscribers\BridgeFactory $bridgeSubscriberFactory,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Connectors\Connector $connectorHelper,
		private readonly Helpers\Devices\Bridge $bridgeHelper,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesUtilities\ConnectorConnection $connectorConnectionManager,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->bridgeSubscriber = $this->bridgeSubscriberFactory->create($this->connector);
	}

	/**
	 * @return Promise\PromiseInterface<mixed>
	 *
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function discover(Documents\Devices\Bridge|null $onlyBridge = null): Promise\PromiseInterface
	{
		$this->onlyBridge = $onlyBridge;

		$client = $this->getClient();
		$client->onConnect[] = function (): void {
			$this->onConnect();
		};

		if (!$this->isRunning()) {
			$this->bridgeSubscriber->subscribe($client);

			$this->subscribed = true;
		}

		return $client->connect();
	}

	/**
	 * @return Promise\PromiseInterface<mixed>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): Promise\PromiseInterface
	{
		$client = $this->getClient();

		return $client->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function onConnect(): void
	{
		$promises = [];

		if ($this->onlyBridge !== null) {
			$this->logger->debug(
				'Starting sub-devices discovery for selected Zigbee2MQTT bridge',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $this->onlyBridge->getId()->toString(),
					],
				],
			);

			$promises[] = $this->discoverSubDevices($this->onlyBridge);

		} else {
			$this->logger->debug(
				'Starting sub-devices discovery for all registered Zigbee2MQTT bridges',
				[
					'source' => Sources\Connector::ZIGBEE2MQTT->value,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$findDevicesQuery = new Queries\Configuration\FindBridgeDevices();
			$findDevicesQuery->forConnector($this->connector);

			$bridges = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\Bridge::class,
			);

			foreach ($bridges as $bridge) {
				$promises[] = $this->discoverSubDevices($bridge);
			}
		}

		Promise\all($promises)
			->then(function (): void {
				$this->eventLoop->addTimer(self::DISCOVERY_TIMEOUT, function (): void {
					$this->dispatcher?->dispatch(
						new DevicesEvents\TerminateConnector(
							Sources\Connector::ZIGBEE2MQTT,
							'Devices discovery failed',
						),
					);
				});
			})
			->catch(function (): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						Sources\Connector::ZIGBEE2MQTT,
						'Devices discovery failed',
					),
				);
			});
	}

	/**
	 * @return Promise\PromiseInterface<true>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function discoverSubDevices(
		Documents\Devices\Bridge $bridge,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->subscribed) {
			$topic = sprintf(Zigbee2Mqtt\Constants::BRIDGE_TOPIC, $this->bridgeHelper->getBaseTopic($bridge));
			$topic = new NetMqtt\DefaultSubscription($topic);

			$this->getClient()
				->subscribe($topic)
				->then(
					function (mixed $subscription) use ($deferred, $bridge): void {
						assert($subscription instanceof NetMqtt\Subscription);

						$this->logger->info(
							sprintf('Subscribed to: %s', $subscription->getFilter()),
							[
								'source' => Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'discovery-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);

						$this->publishDiscoveryRequest($bridge)
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});
					},
					function (Throwable $ex): void {
						$this->logger->error(
							$ex->getMessage(),
							[
								'source' => Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'discovery-client',
								'exception' => Logging\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
				);
		} else {
			$this->publishDiscoveryRequest($bridge)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		}

		return $deferred->promise();
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws CoreExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function isRunning(): bool
	{
		return $this->connectorConnectionManager->isRunning($this->connector);
	}

	/**
	 * @return Promise\PromiseInterface<true>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function publishDiscoveryRequest(
		Documents\Devices\Bridge $bridge,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$topic = sprintf(self::DISCOVERY_TOPIC, $this->bridgeHelper->getBaseTopic($bridge));

		$payload = new stdClass();
		$payload->value = true;
		$payload->time = self::DISCOVERY_TIMEOUT;

		try {
			$this->getClient()->publish(
				$topic,
				Utils\Json::encode($payload),
			)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		} catch (Utils\JsonException $ex) {
			$deferred->reject(
				new Zigbee2MqttExceptions\InvalidState('Discovery action could not be published', $ex->getCode(), $ex),
			);
		}

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getClient(): API\Client
	{
		return $this->connectionManager->getClient(
			$this->connector->getId()->toString(),
			$this->connectorHelper->getServerAddress($this->connector),
			$this->connectorHelper->getServerPort($this->connector),
			$this->connectorHelper->getUsername($this->connector),
			$this->connectorHelper->getPassword($this->connector),
		);
	}

}
