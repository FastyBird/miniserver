<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\Modbus\Subscribers;

use Doctrine\Common;
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions as ModbusExceptions;
use FastyBird\Connector\Modbus\Queries;
use FastyBird\Connector\Modbus\Types as ModbusTypes;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Persistence\Exceptions as PersistenceExceptions;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function intval;
use function sprintf;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:ModbusConnector!
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
	 * @throws ModbusExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws PersistenceExceptions\EntityCreation
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\Devices\Device) {
			$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($entity);
			$findDevicePropertyQuery->byIdentifier(ModbusTypes\DevicePropertyIdentifier::STATE);

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
						DevicesTypes\ConnectionState::LOST->value,
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
					'identifier' => ModbusTypes\DevicePropertyIdentifier::STATE->value,
					'dataType' => ValuesTypes\DataType::ENUM,
					'unit' => null,
					'format' => [
						DevicesTypes\ConnectionState::CONNECTED->value,
						DevicesTypes\ConnectionState::DISCONNECTED->value,
						DevicesTypes\ConnectionState::LOST->value,
						DevicesTypes\ConnectionState::ALERT->value,
						DevicesTypes\ConnectionState::UNKNOWN->value,
					],
					'settable' => false,
					'queryable' => false,
				]));
			}
		} elseif (
			$entity instanceof DevicesEntities\Connectors\Properties\Variable
			&& $entity->getConnector() instanceof Entities\Connectors\Connector
		) {
			if (
				(
					$entity->getIdentifier() === ModbusTypes\ConnectorPropertyIdentifier::CLIENT_MODE->value
					&& ModbusTypes\ClientMode::tryFrom(Utilities\Value::toString($entity->getValue(), true)) === null
				) || (
					$entity->getIdentifier() === ModbusTypes\ConnectorPropertyIdentifier::RTU_BYTE_SIZE->value
					&& ModbusTypes\ByteSize::tryFrom(
						intval(Utilities\Value::flattenValue($entity->getValue())),
					) === null
				) || (
					$entity->getIdentifier() === ModbusTypes\ConnectorPropertyIdentifier::RTU_BAUD_RATE->value
					&& ModbusTypes\BaudRate::tryFrom(
						intval(Utilities\Value::flattenValue($entity->getValue())),
					) === null
				) || (
					$entity->getIdentifier() === ModbusTypes\ConnectorPropertyIdentifier::RTU_PARITY->value
					&& ModbusTypes\Parity::tryFrom(
						intval(Utilities\Value::flattenValue($entity->getValue())),
					) === null
				) || (
					$entity->getIdentifier() === ModbusTypes\ConnectorPropertyIdentifier::RTU_STOP_BITS->value
					&& ModbusTypes\StopBits::tryFrom(
						intval(Utilities\Value::flattenValue($entity->getValue())),
					) === null
				)
			) {
				throw new DevicesExceptions\InvalidArgument(sprintf(
					'Provided value for connector property: %s is not in valid range',
					$entity->getIdentifier(),
				));
			}
		} elseif (
			$entity instanceof DevicesEntities\Devices\Properties\Variable
			&& $entity->getDevice() instanceof Entities\Devices\Device
		) {
			if (
				(
					$entity->getIdentifier() === ModbusTypes\DevicePropertyIdentifier::BYTE_ORDER->value
					&& ModbusTypes\ByteOrder::tryFrom(Utilities\Value::toString($entity->getValue(), true)) === null
				)
			) {
				throw new DevicesExceptions\InvalidArgument(sprintf(
					'Provided value for device property: %s is not in valid range',
					$entity->getIdentifier(),
				));
			}
		}
	}

}
