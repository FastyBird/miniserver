<?php declare(strict_types = 1);

/**
 * Driver.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           04.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Logging;
use FastyBird\Core\Values\Formats;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use TypeError;
use ValueError;
use function array_diff;
use function array_map;
use function assert;
use function sprintf;

/**
 * NS panel device driver loader
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Loader
{

	/**
	 * @param array<Protocol\Devices\DeviceFactory> $devicesFactories
	 * @param array<Protocol\Capabilities\CapabilityFactory> $capabilitiesFactories
	 * @param array<Protocol\Attributes\AttributeFactory> $attributesFactories
	 * @param array<Protocol\Configurations\ConfigurationFactory> $configurationsFactories
	 */
	public function __construct(
		private Protocol\Driver $devicesDriver,
		private array $devicesFactories,
		private array $capabilitiesFactories,
		private array $attributesFactories,
		private array $configurationsFactories,
		private Helpers\MessageBuilder $messageBuilder,
		private Helpers\Channels\Channel $channelHelper,
		private Mapping\Builder $mappingBuilder,
		private Queue\Queue $queue,
		private NsPanel\Logger $logger,
		private DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\MalformedInput
	 * @throws ApplicationExceptions\Logic
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function load(Documents\Connectors\Connector $connector): void
	{
		$this->devicesDriver->reset();

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$gateways = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Gateway::class,
		);

		foreach ($gateways as $gateway) {
			assert($gateway instanceof Documents\Devices\Gateway);

			$findDevicesQuery = new Queries\Configuration\FindDevices();
			$findDevicesQuery->forParent($gateway);

			$devices = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\Device::class,
			);

			foreach ($devices as $device) {
				if (
					!$device instanceof Documents\Devices\SubDevice
					&& !$device instanceof Documents\Devices\ThirdPartyDevice
				) {
					continue;
				}

				$protocolDevice = $this->buildDevice($connector, $gateway, $device);

				$findChannelsQuery = new Queries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsConfigurationRepository->findAllBy(
					$findChannelsQuery,
					Documents\Channels\Channel::class,
				);

				$createdCapabilities = [];

				foreach ($channels as $channel) {
					$protocolCapability = $this->buildCapability($channel, $protocolDevice);

					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
					);

					$createdAttributes = [];

					foreach ($properties as $property) {
						$attributeType = Helpers\Name::convertPropertyToAttribute($property->getIdentifier(), false);

						if ($attributeType !== null) {
							$protocolAttribute = $this->buildAttribute($property, $protocolCapability);

							$protocolCapability->addAttribute($protocolAttribute);

							$createdAttributes[] = $protocolAttribute->getType();

							continue;
						}

						$configurationType = Helpers\Name::convertPropertyToConfiguration(
							$property->getIdentifier(),
							false,
						);

						if ($configurationType !== null) {
							$protocolConfiguration = $this->buildConfiguration($property, $protocolCapability);

							$protocolCapability->addConfiguration($protocolConfiguration);

							continue;
						}

						$this->logger->warning(
							'Channel property is not supported',
							[
								'source' => Sources\Connector::NS_PANEL->value,
								'type' => 'protocol-loader',
								'connector' => [
									'id' => $connector->getId()->toString(),
								],
								'device' => [
									'id' => $protocolDevice->getId()->toString(),
								],
								'channel' => [
									'id' => $protocolCapability->getId()->toString(),
								],
								'property' => [
									'id' => $property->getId()->toString(),
								],
							],
						);
					}

					if (
						$protocolDevice instanceof Protocol\Devices\ThirdPartyDevice
						&& array_diff(
							array_map(
								static fn (Types\Attribute $type): string => $type->value,
								$protocolCapability->getAllowedAttributesTypes(),
							),
							array_map(static fn (Types\Attribute $type): string => $type->value, $createdAttributes),
						) !== []
					) {
						$protocolDevice->setCorrupted(true);
					}

					$protocolDevice->addCapability($protocolCapability);

					$createdCapabilities[] = $protocolCapability->getType();
				}

				if (
					$protocolDevice instanceof Protocol\Devices\ThirdPartyDevice
					&& array_diff(
						array_map(
							static fn (Types\Capability $type): string => $type->value,
							$protocolDevice->getRequiredCapabilities(),
						),
						array_map(static fn (Types\Capability $type): string => $type->value, $createdCapabilities),
					) !== []
				) {
					$protocolDevice->setCorrupted(true);
				}

				$this->devicesDriver->addDevice($protocolDevice);
			}
		}

		foreach ($this->devicesDriver->getDevices() as $protocolDevice) {
			foreach ($protocolDevice->getCapabilities() as $protocolCapability) {
				foreach ($protocolCapability->getAttributes() as $protocolAttribute) {
					$property = $this->channelsPropertiesConfigurationRepository->find($protocolAttribute->getId());

					if ($property !== null) {
						$protocolAttribute->setActualValue(
							Utilities\Value::flattenValue($property->getDefault()),
						);
					}

					if ($property instanceof DevicesDocuments\Channels\Properties\Variable) {
						$protocolAttribute->setActualValue(
							Utilities\Value::flattenValue($property->getValue()),
						);
						$protocolAttribute->setValid(true);
					} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
						try {
							$state = $this->channelPropertiesStatesManager->read(
								$property,
								Sources\Connector::NS_PANEL,
							);

							if ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceState::class,
										[
											'connector' => $protocolDevice->getConnector(),
											'gateway' => $protocolDevice->getParent(),
											'device' => $protocolDevice->getId(),
											'state' => [
												[
													'capability' => $protocolCapability->getType()->value,
													'attribute' => $protocolAttribute->getType()->value,
													'value' => Utilities\Value::flattenValue(
														$state->getGet()->getExpectedValue() ?? $state->getGet()->getActualValue(),
													),
													'identifier' => $protocolCapability->getName(),
												],
											],
										],
									),
								);

								$protocolAttribute->setActualValue(
									Utilities\Value::flattenValue($state->getGet()->getActualValue()),
								);
								$protocolAttribute->setExpectedValue(
									Utilities\Value::flattenValue($state->getGet()->getExpectedValue()),
								);
								$protocolAttribute->setValid($state->isValid());
							} else {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceState::class,
										[
											'connector' => $protocolDevice->getConnector(),
											'gateway' => $protocolDevice->getParent(),
											'device' => $protocolDevice->getId(),
											'state' => [
												[
													'capability' => $protocolCapability->getType()->value,
													'attribute' => $protocolAttribute->getType()->value,
													'value' => Utilities\Value::flattenValue(
														$property->getDefault(),
													),
													'identifier' => $protocolCapability->getName(),
												],
											],
										],
									),
								);

								$protocolAttribute->setActualValue(
									Utilities\Value::flattenValue($property->getDefault()),
								);
								$protocolAttribute->setValid(true);
							}
						} catch (Exceptions\InvalidState $ex) {
							$this->logger->warning(
								'State value could not be set to attribute',
								[
									'source' => Sources\Connector::NS_PANEL->value,
									'type' => 'protocol-loader',
									'exception' => Logging\Logger::buildException($ex),
									'connector' => [
										'id' => $connector->getId()->toString(),
									],
									'device' => [
										'id' => $protocolDevice->getId()->toString(),
									],
									'channel' => [
										'id' => $protocolCapability->getId()->toString(),
									],
									'property' => [
										'id' => $protocolAttribute->getId()->toString(),
									],
								],
							);
						} catch (DevicesExceptions\NotImplemented) {
							// Ignore error
						}
					} elseif ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
						$findParentPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
						$findParentPropertyQuery->byId($property->getParent());

						$parent = $this->channelsPropertiesConfigurationRepository->findOneBy($findParentPropertyQuery);

						if ($parent instanceof DevicesDocuments\Channels\Properties\Dynamic) {
							try {
								$state = $this->channelPropertiesStatesManager->read(
									$property,
									Sources\Connector::NS_PANEL,
								);

								if ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
									$protocolAttribute->setActualValue(
										Utilities\Value::flattenValue($state->getGet()->getActualValue()),
									);
									$protocolAttribute->setExpectedValue(
										Utilities\Value::flattenValue($state->getGet()->getExpectedValue()),
									);
									$protocolAttribute->setValid($state->isValid());
								}
							} catch (Exceptions\InvalidState $ex) {
								$this->logger->warning(
									'State value could not be set to attribute',
									[
										'source' => Sources\Connector::NS_PANEL->value,
										'type' => 'protocol-loader',
										'exception' => Logging\Logger::buildException($ex),
										'connector' => [
											'id' => $connector->getId()->toString(),
										],
										'device' => [
											'id' => $protocolDevice->getId()->toString(),
										],
										'channel' => [
											'id' => $protocolCapability->getId()->toString(),
										],
										'property' => [
											'id' => $protocolAttribute->getId()->toString(),
										],
									],
								);
							} catch (DevicesExceptions\NotImplemented) {
								// Ignore error
							}
						} elseif ($parent instanceof DevicesDocuments\Channels\Properties\Variable) {
							$protocolAttribute->setActualValue(
								Utilities\Value::flattenValue($parent->getValue()),
							);
							$protocolAttribute->setValid(true);
						}
					}
				}

				$protocolCapability->recalculateAttributes();

				foreach ($protocolCapability->getAttributes() as $protocolAttribute) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceState::class,
							[
								'connector' => $protocolDevice->getConnector(),
								'gateway' => $protocolDevice->getParent(),
								'device' => $protocolDevice->getId(),
								'state' => [
									[
										'capability' => $protocolCapability->getType()->value,
										'attribute' => $protocolAttribute->getType()->value,
										'value' => $protocolAttribute->getValue(),
										'identifier' => $protocolCapability->getName(),
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildDevice(
		Documents\Connectors\Connector $connector,
		Documents\Devices\Gateway $gateway,
		Documents\Devices\Device $device,
	): Protocol\Devices\Device
	{
		$protocolDevice = null;

		$metadata = $this->mappingBuilder->getCategoriesMapping();

		foreach ($this->devicesFactories as $deviceFactory) {
			if ($device::getType() === $deviceFactory->getEntityClass()::getType()) {
				$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
				$findPropertyQuery->forDevice($device);
				$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::CATEGORY);

				$categoryProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
					$findPropertyQuery,
					DevicesDocuments\Devices\Properties\Variable::class,
				);

				$category = Types\Category::from(
					Utilities\Value::toString($categoryProperty?->getValue()) ?? Types\Category::UNKNOWN->value,
				);

				$categoryMetadata = $metadata->findByCategory($category);

				if ($categoryMetadata === null) {
					continue;
				}

				$protocolDevice = $deviceFactory->create(
					$connector,
					$gateway,
					$device,
					$categoryMetadata,
				);

				break;
			}
		}

		if ($protocolDevice === null) {
			throw new Exceptions\InvalidState('Device could not be created');
		}

		return $protocolDevice;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildCapability(
		Documents\Channels\Channel $channel,
		Protocol\Devices\Device $protocolDevice,
	): Protocol\Capabilities\Capability
	{
		$metadata = $this->mappingBuilder->getCapabilitiesMapping();

		$capabilityMetadata = $metadata->findByCapabilityName(
			$this->channelHelper->getCapability($channel),
			$this->channelHelper->getName($channel),
		);

		if ($capabilityMetadata === null) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$this->channelHelper->getCapability($channel)->value,
			));
		}

		foreach ($this->capabilitiesFactories as $capabilityFactory) {
			if ($channel::getType() === $capabilityFactory->getEntityClass()::getType()) {
				return $capabilityFactory->create(
					$channel,
					$protocolDevice,
					$capabilityMetadata,
				);
			}
		}

		throw new Exceptions\InvalidState('Capability could not be created');
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildAttribute(
		DevicesDocuments\Channels\Properties\Property $property,
		Protocol\Capabilities\Capability $protocolCapability,
	): Protocol\Attributes\Attribute
	{
		$metadata = $this->mappingBuilder->getCapabilitiesMapping();

		$capabilityMetadata = $metadata->findByCapabilityName(
			$protocolCapability->getType(),
			$protocolCapability->getName(),
		);

		if ($capabilityMetadata === null) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$protocolCapability->getType()->value,
			));
		}

		$attributeMetadata = $capabilityMetadata->findAttribute(
			Helpers\Name::convertPropertyToAttribute($property->getIdentifier()),
		);

		if ($attributeMetadata === null) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for attribute: %s was not found',
				$property->getIdentifier(),
			));
		}

		$format = $property->getFormat();

		foreach ($this->attributesFactories as $attributeFactory) {
			if ($attributeMetadata->getAttribute() === $attributeFactory->getType()) {
				return $attributeFactory->create(
					$property->getId(),
					$attributeMetadata->getAttribute(),
					$property->getDataType(),
					$protocolCapability,
					$format instanceof Formats\StringEnum ? $format->toArray() : null,
					null,
					$format instanceof Formats\NumberRange ? $format->getMin() : null,
					$format instanceof Formats\NumberRange ? $format->getMax() : null,
					$property->getStep(),
					Utilities\Value::flattenValue($property->getDefault()),
					$property->getUnit(),
				);
			}
		}

		throw new Exceptions\InvalidState('Attribute could not be created');
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function buildConfiguration(
		DevicesDocuments\Channels\Properties\Property $property,
		Protocol\Capabilities\Capability $protocolCapability,
	): Protocol\Configurations\Configuration
	{
		$metadata = $this->mappingBuilder->getCapabilitiesMapping();

		$capabilityMetadata = $metadata->findByCapabilityName(
			$protocolCapability->getType(),
			$protocolCapability->getName(),
		);

		if ($capabilityMetadata === null) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$protocolCapability->getType()->value,
			));
		}

		$configurationMetadata = $capabilityMetadata->findConfiguration($property->getIdentifier());

		if ($configurationMetadata === null) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for configuration: %s was not found',
				$property->getIdentifier(),
			));
		}

		$format = $property->getFormat();

		foreach ($this->configurationsFactories as $configurationFactory) {
			if ($configurationMetadata->getConfiguration() === $configurationFactory->getType()) {
				return $configurationFactory->create(
					$property->getId(),
					$configurationMetadata->getConfiguration(),
					$property->getDataType(),
					$protocolCapability,
					Utilities\Value::flattenValue($property->getDefault()),
					$format instanceof Formats\StringEnum ? $format->toArray() : null,
					null,
					$format instanceof Formats\NumberRange ? $format->getMin() : null,
					$format instanceof Formats\NumberRange ? $format->getMax() : null,
					$property->getStep(),
					$property->getUnit(),
				);
			}
		}

		throw new Exceptions\InvalidState('Configuration could not be created');
	}

}
