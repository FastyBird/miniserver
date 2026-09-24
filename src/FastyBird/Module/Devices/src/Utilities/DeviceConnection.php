<?php declare(strict_types = 1);

/**
 * DeviceConnection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           19.07.22
 */

namespace FastyBird\Module\Devices\Utilities;

use DateTimeInterface;
use Doctrine\DBAL;
use FastyBird\Core\Documents\Exceptions as DocumentsExceptions;
use FastyBird\Core\Exceptions as ApplicationExceptions;
use FastyBird\Core\Persistence\Helpers;
use FastyBird\Core\Values\Types as ValuesTypes;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
use function assert;
use function sprintf;

/**
 * Device connection states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConnection
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Entities\Devices\DevicesRepository $devicesEntitiesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesManager $devicesPropertiesEntitiesManager,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\States\DevicePropertiesManager $propertiesStatesManager,
		private readonly Helpers\Database $databaseHelper,
		private readonly Devices\Logger $logger,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function setState(
		Entities\Devices\Device|Documents\Devices\Device $device,
		DevicesTypes\ConnectionState $state,
	): bool
	{
		$currentState = $this->getState($device);

		if ($currentState === $state) {
			return true;
		}

		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(DevicesTypes\DevicePropertyIdentifier::STATE->value);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Devices\Properties\Dynamic::class,
		);

		if ($property === null) {
			$property = $this->databaseHelper->transaction(
				function () use ($device): Entities\Devices\Properties\Dynamic {
					if (!$device instanceof Entities\Devices\Device) {
						$device = $this->devicesEntitiesRepository->find($device->getId());
						assert($device instanceof Entities\Devices\Device);
					}

					$property = $this->devicesPropertiesEntitiesManager->create(Utils\ArrayHash::from([
						'device' => $device,
						'entity' => Entities\Devices\Properties\Dynamic::class,
						'identifier' => DevicesTypes\ConnectorPropertyIdentifier::STATE->value,
						'dataType' => ValuesTypes\DataType::ENUM,
						'unit' => null,
						'format' => [
							DevicesTypes\ConnectionState::CONNECTED->value,
							DevicesTypes\ConnectionState::DISCONNECTED->value,
							DevicesTypes\ConnectionState::RUNNING->value,
							DevicesTypes\ConnectionState::SLEEPING->value,
							DevicesTypes\ConnectionState::STOPPED->value,
							DevicesTypes\ConnectionState::LOST->value,
							DevicesTypes\ConnectionState::ALERT->value,
							DevicesTypes\ConnectionState::UNKNOWN->value,
						],
						'settable' => false,
						'queryable' => false,
					]));
					assert($property instanceof Entities\Devices\Properties\Dynamic);

					return $property;
				},
			);
		}

		$property = $this->devicesPropertiesConfigurationRepository->find($property->getId());
		assert($property instanceof Documents\Devices\Properties\Dynamic);

		$this->propertiesStatesManager->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::ACTUAL_VALUE_FIELD => $state->value,
				States\Property::EXPECTED_VALUE_FIELD => null,
			]),
			Sources\Module::DEVICES,
		);

		$this->logger->info(
			sprintf('Device state was changed to: %s', $state->value),
			[
				'source' => Sources\Module::DEVICES->value,
				'type' => 'device-connection-helper',
				'device' => $device->getId()->toString(),
				'state' => $state->value,
			],
		);

		return false;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getState(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): DevicesTypes\ConnectionState
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(DevicesTypes\DevicePropertyIdentifier::STATE->value);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Devices\Properties\Dynamic::class,
		);

		if ($property instanceof Documents\Devices\Properties\Dynamic) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& DevicesTypes\ConnectionState::tryFrom(
					Utilities\Value::toString($state->getRead()->getActualValue(), true),
				) !== null
			) {
				return DevicesTypes\ConnectionState::from(
					Utilities\Value::toString($state->getRead()->getActualValue(), true),
				);
			}
		}

		return DevicesTypes\ConnectionState::UNKNOWN;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getStateTime(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): DateTimeInterface|null
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(DevicesTypes\DevicePropertyIdentifier::STATE->value);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Devices\Properties\Dynamic::class,
		);

		if ($property instanceof Documents\Devices\Properties\Dynamic) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& DevicesTypes\ConnectionState::tryFrom(
					Utilities\Value::toString($state->getRead()->getActualValue(), true),
				) !== null
			) {
				return $state->getUpdatedAt();
			}
		}

		return null;
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Logic
	 * @throws DocumentsExceptions\MalformedInput
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws ApplicationExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getLostAt(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): DateTimeInterface|null
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(DevicesTypes\DevicePropertyIdentifier::STATE->value);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Devices\Properties\Dynamic::class,
		);

		if ($property instanceof Documents\Devices\Properties\Dynamic) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& DevicesTypes\ConnectionState::tryFrom(
					Utilities\Value::toString($state->getRead()->getActualValue(), true),
				) !== null
				&& $state->getRead()->getActualValue() === DevicesTypes\ConnectionState::LOST->value
			) {
				return $state->getUpdatedAt();
			}
		}

		return null;
	}

}
