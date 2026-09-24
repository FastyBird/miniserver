<?php declare(strict_types = 1);

/**
 * StoreBridgeInfo.php
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
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

/**
 * Store bridge info message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeInfo implements Queue\Consumer
{

	use DeviceProperty;
	use Nette\SmartObject;

	public function __construct(
		protected readonly Zigbee2Mqtt\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly ToolsHelpers\Database $databaseHelper,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreBridgeInfo) {
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

		$this->databaseHelper->transaction(
			function () use ($bridge, $message): void {
				$this->devicesManager->update(
					$bridge,
					Utils\ArrayHash::from([
						'identifier' => $message->getCoordinator()->getIeeeAddress(),
					]),
				);
			},
		);

		$this->setDeviceProperty(
			$bridge->getId(),
			$message->getCoordinator()->getType(),
			ValuesTypes\DataType::STRING,
			Zigbee2MqttTypes\DevicePropertyIdentifier::MODEL,
			DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::MODEL->value),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			Zigbee2MqttTypes\DeviceType::COORDINATOR->value,
			ValuesTypes\DataType::STRING,
			Zigbee2MqttTypes\DevicePropertyIdentifier::TYPE,
			DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::TYPE->value),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			$message->getVersion(),
			ValuesTypes\DataType::STRING,
			Zigbee2MqttTypes\DevicePropertyIdentifier::VERSION,
			DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::VERSION->value),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			$message->getCommit(),
			ValuesTypes\DataType::STRING,
			Zigbee2MqttTypes\DevicePropertyIdentifier::COMMIT,
			DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::COMMIT->value),
		);
		$this->setDeviceProperty(
			$bridge->getId(),
			$message->getCoordinator()->getIeeeAddress(),
			ValuesTypes\DataType::STRING,
			Zigbee2MqttTypes\DevicePropertyIdentifier::IEEE_ADDRESS,
			DevicesUtilities\Name::createName(Zigbee2MqttTypes\DevicePropertyIdentifier::IEEE_ADDRESS->value),
		);

		$this->logger->debug(
			'Consumed bridge info message',
			[
				'source' => Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'store-bridge-info-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
