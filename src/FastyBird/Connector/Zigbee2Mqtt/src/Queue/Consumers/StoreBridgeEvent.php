<?php declare(strict_types = 1);

/**
 * StoreBridgeEvent.php
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

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use function sprintf;

/**
 * Store bridge event message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreBridgeEvent implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreBridgeEvent) {
			return false;
		}

		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::BASE_TOPIC);
		$findDevicePropertyQuery->byValue($message->getBaseTopic());

		$baseTopicProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($baseTopicProperty === null) {
			return true;
		}

		$findDeviceQuery = new Queries\Configuration\FindBridgeDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($baseTopicProperty->getDevice());

		$bridge = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\Bridge::class,
		);

		if ($bridge === null) {
			return true;
		}

		$this->logger->info(
			sprintf('Bridge published event: %s', $message->getType()->value),
			[
				'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'bridge-event',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
				],
				'data' => [
					'type' => $message->getType()->value,
					'data' => $message->getData()->toArray(),
				],
			],
		);

		$this->logger->debug(
			'Consumed bridge event message',
			[
				'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT->value,
				'type' => 'store-bridge-event-message-consumer',
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

}
