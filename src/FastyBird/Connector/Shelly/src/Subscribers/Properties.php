<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\Shelly\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Core\Tools\Exceptions as ToolsExceptions;
use FastyBird\Library\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $propertiesManager,
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
	 * @throws ToolsExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\Devices\Device) {
			return;
		}

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($entity);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->propertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		if ($stateProperty !== null) {
			$this->propertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::LOST->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'device' => $entity,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::STATE->value,
				'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::STATE->value),
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::LOST->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

}
