<?php declare(strict_types = 1);

/**
 * ExchangeController.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           01.05.20
 */

namespace FastyBird\MiniServer\Controllers;

use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\Exchange\Publisher as ExchangePublisher;
use FastyBird\Metadata;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Helpers as MetadataHelpers;
use FastyBird\Metadata\Loaders as MetadataLoaders;
use FastyBird\Metadata\Schemas as MetadataSchemas;
use FastyBird\MiniServer\Exceptions;
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

	/** @var DevicesModuleModels\Devices\Properties\IPropertyRepository */
	private DevicesModuleModels\Devices\Properties\IPropertyRepository $devicePropertiesRepository;

	/** @var DevicesModuleModels\Channels\Properties\IPropertyRepository */
	private DevicesModuleModels\Channels\Properties\IPropertyRepository $channelPropertiesRepository;

	/** @var DevicesModuleModels\States\IDevicePropertyRepository */
	private DevicesModuleModels\States\IDevicePropertyRepository $devicePropertiesStatesRepository;

	/** @var DevicesModuleModels\States\IChannelPropertyRepository */
	private DevicesModuleModels\States\IChannelPropertyRepository $channelPropertiesStatesRepository;

	/** @var ExchangePublisher\IPublisher|null */
	private ?ExchangePublisher\IPublisher $publisher;

	/** @var MetadataLoaders\ISchemaLoader */
	private MetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var MetadataSchemas\IValidator */
	private MetadataSchemas\IValidator $jsonValidator;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		DevicesModuleModels\Devices\Properties\IPropertyRepository $devicePropertiesRepository,
		DevicesModuleModels\Channels\Properties\IPropertyRepository $channelPropertiesRepository,
		DevicesModuleModels\States\IDevicePropertyRepository $devicePropertiesStatesRepository,
		DevicesModuleModels\States\IChannelPropertyRepository $channelPropertiesStatesRepository,
		MetadataLoaders\ISchemaLoader $schemaLoader,
		MetadataSchemas\IValidator $jsonValidator,
		?ExchangePublisher\IPublisher $publisher = null,
		?Log\LoggerInterface $logger = null
	) {
		parent::__construct();

		$this->devicePropertiesRepository = $devicePropertiesRepository;
		$this->channelPropertiesRepository = $channelPropertiesRepository;
		$this->devicePropertiesStatesRepository = $devicePropertiesStatesRepository;
		$this->channelPropertiesStatesRepository = $channelPropertiesStatesRepository;

		$this->schemaLoader = $schemaLoader;
		$this->publisher = $publisher;
		$this->jsonValidator = $jsonValidator;
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param WebSocketsWAMP\Entities\Clients\IClient $client
	 * @param WebSocketsWAMP\Entities\Topics\ITopic $topic
	 *
	 * @return void
	 *
	 * @throws Utils\JsonException
	 */
	public function actionSubscribe(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic
	): void {
		$findDevicesProperties = new DevicesModuleQueries\FindDevicePropertiesQuery();

		$devicesProperties = $this->devicePropertiesRepository->getResultSet($findDevicesProperties);

		/** @var DevicesModuleEntities\Devices\Properties\Property $deviceProperty */
		foreach ($devicesProperties as $deviceProperty) {
			$dynamicPropertyData = [];

			if ($deviceProperty instanceof DevicesModuleEntities\Devices\Properties\IDynamicProperty) {
				$devicePropertyState = $this->devicePropertiesStatesRepository->findOne($deviceProperty);

				if ($devicePropertyState !== null) {
					$dynamicPropertyData = [
						'actual_value'   => $deviceProperty->getDataType() !== null ? MetadataHelpers\ValueHelper::normalizeValue($deviceProperty->getDataType(), $devicePropertyState->getActualValue(), $deviceProperty->getFormat()) : $devicePropertyState->getActualValue(),
						'expected_value' => $deviceProperty->getDataType() !== null ? MetadataHelpers\ValueHelper::normalizeValue($deviceProperty->getDataType(), $devicePropertyState->getExpectedValue(), $deviceProperty->getFormat()) : $devicePropertyState->getExpectedValue(),
						'pending'        => $devicePropertyState->isPending(),
					];
				}
			}

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Metadata\Types\RoutingKeyType::ROUTE_DEVICES_PROPERTY_ENTITY_REPORTED,
					'origin'      => Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES,
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

				if ($channelPropertyState !== null) {
					$dynamicPropertyData = [
						'actual_value'   => $channelProperty->getDataType() !== null ? MetadataHelpers\ValueHelper::normalizeValue($channelProperty->getDataType(), $channelPropertyState->getActualValue(), $channelProperty->getFormat()) : $channelPropertyState->getActualValue(),
						'expected_value' => $channelProperty->getDataType() !== null ? MetadataHelpers\ValueHelper::normalizeValue($channelProperty->getDataType(), $channelPropertyState->getExpectedValue(), $channelProperty->getFormat()) : $channelPropertyState->getExpectedValue(),
						'pending'        => $channelPropertyState->isPending(),
					];
				}
			}

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => Metadata\Types\RoutingKeyType::ROUTE_CHANNELS_PROPERTY_ENTITY_REPORTED,
					'origin'      => Metadata\Types\ModuleOriginType::ORIGIN_MODULE_DEVICES,
					'data'        => array_merge(
						$channelProperty->toArray(),
						$dynamicPropertyData,
					),
				]),
			]));
		}
	}

	/**
	 * @param mixed[] $args
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function actionCall(
		array $args
	): void {
		if (!array_key_exists('routing_key', $args) || !array_key_exists('origin', $args)) {
			throw new Exceptions\InvalidArgumentException('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_CONFIGURATION_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_CONFIGURATION_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_TRIGGER_ACTION_ROUTING_KEY:
				$schema = $this->schemaLoader->loadByRoutingKey(Metadata\Types\RoutingKeyType::get($args['routing_key']));
				$data = isset($args['data']) ? $this->parseData($args['data'], $schema) : null;

				if ($this->publisher !== null) {
					$this->publisher->publish(
						Metadata\Types\ModuleOriginType::get($args['origin']),
						Metadata\Types\RoutingKeyType::get($args['routing_key']),
						$data,
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
