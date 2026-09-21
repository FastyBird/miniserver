<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\Tools as ToolsExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Core\Utilities\Tools as ToolsUtilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function assert;
use function React\Async\await;

/**
 * Store device state message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Protocol\Driver $devicesDriver,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly ToolsHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDeviceState) {
			return false;
		}

		$protocolDevice = $this->devicesDriver->findDevice($message->getDevice());

		if ($protocolDevice === null || !$protocolDevice->getConnector()->equals($message->getConnector())) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'store-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if ($protocolDevice instanceof Protocol\Devices\ThirdPartyDevice) {
			$this->processThirdPartyDevice($protocolDevice, $message->getState());
		} elseif ($protocolDevice instanceof Protocol\Devices\SubDevice) {
			$this->processSubDevice($protocolDevice, $message->getState());
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
				'type' => 'store-device-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'device' => [
					'id' => $message->getDevice()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param array<Queue\Messages\CapabilityState> $state
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processSubDevice(Protocol\Devices\SubDevice $device, array $state): void
	{
		foreach ($state as $item) {
			$protocolCapabilities = $device->findCapabilities($item->getCapability(), $item->getIdentifier());

			foreach ($protocolCapabilities as $protocolCapability) {
				$protocolAttribute = $protocolCapability->findAttribute($item->getAttribute());

				if ($protocolAttribute === null) {
					continue;
				}

				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->byChannelId($protocolCapability->getId());
				$findChannelPropertiesQuery->byId($protocolAttribute->getId());

				$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
					$findChannelPropertiesQuery,
					DevicesDocuments\Channels\Properties\Dynamic::class,
				);

				if ($property === null) {
					continue;
				}

				await($this->channelPropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $item->getValue(),
					]),
					MetadataTypes\Sources\Connector::NS_PANEL,
				));

				$protocolAttribute->setActualValue(ToolsUtilities\Value::flattenValue($item->getValue()));
			}
		}
	}

	/**
	 * @param array<Queue\Messages\CapabilityState> $state
	 *
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processThirdPartyDevice(Protocol\Devices\ThirdPartyDevice $device, array $state): void
	{
		foreach ($state as $item) {
			$protocolCapabilities = $device->findCapabilities($item->getCapability(), $item->getIdentifier());

			foreach ($protocolCapabilities as $protocolCapability) {
				$protocolAttribute = $protocolCapability->findAttribute($item->getAttribute());

				if ($protocolAttribute === null) {
					continue;
				}

				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
				$findChannelPropertiesQuery->byChannelId($protocolCapability->getId());
				$findChannelPropertiesQuery->byId($protocolAttribute->getId());

				$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertiesQuery);

				if ($property === null) {
					continue;
				}

				$this->writeThirdPartyProperty(
					$protocolAttribute,
					$property,
					ToolsUtilities\Value::flattenValue($item->getValue()),
				);
			}
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ToolsExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function writeThirdPartyProperty(
		Protocol\Attributes\Attribute $protocolAttribute,
		DevicesDocuments\Channels\Properties\Property $property,
		float|int|string|bool|null $value,
	): void
	{
		if ($property instanceof DevicesDocuments\Channels\Properties\Variable) {
			$this->databaseHelper->transaction(
				function () use ($property, $value): void {
					$property = $this->channelsPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Channels\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Channels\Properties\Variable);

					$this->channelsPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $value,
						]),
					);
				},
			);

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			await($this->channelPropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $value,
				]),
				MetadataTypes\Sources\Connector::NS_PANEL,
			));

		} elseif ($property instanceof DevicesDocuments\Channels\Properties\Mapped) {
			await($this->channelPropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::EXPECTED_VALUE_FIELD => $value,
				]),
				MetadataTypes\Sources\Connector::NS_PANEL,
			));
		}

		$protocolAttribute->setActualValue($value);
	}

}
