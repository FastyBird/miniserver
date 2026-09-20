<?php declare(strict_types = 1);

/**
 * Mqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use BinSoul\Net\Mqtt as NetMqtt;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Models;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use InvalidArgumentException;
use Nette;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
use function assert;
use function sprintf;

/**
 * Zigbee2MQTT MQTT client
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Mqtt implements Client
{

	use Nette\SmartObject;

	private Clients\Subscribers\Bridge $bridgeSubscriber;

	private Clients\Subscribers\Device $deviceSubscriber;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Clients\Subscribers\BridgeFactory $bridgeSubscriberFactory,
		private readonly Clients\Subscribers\DeviceFactory $deviceSubscriberFactory,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Models\StateRepository $stateRepository,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly Helpers\Connectors\Connector $connectorHelper,
		private readonly Helpers\Devices\Bridge $bridgeHelper,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
		$this->bridgeSubscriber = $this->bridgeSubscriberFactory->create($this->connector);
		$this->deviceSubscriber = $this->deviceSubscriberFactory->create($this->connector);
	}

	/**
	 * @return Promise\PromiseInterface<mixed>
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): Promise\PromiseInterface
	{
		$client = $this->getClient();
		$client->onConnect[] = function (): void {
			$this->onConnect();
		};

		$this->bridgeSubscriber->subscribe($client);
		$this->deviceSubscriber->subscribe($client);

		$findDevicesQuery = new Queries\Configuration\FindBridgeDevices();
		$findDevicesQuery->forConnector($this->connector);

		$bridges = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Bridge::class,
		);

		foreach ($bridges as $bridge) {
			$findDevicesQuery = new Queries\Configuration\FindSubDevices();
			$findDevicesQuery->forParent($bridge);

			$subDevices = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\SubDevice::class,
			);

			foreach ($subDevices as $subDevice) {
				$findDeviceProperties = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
				$findDeviceProperties->forDevice($subDevice);

				$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
					$findDeviceProperties,
					DevicesDocuments\Devices\Properties\Dynamic::class,
				);

				foreach ($properties as $property) {
					$state = $this->devicePropertiesStatesManager->read(
						$property,
						MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
					);

					if ($state instanceof DevicesDocuments\States\Devices\Properties\Property) {
						$this->stateRepository->set($property->getId(), $state->getGet()->getActualValue());
					}
				}

				$findChannels = new Queries\Configuration\FindChannels();
				$findChannels->forDevice($subDevice);

				$channels = $this->channelsConfigurationRepository->findAllBy(
					$findChannels,
					Documents\Channels\Channel::class,
				);

				foreach ($channels as $channel) {
					$findChannelProperties = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findChannelProperties->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelProperties,
						DevicesDocuments\Channels\Properties\Dynamic::class,
					);

					foreach ($properties as $property) {
						$state = $this->channelPropertiesStatesManager->read(
							$property,
							MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
						);

						if ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
							$this->stateRepository->set($property->getId(), $state->getGet()->getActualValue());
						}
					}
				}
			}
		}

		return $client->connect();
	}

	/**
	 * @return Promise\PromiseInterface<mixed>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
	 * @throws Exceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function onConnect(): void
	{
		$findDevicesQuery = new Queries\Configuration\FindBridgeDevices();
		$findDevicesQuery->forConnector($this->connector);

		$bridges = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Bridge::class,
		);

		foreach ($bridges as $bridge) {
			$topic = sprintf(Zigbee2Mqtt\Constants::BRIDGE_TOPIC, $this->bridgeHelper->getBaseTopic($bridge));
			$topic = new NetMqtt\DefaultSubscription($topic);

			$this->getClient()
				->subscribe($topic)
				->then(
					function (mixed $subscription): void {
						assert($subscription instanceof NetMqtt\Subscription);

						$this->logger->info(
							sprintf('Subscribed to: %s', $subscription->getFilter()),
							[
								'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'mqtt-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
					function (Throwable $ex): void {
						$this->logger->error(
							$ex->getMessage(),
							[
								'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT->value,
								'type' => 'mqtt-client',
								'exception' => ToolsHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
				);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ToolsExceptions\InvalidState
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
