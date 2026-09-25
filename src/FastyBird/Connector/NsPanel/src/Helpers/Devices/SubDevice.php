<?php declare(strict_types = 1);

/**
 * SubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           01.12.23
 */

namespace FastyBird\Connector\NsPanel\Helpers\Devices;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions as NsPanelExceptions;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use TypeError;
use ValueError;
use function assert;
use function is_string;

/**
 * Sub device helper
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SubDevice
{

	public function __construct(
		private DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidState
	 */
	public function getGateway(Documents\Devices\SubDevice $device): Documents\Devices\Gateway
	{
		foreach ($device->getParents() as $parent) {
			$findDeviceQuery = new Queries\Configuration\FindGatewayDevices();
			$findDeviceQuery->byId($parent);

			$parent = $this->devicesConfigurationRepository->findOneBy(
				$findDeviceQuery,
				Documents\Devices\Gateway::class,
			);

			if ($parent !== null) {
				return $parent;
			}
		}

		throw new NsPanelExceptions\InvalidState('Sub-device have to have parent gateway defined');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getDisplayCategory(Documents\Devices\SubDevice $device): Types\Category
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::CATEGORY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return Types\Category::UNKNOWN;
		}

		$value = $property->getValue();
		assert(is_string($value));

		if (Types\Category::tryFrom($value) === null) {
			return Types\Category::UNKNOWN;
		}

		return Types\Category::from(Utilities\Value::toString($property->getValue(), true));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getManufacturer(Documents\Devices\SubDevice $device): string
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MANUFACTURER);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return 'N/A';
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getModel(Documents\Devices\SubDevice $device): string
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MODEL);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return 'N/A';
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws NsPanelExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFirmwareVersion(Documents\Devices\SubDevice $device): string
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::FIRMWARE_VERSION);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return 'N/A';
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

}
