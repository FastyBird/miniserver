<?php declare(strict_types = 1);

/**
 * StoreSubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Formats\Tools as ToolsFormats;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use function assert;
use function in_array;

/**
 * Store NS Panel sub-device message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreSubDevice implements Queue\Consumer
{

	use DeviceProperty;
	use Nette\SmartObject;

	public function __construct(
		protected readonly NsPanel\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly ToolsHelpers\Database $databaseHelper,
		private readonly Mapping\Builder $mappingBuilder,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws ToolsExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreSubDevice) {
			return false;
		}

		$parent = $this->devicesRepository->find($message->getGateway(), Entities\Devices\Gateway::class);

		if ($parent === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'store-sub-device-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'gateway' => [
						'id' => $message->getGateway()->toString(),
					],
					'device' => [
						'identifier' => $message->getIdentifier(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new Queries\Entities\FindSubDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->forParent($parent);
		$findDeviceQuery->byIdentifier($message->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);

		if ($device === null) {
			$connector = $this->connectorsRepository->find(
				$message->getConnector(),
				Entities\Connectors\Connector::class,
			);

			if ($connector === null) {
				$this->logger->error(
					'Connector could not be loaded',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'store-sub-device-message-consumer',
						'connector' => [
							'id' => $message->getConnector()->toString(),
						],
						'gateway' => [
							'id' => $message->getGateway()->toString(),
						],
						'device' => [
							'identifier' => $message->getIdentifier(),
						],
						'data' => $message->toArray(),
					],
				);

				return true;
			}

			$device = $this->databaseHelper->transaction(
				function () use ($message, $connector, $parent): Entities\Devices\SubDevice {
					$device = $this->devicesManager->create(Utils\ArrayHash::from([
						'entity' => Entities\Devices\SubDevice::class,
						'connector' => $connector,
						'parent' => $parent,
						'identifier' => $message->getIdentifier(),
						'name' => $message->getName(),
					]));
					assert($device instanceof Entities\Devices\SubDevice);

					return $device;
				},
			);

			$this->logger->info(
				'Sub-device was created',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'store-sub-device-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'gateway' => [
						'id' => $message->getGateway()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
						'identifier' => $message->getIdentifier(),
						'protocol' => $message->getProtocol(),
					],
				],
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$message->getManufacturer(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MANUFACTURER,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MANUFACTURER->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getModel(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MODEL->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getFirmwareVersion(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getDisplayCategory()->value,
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::CATEGORY,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::CATEGORY->value),
		);
		$this->setDeviceProperty(
			$device->getId(),
			$message->getMacAddress(),
			MetadataTypes\DataType::STRING,
			Types\DevicePropertyIdentifier::MAC_ADDRESS,
			DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS->value),
		);

		foreach ($message->getCapabilities() as $capability) {
			$this->databaseHelper->transaction(function () use ($message, $device, $capability): bool {
				$identifier = Helpers\Name::convertCapabilityToChannel(
					$capability->getCapability(),
					$capability->getName(),
				);

				$findChannelQuery = new Queries\Entities\FindChannels();
				$findChannelQuery->byIdentifier($identifier);
				$findChannelQuery->forDevice($device);

				$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

				if ($channel === null) {
					$capabilityMetadata = $this->mappingBuilder->getCapabilitiesMapping()->findByCapabilityName(
						$capability->getCapability(),
						$capability->getName(),
					);

					if ($capabilityMetadata === null) {
						return false;
					}

					$channel = $this->channelsManager->create(Utils\ArrayHash::from([
						'entity' => $capabilityMetadata->getClass(),
						'device' => $device,
						'identifier' => $identifier,
					]));

					$this->logger->debug(
						'Device channel was created',
						[
							'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
							'type' => 'store-sub-device-message-consumer',
							'connector' => [
								'id' => $message->getConnector()->toString(),
							],
							'gateway' => [
								'id' => $message->getGateway()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
						],
					);
				}

				return true;
			});
		}

		foreach ($message->getState() as $state) {
			$identifier = Helpers\Name::convertCapabilityToChannel($state->getCapability(), $state->getIdentifier());

			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->byIdentifier($identifier);
			$findChannelQuery->forDevice($device);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

			if ($channel === null) {
				continue;
			}

			$capabilityMetadata = $this->mappingBuilder->getCapabilitiesMapping()->findByCapabilityName(
				$state->getCapability(),
				$state->getIdentifier(),
			);

			if ($capabilityMetadata === null) {
				continue;
			}

			$attributeMetadata = $capabilityMetadata->findAttribute($state->getAttribute());

			if ($attributeMetadata === null) {
				continue;
			}

			$this->databaseHelper->transaction(
				function () use ($message, $device, $channel, $state, $capabilityMetadata, $attributeMetadata): bool {
					$format = null;

					if (
						$attributeMetadata->getMinValue() !== null
						|| $attributeMetadata->getMaxValue() !== null
					) {
						$format = new ToolsFormats\NumberRange([
							$attributeMetadata->getMinValue(),
							$attributeMetadata->getMaxValue(),
						]);
					}

					if (
						$attributeMetadata->getDataType() === MetadataTypes\DataType::ENUM
						|| $attributeMetadata->getDataType() === MetadataTypes\DataType::SWITCH
						|| $attributeMetadata->getDataType() === MetadataTypes\DataType::BUTTON
						|| $attributeMetadata->getDataType() === MetadataTypes\DataType::COVER
					) {
						if ($attributeMetadata->getMappedValues() !== []) {
							$format = $attributeMetadata->getMappedValues();
						} elseif ($attributeMetadata->getValidValues() !== []) {
							$format = $attributeMetadata->getValidValues();
						}
					}

					$findPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
					$findPropertyQuery->byIdentifier(Helpers\Name::convertAttributeToProperty($state->getAttribute()));
					$findPropertyQuery->forChannel($channel);

					$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

					if ($property === null) {
						$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
							'channel' => $channel,
							'identifier' => Helpers\Name::convertAttributeToProperty($state->getAttribute()),
							'dataType' => $attributeMetadata->getDataType(),
							'format' => $format,
							'invalid' => $attributeMetadata->getInvalidValue(),
							'settable' => in_array(
								$capabilityMetadata->getPermission(),
								[Types\Permission::READ_WRITE, Types\Permission::WRITE],
								true,
							),
							'queryable' => in_array(
								$capabilityMetadata->getPermission(),
								[Types\Permission::READ_WRITE, Types\Permission::READ],
								true,
							),
						]));

						$this->logger->debug(
							'Device channel property was created',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'store-sub-device-message-consumer',
								'connector' => [
									'id' => $message->getConnector()->toString(),
								],
								'gateway' => [
									'id' => $message->getGateway()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
								'channel' => [
									'id' => $channel->getId()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
								],
							],
						);
					} else {
						$property = $this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
							'dataType' => $attributeMetadata->getDataType(),
							'format' => $format,
							'invalid' => $attributeMetadata->getInvalidValue(),
							'settable' => in_array(
								$capabilityMetadata->getPermission(),
								[Types\Permission::READ_WRITE, Types\Permission::WRITE],
								true,
							),
							'queryable' => in_array(
								$capabilityMetadata->getPermission(),
								[Types\Permission::READ_WRITE, Types\Permission::READ],
								true,
							),
						]));

						$this->logger->debug(
							'Device channel property was updated',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'store-sub-device-message-consumer',
								'connector' => [
									'id' => $message->getConnector()->toString(),
								],
								'gateway' => [
									'id' => $message->getGateway()->toString(),
								],
								'device' => [
									'id' => $device->getId()->toString(),
								],
								'channel' => [
									'id' => $channel->getId()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
								],
							],
						);
					}

					return true;
				},
			);
		}

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
				'type' => 'store-sub-device-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'gateway' => [
					'id' => $message->getGateway()->toString(),
				],
				'device' => [
					'identifier' => $message->getIdentifier(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
