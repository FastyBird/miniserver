<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           05.09.22
 */

namespace FastyBird\Connector\Tuya\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions as TuyaExceptions;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Types as TuyaTypes;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:TuyaConnector!
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
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws TuyaExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
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
		$findDevicePropertyQuery->byIdentifier(TuyaTypes\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->propertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		if ($stateProperty !== null) {
			$this->propertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => ValuesTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::LOST->value,
					DevicesTypes\ConnectionState::SLEEPING->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'device' => $entity,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => TuyaTypes\DevicePropertyIdentifier::STATE->value,
				'name' => DevicesUtilities\Name::createName(TuyaTypes\DevicePropertyIdentifier::STATE->value),
				'dataType' => ValuesTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::LOST->value,
					DevicesTypes\ConnectionState::SLEEPING->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

}
