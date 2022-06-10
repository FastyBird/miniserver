<?php declare(strict_types = 1);

/**
 * WsServerSubscriber.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Subscribers
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer\Subscribers;

use FastyBird\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\MiniServer\States;
use FastyBird\WsServerPlugin\Events as WsServerPluginEvents;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use Throwable;

/**
 * WS events subscriber
 *
 * @package         FastyBird:MiniServer!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsServerSubscriber implements EventDispatcher\EventSubscriberInterface
{

	/** @var DevicesModuleModels\Connectors\Properties\IPropertiesRepository */
	private DevicesModuleModels\Connectors\Properties\IPropertiesRepository $connectorPropertiesRepository;

	/** @var DevicesModuleModels\Devices\Properties\IPropertiesRepository */
	private DevicesModuleModels\Devices\Properties\IPropertiesRepository $devicePropertiesRepository;

	/** @var DevicesModuleModels\Channels\Properties\IPropertiesRepository */
	private DevicesModuleModels\Channels\Properties\IPropertiesRepository $channelPropertiesRepository;

	/** @var DevicesModuleModels\States\ConnectorPropertiesRepository */
	private DevicesModuleModels\States\ConnectorPropertiesRepository $connectorPropertiesStatesRepository;

	/** @var DevicesModuleModels\States\DevicePropertiesRepository */
	private DevicesModuleModels\States\DevicePropertiesRepository $devicePropertiesStatesRepository;

	/** @var DevicesModuleModels\States\ChannelPropertiesRepository */
	private DevicesModuleModels\States\ChannelPropertiesRepository $channelPropertiesStatesRepository;

	/** @var Log\LoggerInterface */
	protected Log\LoggerInterface $logger;

	/** @var BootstrapHelpers\Database */
	private BootstrapHelpers\Database $database;

	/**
	 * {@inheritDoc}
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			WsServerPluginEvents\ClientSubscribedEvent::class => 'clientSubscribed',
			WsServerPluginEvents\IncomingMessage::class => 'incomingMessage',
		];
	}

	public function __construct(
		DevicesModuleModels\Connectors\Properties\IPropertiesRepository $connectorPropertiesRepository,
		DevicesModuleModels\Devices\Properties\IPropertiesRepository $devicePropertiesRepository,
		DevicesModuleModels\Channels\Properties\IPropertiesRepository $channelPropertiesRepository,
		DevicesModuleModels\States\ConnectorPropertiesRepository $connectorPropertiesStatesRepository,
		DevicesModuleModels\States\DevicePropertiesRepository $devicePropertiesStatesRepository,
		DevicesModuleModels\States\ChannelPropertiesRepository $channelPropertiesStatesRepository,
		BootstrapHelpers\Database $database,
		?Log\LoggerInterface $logger
	) {
		$this->connectorPropertiesRepository = $connectorPropertiesRepository;
		$this->devicePropertiesRepository = $devicePropertiesRepository;
		$this->channelPropertiesRepository = $channelPropertiesRepository;

		$this->connectorPropertiesStatesRepository = $connectorPropertiesStatesRepository;
		$this->devicePropertiesStatesRepository = $devicePropertiesStatesRepository;
		$this->channelPropertiesStatesRepository = $channelPropertiesStatesRepository;

		$this->database = $database;
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param WsServerPluginEvents\ClientSubscribedEvent $event
	 *
	 * @return void
	 */
	public function clientSubscribed(
		WsServerPluginEvents\ClientSubscribedEvent $event
	): void {
		try {
			$findDevicesProperties = new DevicesModuleQueries\FindDevicePropertiesQuery();

			$devicesProperties = $this->devicePropertiesRepository->getResultSet($findDevicesProperties);

			/** @var DevicesModuleEntities\Devices\Properties\Property $deviceProperty */
			foreach ($devicesProperties as $deviceProperty) {
				$dynamicPropertyData = [];

				if (
					$deviceProperty instanceof DevicesModuleEntities\Devices\Properties\IDynamicProperty
					|| (
						$deviceProperty->getParent() !== null
						&& $deviceProperty->getParent() instanceof DevicesModuleEntities\Devices\Properties\IDynamicProperty
					)
				) {
					$devicePropertyState = $this->devicePropertiesStatesRepository->findOne($deviceProperty);

					if ($devicePropertyState instanceof States\IProperty) {
						$dynamicPropertyData = $devicePropertyState->toExchange($deviceProperty);
					}
				}

				$event->getClient()->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$event->getTopic()->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKeyType::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
						'source'      => MetadataTypes\ModuleSourceType::SOURCE_MODULE_DEVICES,
						'data'        => array_merge(
							$deviceProperty->toArray(),
							$dynamicPropertyData,
						),
					]),
				]));
			}

			$findChannelsProperties = new DevicesModuleQueries\FindChannelPropertiesQuery();

			$channelsProperties = $this->channelPropertiesRepository->getResultSet($findChannelsProperties);

			/** @var DevicesModuleEntities\Channels\Properties\Property $channelProperty */
			foreach ($channelsProperties as $channelProperty) {
				$dynamicPropertyData = [];

				if ($channelProperty instanceof DevicesModuleEntities\Channels\Properties\IDynamicProperty) {
					$channelPropertyState = $this->channelPropertiesStatesRepository->findOne($channelProperty);

					if ($channelPropertyState instanceof States\IProperty) {
						$dynamicPropertyData = $channelPropertyState->toExchange($channelProperty);
					}
				}

				$event->getClient()->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$event->getTopic()->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKeyType::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED,
						'source'      => MetadataTypes\ModuleSourceType::SOURCE_MODULE_DEVICES,
						'data'        => array_merge(
							$channelProperty->toArray(),
							$dynamicPropertyData,
						),
					]),
				]));
			}

			$findConnectorsProperties = new DevicesModuleQueries\FindConnectorPropertiesQuery();

			$connectorsProperties = $this->connectorPropertiesRepository->getResultSet($findConnectorsProperties);

			/** @var DevicesModuleEntities\Connectors\Properties\Property $connectorProperty */
			foreach ($connectorsProperties as $connectorProperty) {
				$dynamicPropertyData = [];

				if ($connectorProperty instanceof DevicesModuleEntities\Connectors\Properties\IDynamicProperty) {
					$connectorPropertyState = $this->connectorPropertiesStatesRepository->findOne($connectorProperty);

					if ($connectorPropertyState instanceof States\IProperty) {
						$dynamicPropertyData = $connectorPropertyState->toExchange($connectorProperty);
					}
				}

				$event->getClient()->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$event->getTopic()->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKeyType::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
						'source'      => MetadataTypes\ModuleSourceType::SOURCE_MODULE_DEVICES,
						'data'        => array_merge(
							$connectorProperty->toArray(),
							$dynamicPropertyData,
						),
					]),
				]));
			}
		} catch (Throwable $ex) {
			$this->logger->error('State couldn\'t be sent to subscriber', [
				'source'    => 'ws-server-plugin-controller',
				'type'      => 'subscribe',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);
		}
	}

	/**
	 * @param WsServerPluginEvents\IncomingMessage $event
	 *
	 * @return void
	 *
	 * @throws Throwable
	 */
	public function incomingMessage(
		WsServerPluginEvents\IncomingMessage $event
	): void {
		if (!$this->database->ping()) {
			$this->database->reconnect();
		}

		$this->database->clear();
	}

}
