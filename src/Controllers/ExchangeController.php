<?php declare(strict_types = 1);

/**
 * ExchangeController.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Controllers
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\MiniServer\Controllers;

use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\Exchange\Publisher as ExchangePublisher;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Loaders as MetadataLoaders;
use FastyBird\Metadata\Schemas as MetadataSchemas;
use FastyBird\MiniServer\Exceptions;
use FastyBird\MiniServer\States;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Psr\Log;
use Throwable;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExchangeController extends WebSockets\Application\Controller\Controller
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

	/** @var ExchangePublisher\IPublisher|null */
	private ?ExchangePublisher\IPublisher $publisher;

	/** @var MetadataLoaders\ISchemaLoader */
	private MetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var MetadataSchemas\IValidator */
	private MetadataSchemas\IValidator $jsonValidator;

	/** @var MetadataEntities\GlobalEntityFactory */
	private MetadataEntities\GlobalEntityFactory $entityFactory;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		DevicesModuleModels\Connectors\Properties\IPropertiesRepository $connectorPropertiesRepository,
		DevicesModuleModels\Devices\Properties\IPropertiesRepository $devicePropertiesRepository,
		DevicesModuleModels\Channels\Properties\IPropertiesRepository $channelPropertiesRepository,
		DevicesModuleModels\States\ConnectorPropertiesRepository $connectorPropertiesStatesRepository,
		DevicesModuleModels\States\DevicePropertiesRepository $devicePropertiesStatesRepository,
		DevicesModuleModels\States\ChannelPropertiesRepository $channelPropertiesStatesRepository,
		MetadataLoaders\ISchemaLoader $schemaLoader,
		MetadataSchemas\IValidator $jsonValidator,
		MetadataEntities\GlobalEntityFactory $entityFactory,
		?ExchangePublisher\IPublisher $publisher = null,
		?Log\LoggerInterface $logger = null
	) {
		parent::__construct();

		$this->connectorPropertiesRepository = $connectorPropertiesRepository;
		$this->devicePropertiesRepository = $devicePropertiesRepository;
		$this->channelPropertiesRepository = $channelPropertiesRepository;

		$this->connectorPropertiesStatesRepository = $connectorPropertiesStatesRepository;
		$this->devicePropertiesStatesRepository = $devicePropertiesStatesRepository;
		$this->channelPropertiesStatesRepository = $channelPropertiesStatesRepository;

		$this->schemaLoader = $schemaLoader;
		$this->publisher = $publisher;
		$this->jsonValidator = $jsonValidator;
		$this->entityFactory = $entityFactory;

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param WebSocketsWAMP\Entities\Clients\IClient $client
	 * @param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 *
	 * @return void
	 *
	 * @throws Utils\JsonException
	 */
	public function actionSubscribe(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic
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

				$client->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => Metadata\Types\RoutingKeyType::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
						'source'      => Metadata\Types\ModuleSourceType::SOURCE_MODULE_DEVICES,
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

				$client->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => Metadata\Types\RoutingKeyType::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED,
						'source'      => Metadata\Types\ModuleSourceType::SOURCE_MODULE_DEVICES,
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

				$client->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => Metadata\Types\RoutingKeyType::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
						'source'      => Metadata\Types\ModuleSourceType::SOURCE_MODULE_DEVICES,
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
	 * @param mixed[] $args
	 * @param WebSocketsWAMP\Entities\Clients\IClient $client
	 * @param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function actionCall(
		array $args,
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic
	): void {
		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new Exceptions\InvalidArgumentException('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_TRIGGER_ACTION_ROUTING_KEY:
				$schema = $this->schemaLoader->loadByRoutingKey(Metadata\Types\RoutingKeyType::get($args['routing_key']));
				$data = isset($args['data']) ? $this->parseData($args['data'], $schema) : null;

				if ($this->publisher !== null) {
					$this->publisher->publish(
						Metadata\Types\ModuleSourceType::get($args['source']),
						Metadata\Types\RoutingKeyType::get($args['routing_key']),
						$this->entityFactory->create(
							Utils\Json::encode($data),
							Metadata\Types\RoutingKeyType::get($args['routing_key'])
						),
					);
				}

				break;

			default:
				throw new Exceptions\InvalidArgumentException('Provided message has unsupported routing key');
		}

		$this->payload->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * @param mixed[] $data
	 * @param string $schema
	 *
	 * @return Utils\ArrayHash
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	private function parseData(
		array $data,
		string $schema
	): Utils\ArrayHash {
		try {
			return $this->jsonValidator->validate(Utils\Json::encode($data), $schema);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('Received message could not be validated', [
				'source'    => 'ws-server-plugin-controller',
				'type'      => 'parse-data',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data are not valid json format', 0, $ex);

		} catch (MetadataExceptions\InvalidDataException $ex) {
			$this->logger->debug('Received message is not valid', [
				'source'    => 'ws-server-plugin-controller',
				'type'      => 'parse-data',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data are not in valid structure', 0, $ex);

		} catch (Throwable $ex) {
			$this->logger->error('Received message is not valid', [
				'source'    => 'ws-server-plugin-controller',
				'type'      => 'parse-data',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new Exceptions\InvalidArgumentException('Provided data could not be validated', 0, $ex);
		}
	}

}
