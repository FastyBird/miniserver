<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Exceptions\DoctrineCrud as DoctrineCrudExceptions;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:VirtualConnector!
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
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
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
					DevicesTypes\ConnectionState::STOPPED->value,
					DevicesTypes\ConnectionState::ALERT->value,
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
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::STOPPED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($entity);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MANUFACTURER);

		$manufacturerProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if (
			$manufacturerProperty !== null
			&& !$manufacturerProperty instanceof DevicesEntities\Devices\Properties\Variable
		) {
			$this->propertiesManager->delete($manufacturerProperty);

			$manufacturerProperty = null;
		}

		if ($manufacturerProperty === null) {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'device' => $entity,
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::MANUFACTURER->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'unit' => null,
				'format' => null,
				'value' => Virtual\Constants::MANUFACTURER,
			]));
		}

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($entity);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MAC_ADDRESS);

		$macAddressProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if (
			$macAddressProperty !== null
			&& !$macAddressProperty instanceof DevicesEntities\Devices\Properties\Variable
		) {
			$this->propertiesManager->delete($macAddressProperty);

			$macAddressProperty = null;
		}

		if ($macAddressProperty === null) {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'device' => $entity,
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::MAC_ADDRESS->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'unit' => null,
				'format' => null,
				'value' => Helpers\Drivers::generateMacAddress(),
			]));
		}
	}

}
