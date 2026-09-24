<?php declare(strict_types = 1);

/**
 * StoreThirdPartyDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           04.08.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Types as NsPanelTypes;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;

/**
 * Store NS Panel third-party device message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreThirdPartyDevice implements Queue\Consumer
{

	use DeviceProperty;
	use Nette\SmartObject;

	public function __construct(
		protected readonly NsPanel\Logger $logger,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly ToolsHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws DBAL\Exception
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreThirdPartyDevice) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindThirdPartyDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\ThirdPartyDevice::class);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => Sources\Connector::NS_PANEL->value,
					'type' => 'store-third-party-device-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'gateway' => [
						'id' => $message->getGateway()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$this->setDeviceProperty(
			$device->getId(),
			$message->getGatewayIdentifier(),
			ValuesTypes\DataType::STRING,
			NsPanelTypes\DevicePropertyIdentifier::GATEWAY_IDENTIFIER,
			DevicesUtilities\Name::createName(NsPanelTypes\DevicePropertyIdentifier::GATEWAY_IDENTIFIER->value),
		);

		$this->logger->debug(
			'Consumed store device message',
			[
				'source' => Sources\Connector::NS_PANEL->value,
				'type' => 'store-third-party-device-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'gateway' => [
					'id' => $message->getGateway()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
