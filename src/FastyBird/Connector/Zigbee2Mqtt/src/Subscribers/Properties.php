<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (
			$entity instanceof Entities\Devices\Bridge
			|| $entity instanceof Entities\Devices\SubDevice
		) {
			$this->processDeviceProperties($entity);
		}
	}

	/**
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 */
	private function processDeviceProperties(Entities\Devices\Device $device): void
	{
		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->devicesPropertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		if ($stateProperty !== null) {
			$this->devicesPropertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'device' => $device,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::STATE->value,
				'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::STATE->value),
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

}
