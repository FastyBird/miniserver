<?php declare(strict_types = 1);

/**
 * Bridge.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Helpers\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions as Zigbee2MqttExceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use TypeError;
use ValueError;
use function assert;
use function is_string;

/**
 * Bridge device helper
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Bridge
{

	public function __construct(
		private DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getBaseTopic(Documents\Devices\Bridge $device): string
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::BASE_TOPIC);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return Entities\Devices\Bridge::BASE_TOPIC;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getFriendlyName(Documents\Devices\Bridge $device): string|null
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::FRIENDLY_NAME);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Zigbee2MqttExceptions\InvalidArgument
	 * @throws Zigbee2MqttExceptions\InvalidState
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getIeeeAddress(Documents\Devices\Bridge $device): string
	{
		$findPropertyQuery = new Queries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IEEE_ADDRESS);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			throw new Zigbee2MqttExceptions\InvalidState('Bridge have to have configured IEEE address');
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

}
