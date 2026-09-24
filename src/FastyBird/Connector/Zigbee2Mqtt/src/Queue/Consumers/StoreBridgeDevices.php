<?php declare(strict_types = 1);

/**
 * StoreBridgeDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use Doctrine\DBAL;
use Exception;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types as Zigbee2MqttTypes;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function array_merge;
use function assert;
use function implode;
use function preg_match;

/**
 * Store bridge devices message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeDevices implements Queue\Consumer
{

	use Nette\SmartObject;
	use DeviceProperty;
	use ChannelProperty;

	public function __construct(
		protected readonly Zigbee2Mqtt\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		protected readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		protected readonly ToolsHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreBridgeDevices) {
			return false;
		}

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::BASE_TOPIC);
		$findDevicePropertyQuery->byValue($message->getBaseTopic());

		$baseTopicProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		if ($baseTopicProperty === null) {
			return true;
		}

		$bridge = $this->devicesRepository->find(
			$baseTopicProperty->getDevice()->getId(),
			Entities\Devices\Bridge::class,
		);

		if ($bridge === null) {
			return true;
		}

		foreach ($message->getDevices() as $deviceDescription) {
			if ($bridge->getIdentifier() === $deviceDescription->getIeeeAddress()) {
				$device = $bridge;

			} else {
				$findDeviceQuery = new Queries\Entities\FindSubDevices();
				$findDeviceQuery->byConnectorId($message->getConnector());
				$findDeviceQuery->forParent($bridge);
				$findDeviceQuery->byIdentifier($deviceDescription->getIeeeAddress());

				$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);
			}

			if ($device === null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byConnectorId($message->getConnector());
				$findDeviceQuery->byIdentifier($deviceDescription->getIeeeAddress());

				if (
					$this->devicesRepository->getResultSet(
						$findDeviceQuery,
						Entities\Devices\Device::class,
					)->count() !== 0
				) {
					$this->logger->error(
						'There is already registered device with same ieee address',
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'identifier' => $deviceDescription->getIeeeAddress(),
							],
							'data' => $deviceDescription->toArray(),
						],
					);

					continue;
				}

				$connector = $this->connectorsRepository->find(
					$message->getConnector(),
					Entities\Connectors\Connector::class,
				);

				if ($connector === null) {
					$this->logger->error(
						'Connector could not be loaded',
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'identifier' => $deviceDescription->getIeeeAddress(),
							],
							'data' => $deviceDescription->toArray(),
						],
					);

					return true;
				}

				$device = $this->databaseHelper->transaction(
					function () use ($connector, $bridge, $deviceDescription): Entities\Devices\SubDevice {
						$device = $this->devicesManager->create(Utils\ArrayHash::from([
							'entity' => Entities\Devices\SubDevice::class,
							'connector' => $connector,
							'parent' => $bridge,
							'identifier' => $deviceDescription->getIeeeAddress(),
							'name' => $deviceDescription->getDefinition()?->getDescription(),
							'comment' => $deviceDescription->getDescription(),
						]));
						assert($device instanceof Entities\Devices\SubDevice);

						return $device;
					},
				);

				$this->logger->info(
					'Sub-device was created',
					[
						'source' => Sources\Connector::ZIGBEE2MQTT->value,
						'type' => 'store-bridge-devices-message-consumer',
						'connector' => [
							'id' => $device->getConnector()->getId()->toString(),
						],
						'bridge' => [
							'id' => $device->getBridge()->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
							'identifier' => $deviceDescription->getIeeeAddress(),
						],
					],
				);
			}

			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getFriendlyName(),
				ValuesTypes\DataType::STRING,
				Zigbee2MqttTypes\DevicePropertyIdentifier::FRIENDLY_NAME,
				DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::FRIENDLY_NAME->value),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getIeeeAddress(),
				ValuesTypes\DataType::STRING,
				Zigbee2MqttTypes\DevicePropertyIdentifier::IEEE_ADDRESS,
				DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::IEEE_ADDRESS->value),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->isDisabled(),
				ValuesTypes\DataType::BOOLEAN,
				Zigbee2MqttTypes\DevicePropertyIdentifier::DISABLED,
				DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::DISABLED->value),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->isSupported(),
				ValuesTypes\DataType::BOOLEAN,
				Zigbee2MqttTypes\DevicePropertyIdentifier::SUPPORTED,
				DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::SUPPORTED->value),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getType()->value,
				ValuesTypes\DataType::STRING,
				Zigbee2MqttTypes\DevicePropertyIdentifier::TYPE,
				DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::TYPE->value),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getDefinition()?->getModel(),
				ValuesTypes\DataType::STRING,
				Zigbee2MqttTypes\DevicePropertyIdentifier::MODEL,
				DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::MODEL->value),
			);
			$this->setDeviceProperty(
				$device->getId(),
				$deviceDescription->getDefinition()?->getVendor(),
				ValuesTypes\DataType::STRING,
				Zigbee2MqttTypes\DevicePropertyIdentifier::MANUFACTURER,
				DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::MANUFACTURER->value),
			);

			if ($device instanceof Entities\Devices\SubDevice) {
				$this->processExposes($device, $deviceDescription->getDefinition()?->getExposes() ?? []);
			}
		}

		$this->logger->debug(
			'Consumed bridge devices list message',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'store-bridge-devices-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param array<Queue\Messages\Exposes\Type> $exposes
	 * @param array<string> $identifiers
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processExposes(
		Entities\Devices\SubDevice $device,
		array $exposes,
		array $identifiers = [],
	): void
	{
		foreach ($exposes as $expose) {
			if ($expose instanceof Queue\Messages\Exposes\ListType) {
				$this->logger->warning(
					'List type expose is not supported',
					[
						'source' => Sources\Connector::ZIGBEE2MQTT->value,
						'type' => 'store-bridge-devices-message-consumer',
						'connector' => [
							'id' => $device->getConnector()->getId()->toString(),
						],
						'bridge' => [
							'id' => $device->getBridge()->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
							'identifier' => $device->getIdentifier(),
						],
						'data' => $expose->toArray(),
					],
				);

				continue;
			}

			if (
				$expose instanceof Queue\Messages\Exposes\ClimateType
				|| $expose instanceof Queue\Messages\Exposes\CoverType
				|| $expose instanceof Queue\Messages\Exposes\FanType
				|| $expose instanceof Queue\Messages\Exposes\LightType
				|| $expose instanceof Queue\Messages\Exposes\LockType
				|| $expose instanceof Queue\Messages\Exposes\SwitchType
			) {
				$this->processExposes(
					$device,
					$expose->getFeatures(),
					array_merge($identifiers, [$expose->getType()->value]),
				);

			} elseif (
				$expose instanceof Queue\Messages\Exposes\BinaryType
				|| $expose instanceof Queue\Messages\Exposes\EnumType
				|| $expose instanceof Queue\Messages\Exposes\NumericType
				|| $expose instanceof Queue\Messages\Exposes\TextType
				|| $expose instanceof Queue\Messages\Exposes\CompositeType
			) {
				$channelIdentifier = implode(
					'_',
					array_merge($identifiers, [$expose->getType()->value, $expose->getProperty()]),
				);

				if (
					preg_match(Zigbee2Mqtt\Constants::CHANNEL_IDENTIFIER_REGEX, $channelIdentifier) !== 1
					&& preg_match(Zigbee2Mqtt\Constants::CHANNEL_SPECIAL_IDENTIFIER_REGEX, $channelIdentifier) !== 1
				) {
					$this->logger->error(
						'Channel identifier could not be generated',
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->getId()->toString(),
							],
							'bridge' => [
								'id' => $device->getBridge()->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
								'identifier' => $device->getIdentifier(),
							],
							'data' => $expose->toArray(),
						],
					);

					continue;
				}

				$channel = $this->createChannel(
					$channelIdentifier,
					$expose->getLabel() ?? $expose->getName(),
					$device,
				);

				if ($expose instanceof Queue\Messages\Exposes\CompositeType) {
					foreach ($expose->getFeatures() as $feature) {
						$this->setChannelProperty(
							DevicesEntities\Channels\Properties\Dynamic::class,
							$channel->getId(),
							null,
							$feature->getDataType(),
							$feature->getProperty(),
							$feature->getLabel() ?? $feature->getName(),
							$feature->getFormat(),
							$feature->getUnit(),
							null,
							$feature instanceof Queue\Messages\Exposes\NumericType ? $feature->getValueStep() : null,
							$feature->isSettable(),
							$feature->isQueryable(),
						);
					}
				} else {
					$this->setChannelProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						$channel->getId(),
						null,
						$expose->getDataType(),
						$expose->getProperty(),
						$expose->getLabel() ?? $expose->getName(),
						$expose->getFormat(),
						$expose->getUnit(),
						null,
						$expose instanceof Queue\Messages\Exposes\NumericType ? $expose->getValueStep() : null,
						$expose->isSettable(),
						$expose->isQueryable(),
					);
				}
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 */
	private function createChannel(
		string $identifier,
		string|null $name,
		Entities\Devices\SubDevice $device,
	): DevicesEntities\Channels\Channel
	{
		return $this->databaseHelper->transaction(
			function () use ($identifier, $name, $device): DevicesEntities\Channels\Channel {
				$findChannelQuery = new Queries\Entities\FindChannels();
				$findChannelQuery->byIdentifier($identifier);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

				if ($channel === null) {
					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Channels\Channel::class,
						'device' => $device,
						'identifier' => $identifier,
						'name' => $name,
					]));

					$this->logger->debug(
						'Device channel was created',
						[
							'source' => Sources\Connector::ZIGBEE2MQTT->value,
							'type' => 'store-bridge-devices-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->getId()->toString(),
							],
							'bridge' => [
								'id' => $device->getBridge()->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
								'identifier' => $device->getIdentifier(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
						],
					);
				}

				return $channel;
			},
		);
	}

}
