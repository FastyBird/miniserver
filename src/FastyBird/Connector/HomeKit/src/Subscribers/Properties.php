<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           12.02.23
 */

namespace FastyBird\Connector\HomeKit\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions as HomeKitExceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function assert;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $connectorsPropertiesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\Connectors\Connector) {
			$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($entity);
			$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::MAC_ADDRESS);

			$macAddressProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($macAddressProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'connector' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::MAC_ADDRESS->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'unit' => null,
					'format' => null,
					'value' => Helpers\Protocol::generateMacAddress(),
				]));
			}

			$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($entity);
			$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::SETUP_ID);

			$setupIdProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($setupIdProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'connector' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::SETUP_ID->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'unit' => null,
					'format' => null,
					'value' => Helpers\Protocol::generateSetupId(),
				]));
			}

			$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($entity);
			$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::PIN_CODE);

			$pinCodeProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($pinCodeProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'connector' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::PIN_CODE->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'unit' => null,
					'format' => null,
					'value' => Helpers\Protocol::generatePinCode(),
				]));
			}

			$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($entity);
			$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::SERVER_SECRET);

			$serverSecretProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($serverSecretProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'connector' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::SERVER_SECRET->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'unit' => null,
					'format' => null,
					'value' => Helpers\Protocol::generateSignKey(),
				]));
			}

			$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($entity);
			$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::CONFIG_VERSION);

			$versionProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($versionProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'connector' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::CONFIG_VERSION->value,
					'dataType' => ValuesTypes\DataType::USHORT,
					'unit' => null,
					'format' => null,
					'value' => 1,
				]));
			}

			$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($entity);
			$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::PAIRED);

			$pairedProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($pairedProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'connector' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::PAIRED->value,
					'dataType' => ValuesTypes\DataType::BOOLEAN,
					'unit' => null,
					'format' => null,
					'value' => false,
				]));
			}

			$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($entity);
			$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::XHM_URI);

			$xhmUriProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($xhmUriProperty === null) {
				$xhmUri = Helpers\Protocol::getXhmUri(
					$entity->getPinCode(),
					$entity->getSetupId(),
					HomeKitTypes\AccessoryCategory::BRIDGE,
				);

				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'connector' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::XHM_URI->value,
					'dataType' => ValuesTypes\DataType::STRING,
					'unit' => null,
					'format' => null,
					'value' => $xhmUri,
				]));
			}
		} elseif ($entity instanceof Entities\Devices\Device) {
			$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($entity);
			$findDevicePropertyQuery->byIdentifier(HomeKitTypes\DevicePropertyIdentifier::STATE);

			$stateProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

			if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
				$this->devicesPropertiesManager->delete($stateProperty);

				$stateProperty = null;
			}

			if ($stateProperty !== null) {
				$this->devicesPropertiesManager->update($stateProperty, Utils\ArrayHash::from([
					'dataType' => ValuesTypes\DataType::ENUM,
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
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'device' => $entity,
					'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
					'identifier' => HomeKitTypes\DevicePropertyIdentifier::STATE->value,
					'name' => DevicesUtilities\Name::createName(HomeKitTypes\DevicePropertyIdentifier::STATE->value),
					'dataType' => ValuesTypes\DataType::ENUM,
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

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws HomeKitExceptions\InvalidArgument
	 * @throws HomeKitExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if ($entity instanceof DevicesEntities\Connectors\Properties\Variable) {
			if (
				$entity->getIdentifier() === HomeKitTypes\ConnectorPropertyIdentifier::PIN_CODE->value
				|| $entity->getIdentifier() === HomeKitTypes\ConnectorPropertyIdentifier::SETUP_ID->value
			) {
				$connector = $entity->getConnector();
				assert($connector instanceof Entities\Connectors\Connector);

				$xhmUri = Helpers\Protocol::getXhmUri(
					$connector->getPinCode(),
					$connector->getSetupId(),
					HomeKitTypes\AccessoryCategory::BRIDGE,
				);

				$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
				$findConnectorPropertyQuery->forConnector($connector);
				$findConnectorPropertyQuery->byIdentifier(HomeKitTypes\ConnectorPropertyIdentifier::XHM_URI);

				$xhmUriProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

				if ($xhmUriProperty === null) {
					$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
						'connector' => $entity,
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => HomeKitTypes\ConnectorPropertyIdentifier::XHM_URI->value,
						'dataType' => ValuesTypes\DataType::STRING,
						'unit' => null,
						'format' => null,
						'value' => $xhmUri,
					]));
				} else {
					$this->connectorsPropertiesManager->update($entity, Utils\ArrayHash::from([
						'value' => $xhmUri,
					]));
				}
			}
		}
	}

}
